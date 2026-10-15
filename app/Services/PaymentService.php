<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Audit;
use App\Models\Order;

class PaymentService
{
    /**
     * Admin verifies payment proof. Anti double-approve via SELECT FOR UPDATE +
     * conditional status update (only from PAYMENT_REVIEW).
     */
    public function verify(int $orderId, int $adminId, string $decision, string $note = ''): array
    {
        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new \RuntimeException('Keputusan tidak valid.');
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$orderId]);
            $order = $st->fetch();
            if (!$order) {
                throw new \RuntimeException('Order tidak ditemukan.');
            }
            if ($order['status'] !== 'PAYMENT_REVIEW') {
                throw new \RuntimeException('Order sudah diproses (status: ' . $order['status'] . '). Muat ulang antrean.');
            }
            $to = $decision === 'approve' ? 'PAID' : 'PAYMENT_REJECTED';
            if ($decision === 'reject' && trim($note) === '') {
                throw new \RuntimeException('Alasan penolakan wajib diisi.');
            }
            Order::setStatus($pdo, $orderId, 'PAYMENT_REVIEW', $to, $adminId, $note);
            $pdo->prepare(
                'INSERT INTO payment_reviews (order_id, admin_id, decision, note, created_at) VALUES (?, ?, ?, ?, NOW())'
            )->execute([$orderId, $adminId, $to, $note]);
            $pdo->commit();
            Audit::log($adminId, 'admin', 'payment.verify', 'orders', $orderId, ['from' => 'PAYMENT_REVIEW'], ['to' => $to, 'note' => $note]);
            return Order::find($orderId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
