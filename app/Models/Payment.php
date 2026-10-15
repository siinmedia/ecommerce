<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Payment
{
    public static function banks(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM bank_accounts';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function proofsForOrder(int $orderId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payment_proofs WHERE order_id = ? ORDER BY id DESC');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    public static function reviewQueue(): array
    {
        return Database::pdo()->query(
            "SELECT o.*, u.email AS user_email, u.name AS user_name,
              (SELECT COUNT(*) FROM payment_proofs pp WHERE pp.order_id = o.id) AS proof_count
             FROM orders o JOIN users u ON u.id = o.user_id
             WHERE o.status = 'PAYMENT_REVIEW' ORDER BY o.updated_at ASC"
        )->fetchAll();
    }

    public static function reviewsForOrder(int $orderId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT pr.*, u.email AS admin_email FROM payment_reviews pr
             LEFT JOIN users u ON u.id = pr.admin_id WHERE pr.order_id = ? ORDER BY pr.id DESC'
        );
        $st->execute([$orderId]);
        return $st->fetchAll();
    }
}
