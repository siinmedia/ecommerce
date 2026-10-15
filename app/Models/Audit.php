<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Audit
{
    public static function log(?int $actorId, string $actorRole, string $action, string $entity, ?int $entityId, ?array $before = null, ?array $after = null): void
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO audit_logs (actor_id, actor_role, action, entity, entity_id, before_json, after_json, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([
            $actorId, $actorRole, $action, $entity, $entityId,
            $before !== null ? json_encode($before) : null,
            $after !== null ? json_encode($after) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function list(int $page = 1, int $perPage = 30): array
    {
        $off = ($page - 1) * $perPage;
        $st = Database::pdo()->prepare(
            'SELECT a.*, u.email AS actor_email FROM audit_logs a
             LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.id DESC LIMIT :l OFFSET :o'
        );
        $st->bindValue(':l', $perPage, PDO::PARAM_INT);
        $st->bindValue(':o', $off, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
