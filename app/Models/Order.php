<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    public const TRANSITIONS = [
        'PENDING_PAYMENT' => ['PAYMENT_REVIEW', 'EXPIRED', 'CANCELLED'],
        'PAYMENT_REVIEW' => ['PAID', 'PAYMENT_REJECTED', 'EXPIRED'],
        'PAYMENT_REJECTED' => ['PAYMENT_REVIEW', 'EXPIRED', 'CANCELLED'],
        'PAID' => ['PROCESSING', 'REFUND_PENDING'],
        'PROCESSING' => ['SHIPPED', 'CANCELLED', 'REFUND_PENDING'],
        'SHIPPED' => ['DELIVERED', 'RETURN_REQUESTED'],
        'DELIVERED' => ['RETURN_REQUESTED', 'COMPLETED'],
        'COMPLETED' => [],
        'EXPIRED' => ['CANCELLED'],
        'CANCELLED' => [],
        'RETURN_REQUESTED' => ['RETURN_APPROVED', 'RETURN_REJECTED'],
        'RETURN_APPROVED' => ['REFUND_PENDING'],
        'RETURN_REJECTED' => ['COMPLETED'],
        'REFUND_PENDING' => ['REFUNDED', 'REFUND_FAILED'],
        'REFUND_FAILED' => ['REFUND_PENDING'],
        'REFUNDED' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function findByCode(string $code): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM orders WHERE order_code = ? LIMIT 1');
        $st->execute([$code]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function items(int $orderId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    public static function history(int $orderId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    public static function forUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        $off = ($page - 1) * $perPage;
        // NOTE: PDO forbids mixing positional and named placeholders — all positional here.
        $st = Database::pdo()->prepare(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?'
        );
        $st->bindValue(1, $userId, PDO::PARAM_INT);
        $st->bindValue(2, $perPage, PDO::PARAM_INT);
        $st->bindValue(3, $off, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'o.status = :st';
            $params[':st'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(o.order_code LIKE :q1 OR u.email LIKE :q2)';
            $like = '%' . $filters['q'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }
        $w = implode(' AND ', $where);
        $off = ($page - 1) * $perPage;
        $st = Database::pdo()->prepare(
            "SELECT o.*, u.email AS user_email, u.name AS user_name FROM orders o
             JOIN users u ON u.id = o.user_id WHERE {$w} ORDER BY o.id DESC LIMIT :l OFFSET :o"
        );
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':l', $perPage, PDO::PARAM_INT);
        $st->bindValue(':o', $off, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function recordHistory(PDO $pdo, int $orderId, ?string $from, string $to, ?int $actorId, string $note = ''): void
    {
        $st = $pdo->prepare(
            'INSERT INTO order_status_history (order_id, from_status, to_status, actor_id, note, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([$orderId, $from, $to, $actorId, $note]);
    }

    public static function setStatus(PDO $pdo, int $orderId, string $from, string $to, ?int $actorId, string $note = ''): void
    {
        if (!self::canTransition($from, $to)) {
            throw new \RuntimeException("Transisi {$from} -> {$to} tidak diizinkan.");
        }
        // Optimistic lock: only update if still in expected state
        $st = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND status = ?');
        $st->execute([$to, $orderId, $from]);
        if ($st->rowCount() !== 1) {
            throw new \RuntimeException('Status order berubah oleh proses lain. Muat ulang halaman.');
        }
        self::recordHistory($pdo, $orderId, $from, $to, $actorId, $note);
    }

    public static function dashboardStats(): array
    {
        $pdo = Database::pdo();
        return [
            'pending_payment' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'PENDING_PAYMENT'")->fetchColumn(),
            'payment_review' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'PAYMENT_REVIEW'")->fetchColumn(),
            'paid' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'PAID'")->fetchColumn(),
            'revenue' => (int)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE status IN ('PAID','PROCESSING','SHIPPED','DELIVERED','COMPLETED')")->fetchColumn(),
            'low_stock' => (int)$pdo->query('SELECT COUNT(*) FROM product_variants WHERE stock <= low_stock_threshold AND status = "active"')->fetchColumn(),
        ];
    }
}
