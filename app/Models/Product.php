<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Product
{
    public static function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = ["p.status = 'published'"];
        $params = [];
        if (!empty($filters['q'])) {
            // NOTE: named placeholders cannot be reused with native prepares — use 3 distinct keys.
            $where[] = '(p.name LIKE :q1 OR p.description LIKE :q2 OR p.sku LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = :cat';
            $params[':cat'] = $filters['category'];
        }
        if (!empty($filters['min_price'])) {
            $where[] = 'p.price >= :minp';
            $params[':minp'] = (int)$filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = 'p.price <= :maxp';
            $params[':maxp'] = (int)$filters['max_price'];
        }
        $sort = match ($filters['sort'] ?? 'newest') {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.id DESC',
        };
        $w = implode(' AND ', $where);
        $off = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p LEFT JOIN categories c ON c.id = p.category_id
                WHERE {$w} ORDER BY {$sort} LIMIT :l OFFSET :o";
        $st = Database::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':l', $perPage, PDO::PARAM_INT);
        $st->bindValue(':o', $off, PDO::PARAM_INT);
        $st->execute();
        $items = $st->fetchAll();

        $cst = Database::pdo()->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE {$w}");
        foreach ($params as $k => $v) {
            $cst->bindValue($k, $v);
        }
        $cst->execute();
        return ['items' => $items, 'total' => (int)$cst->fetchColumn()];
    }

    public static function findBySlug(string $slug): ?array
    {
        $st = Database::pdo()->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? LIMIT 1"
        );
        $st->execute([$slug]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $row['variants'] = self::variants((int)$row['id']);
        return $row;
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function variants(int $productId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM product_variants WHERE product_id = ? AND status = "active" ORDER BY id');
        $st->execute([$productId]);
        return $st->fetchAll();
    }

    public static function findVariant(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM product_variants WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Lock variant row for stock mutation. Must run inside a transaction. */
    public static function lockVariant(PDO $pdo, int $variantId): ?array
    {
        $st = $pdo->prepare('SELECT * FROM product_variants WHERE id = ? FOR UPDATE');
        $st->execute([$variantId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function decrementStock(PDO $pdo, int $variantId, int $qty): void
    {
        $st = $pdo->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $st->execute([$qty, $variantId, $qty]);
        if ($st->rowCount() !== 1) {
            throw new \RuntimeException('Stok tidak mencukupi.');
        }
    }

    public static function incrementStock(PDO $pdo, int $variantId, int $qty): void
    {
        $st = $pdo->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?');
        $st->execute([$qty, $variantId]);
    }

    public static function adminList(int $page = 1, int $perPage = 20): array
    {
        $off = ($page - 1) * $perPage;
        $st = Database::pdo()->prepare(
            'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC LIMIT :l OFFSET :o'
        );
        $st->bindValue(':l', $perPage, PDO::PARAM_INT);
        $st->bindValue(':o', $off, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function create(array $d): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO products (category_id, name, slug, sku, description, price, compare_price, weight_gram, status, created_at, updated_at)
             VALUES (:cat, :name, :slug, :sku, :desc, :price, :cmp, :w, :status, NOW(), NOW())'
        );
        $st->execute([
            ':cat' => $d['category_id'] ?: null,
            ':name' => $d['name'],
            ':slug' => $d['slug'],
            ':sku' => $d['sku'],
            ':desc' => $d['description'] ?? '',
            ':price' => (int)$d['price'],
            ':cmp' => $d['compare_price'] !== '' ? (int)$d['compare_price'] : null,
            ':w' => (int)($d['weight_gram'] ?? 0),
            ':status' => $d['status'] ?? 'draft',
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE products SET category_id = :cat, name = :name, slug = :slug, sku = :sku,
             description = :desc, price = :price, compare_price = :cmp, weight_gram = :w, status = :status, updated_at = NOW()
             WHERE id = :id'
        );
        $st->execute([
            ':cat' => $d['category_id'] ?: null,
            ':name' => $d['name'],
            ':slug' => $d['slug'],
            ':sku' => $d['sku'],
            ':desc' => $d['description'] ?? '',
            ':price' => (int)$d['price'],
            ':cmp' => $d['compare_price'] !== '' ? (int)$d['compare_price'] : null,
            ':w' => (int)($d['weight_gram'] ?? 0),
            ':status' => $d['status'] ?? 'draft',
            ':id' => $id,
        ]);
    }

    public static function upsertVariant(int $productId, array $v): void
    {
        if (!empty($v['id'])) {
            $st = Database::pdo()->prepare(
                'UPDATE product_variants SET name = ?, sku = ?, price = ?, stock = ?, weight_gram = ?, status = ? WHERE id = ? AND product_id = ?'
            );
            $st->execute([$v['name'], $v['sku'], (int)$v['price'], (int)$v['stock'], (int)($v['weight_gram'] ?? 0), $v['status'] ?? 'active', (int)$v['id'], $productId]);
        } else {
            $st = Database::pdo()->prepare(
                'INSERT INTO product_variants (product_id, name, sku, price, stock, weight_gram, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([$productId, $v['name'], $v['sku'], (int)$v['price'], (int)$v['stock'], (int)($v['weight_gram'] ?? 0), $v['status'] ?? 'active']);
        }
    }

    public static function categories(): array
    {
        return Database::pdo()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }
}
