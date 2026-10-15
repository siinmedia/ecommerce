<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Helpers;
use App\Models\Audit;
use App\Models\Order;
use App\Models\Product;
use PDO;

class OrderService
{
    /**
     * Create order from validated cart snapshot.
     * All totals recomputed server-side inside a transaction with row locks.
     *
     * @param array $items each: ['variant_id'=>int,'qty'=>int]
     * @param array $shipping ['courier'=>,'service'=>,'cost'=>int,'etd'=>,'origin_id'=>,'destination_id'=>,'destination_label'=>]
     * @param array|null $promo ['code'=>,'type'=>,'value'=>,'discount'=>int]
     */
    public function create(
        int $userId,
        int $addressId,
        int $bankId,
        array $items,
        array $shipping,
        ?array $promo,
        string $idempotencyKey,
        string $note = ''
    ): array {
        $pdo = Database::pdo();

        // Idempotency: same key returns existing order
        $st = $pdo->prepare('SELECT * FROM orders WHERE idempotency_key = ? LIMIT 1');
        $st->execute([$idempotencyKey]);
        if ($existing = $st->fetch()) {
            return $existing;
        }

        $pdo->beginTransaction();
        try {
            $subtotal = 0;
            $weight = 0;
            $lines = [];
            foreach ($items as $it) {
                $variantId = (int)$it['variant_id'];
                $qty = (int)$it['qty'];
                if ($qty < 1 || $qty > 99) {
                    throw new \RuntimeException('Qty tidak valid.');
                }
                $v = Product::lockVariant($pdo, $variantId);
                if (!$v || $v['status'] !== 'active') {
                    throw new \RuntimeException('Varian produk tidak tersedia.');
                }
                $pst = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
                $pst->execute([$v['product_id']]);
                $p = $pst->fetch();
                if (!$p || $p['status'] !== 'published') {
                    throw new \RuntimeException('Produk tidak tersedia.');
                }
                if ((int)$v['stock'] < $qty) {
                    throw new \RuntimeException("Stok '{$p['name']}' tidak mencukupi (sisa {$v['stock']}).");
                }
                $price = (int)$v['price'];
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;
                $weight += ((int)($v['weight_gram'] ?: $p['weight_gram'])) * $qty;
                $lines[] = [
                    'variant' => $v, 'product' => $p, 'qty' => $qty,
                    'price' => $price, 'line_total' => $lineTotal,
                ];
            }

            // Re-validate promo server-side
            $discount = 0;
            $promoId = null;
            if ($promo !== null) {
                $promoId = (int)$promo['id'];
                $pr = $pdo->prepare('SELECT * FROM promotions WHERE id = ? FOR UPDATE');
                $pr->execute([$promoId]);
                $row = $pr->fetch();
                if (!$row || $row['is_active'] != 1) {
                    throw new \RuntimeException('Promo tidak aktif.');
                }
                $now = date('Y-m-d H:i:s');
                if ($row['starts_at'] > $now || $row['ends_at'] < $now) {
                    throw new \RuntimeException('Promo kedaluwarsa.');
                }
                if ($subtotal < (int)$row['min_spend']) {
                    throw new \RuntimeException('Belanja belum memenuhi minimum promo.');
                }
                if ((int)$row['used_count'] >= (int)$row['quota']) {
                    throw new \RuntimeException('Kuota promo habis.');
                }
                $discount = $row['type'] === 'percent'
                    ? (int)round($subtotal * ((int)$row['value'] / 100))
                    : min((int)$row['value'], $subtotal);
                if (!empty($row['max_discount']) && $discount > (int)$row['max_discount']) {
                    $discount = (int)$row['max_discount'];
                }
                $pdo->prepare('UPDATE promotions SET used_count = used_count + 1 WHERE id = ?')->execute([$promoId]);
                $pdo->prepare('INSERT INTO promotion_redemptions (promotion_id, user_id, order_id, created_at) VALUES (?, ?, NULL, NOW())')
                    ->execute([$promoId, $userId]);
            }

            $shippingCost = (int)($shipping['cost'] ?? 0);
            if ($shippingCost < 0 || empty($shipping['courier']) || empty($shipping['service'])) {
                throw new \RuntimeException('Layanan pengiriman tidak valid.');
            }
            $grandTotal = $subtotal - $discount + $shippingCost;
            if ($grandTotal <= 0) {
                throw new \RuntimeException('Total order tidak valid.');
            }

            // Validate address & bank ownership/existence
            $ast = $pdo->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ? LIMIT 1');
            $ast->execute([$addressId, $userId]);
            $addr = $ast->fetch();
            if (!$addr) {
                throw new \RuntimeException('Alamat tidak valid.');
            }
            $bst = $pdo->prepare('SELECT * FROM bank_accounts WHERE id = ? AND is_active = 1 LIMIT 1');
            $bst->execute([$bankId]);
            $bank = $bst->fetch();
            if (!$bank) {
                throw new \RuntimeException('Rekening tujuan tidak valid.');
            }

            $code = Helpers::orderCode();
            $expiryHours = 24;
            $ost = $pdo->prepare(
                'INSERT INTO orders (order_code, user_id, address_id, bank_account_id, status,
                 subtotal, discount, shipping_cost, grand_total,
                 courier, courier_service, shipping_etd, shipping_snapshot_json,
                 promo_code, note, idempotency_key, expires_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, "PENDING_PAYMENT", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW(), NOW())'
            );
            $ost->execute([
                $code, $userId, $addressId, $bankId,
                $subtotal, $discount, $shippingCost, $grandTotal,
                $shipping['courier'], $shipping['service'], $shipping['etd'] ?? '',
                json_encode($shipping), $promo['code'] ?? null, $note, $idempotencyKey,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // Tautkan redemption promo yang masih NULL ke order ini (dalam transaksi yang sama).
            if ($promoId !== null) {
                $pdo->prepare(
                    'UPDATE promotion_redemptions SET order_id = ? WHERE promotion_id = ? AND user_id = ? AND order_id IS NULL'
                )->execute([$orderId, $promoId, $userId]);
            }

            $ist = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, sku, price, qty, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($lines as $l) {
                $ist->execute([
                    $orderId, $l['product']['id'], $l['variant']['id'],
                    $l['product']['name'], $l['variant']['name'], $l['variant']['sku'],
                    $l['price'], $l['qty'], $l['line_total'],
                ]);
                Product::decrementStock($pdo, (int)$l['variant']['id'], $l['qty']);
            }

            Order::recordHistory($pdo, $orderId, null, 'PENDING_PAYMENT', $userId, 'Order dibuat');
            // Snapshot address at order time
            $pdo->prepare(
                'INSERT INTO order_addresses (order_id, recipient, phone, address_line, city_label, postal_code, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $orderId, $addr['recipient'], $addr['phone'], $addr['address_line'],
                $addr['city_label'], $addr['postal_code'], $addr['note'] ?? '',
            ]);

            // Create shipment row with locked rate snapshot
            $pdo->prepare(
                'INSERT INTO shipments (order_id, courier, service, cost, etd, origin_id, destination_id, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "pending", NOW())'
            )->execute([
                $orderId, $shipping['courier'], $shipping['service'], $shippingCost,
                $shipping['etd'] ?? '', $shipping['origin_id'] ?? '', $shipping['destination_id'] ?? '',
            ]);

            $pdo->commit();
            Audit::log($userId, 'user', 'order.create', 'orders', $orderId, null, ['code' => $code, 'total' => $grandTotal]);
            return Order::find($orderId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function transition(int $orderId, string $to, ?int $actorId, string $actorRole, string $note = ''): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$orderId]);
            $order = $st->fetch();
            if (!$order) {
                throw new \RuntimeException('Order tidak ditemukan.');
            }
            $from = $order['status'];
            Order::setStatus($pdo, $orderId, $from, $to, $actorId, $note);

            // Release stock on terminal unpaid states
            if (in_array($to, ['EXPIRED', 'CANCELLED'], true) && in_array($from, ['PENDING_PAYMENT', 'PAYMENT_REVIEW', 'PAYMENT_REJECTED'], true)) {
                $items = Order::items($orderId);
                foreach ($items as $it) {
                    if ($it['variant_id']) {
                        Product::incrementStock($pdo, (int)$it['variant_id'], (int)$it['qty']);
                    }
                }
            }
            $pdo->commit();
            Audit::log($actorId, $actorRole, 'order.transition', 'orders', $orderId, ['from' => $from], ['to' => $to, 'note' => $note]);
            return Order::find($orderId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
