<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $hash, string $role = 'user'): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, "active", NOW())'
        );
        $st->execute([$name, $email, $hash, $role]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function paginateCustomers(int $page = 1, int $perPage = 20): array
    {
        $off = ($page - 1) * $perPage;
        $st = Database::pdo()->prepare(
            "SELECT id, name, email, phone, status, created_at FROM users WHERE role = 'user' ORDER BY id DESC LIMIT :l OFFSET :o"
        );
        $st->bindValue(':l', $perPage, PDO::PARAM_INT);
        $st->bindValue(':o', $off, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
