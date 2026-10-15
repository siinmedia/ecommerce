<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\Validator;
use App\Core\View;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\RealRajaOngkir;
use App\Services\UploadService;

class CheckoutController
{
    private array $config;

    public function __construct()
    {
        $this->config = require Database::configPath();
    }

    private function raja(): \App\Services\RajaOngkirInterface
    {
        return RealRajaOngkir::make($this->config['rajaongkir']);
    }

    private function addresses(int $userId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function show(array $p = []): void
    {
        Auth::requireLogin();
        $cart = CartController::lines();
        if ($cart['lines'] === []) {
            Helpers::flash('error', 'Keranjang kosong.');
            Helpers::redirect('/products');
        }
        // Re-validate stock before checkout display
        foreach ($cart['lines'] as $l) {
            if ($l['over_stock']) {
                Helpers::flash('error', "Stok '{$l['product_name']}' berubah (sisa {$l['stock']}). Sesuaikan qty.");
                Helpers::redirect('/cart');
            }
        }
        View::layout('store/checkout', [
            'title' => 'Checkout',
            'user' => Auth::user(),
            'config' => $this->config,
            'cart' => $cart,
            'addresses' => $this->addresses(Auth::id()),
            'banks' => Payment::banks(),
            'raja_mode' => $this->config['rajaongkir']['mode'],
            'origin_id' => $this->config['rajaongkir']['origin_city_id'],
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ]);
    }

    /** AJAX: search destination */
    public function destSearch(array $p = []): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        try {
            $res = $this->raja()->searchDestination(trim($_GET['q'] ?? ''));
            echo json_encode(['ok' => true, 'data' => $res]);
        } catch (\Throwable $e) {
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** AJAX: shipping cost */
    public function shipCost(array $p = []): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        try {
            $destId = trim($_GET['destination'] ?? '');
            $courier = trim($_GET['courier'] ?? '');
            $cart = CartController::lines();
            $weight = 0;
            foreach ($cart['lines'] as $l) {
                $v = Product::findVariant($l['variant_id']);
                $prod = Product::find((int)$v['product_id']);
                $weight += ((int)($v['weight_gram'] ?: $prod['weight_gram'])) * $l['qty'];
            }
            $weight = max(1, $weight);
            $res = $this->raja()->cost($this->config['rajaongkir']['origin_city_id'], $destId, $weight, $courier);
            // Stash quote in session for order creation (snapshot must match a quoted rate)
            $_SESSION['ship_quote'] = [
                'origin_id' => $this->config['rajaongkir']['origin_city_id'],
                'destination_id' => $destId,
                'weight' => $weight,
                'rates' => $res,
                'quoted_at' => time(),
            ];
            echo json_encode(['ok' => true, 'data' => $res, 'weight' => $weight]);
        } catch (\Throwable $e) {
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** AJAX: validate promo */
    public function promoCheck(array $p = []): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $code = trim($_POST['code'] ?? '');
        $subtotal = (int)($_POST['subtotal'] ?? 0);
        $promo = $this->validatePromo($code, $subtotal, Auth::id());
        if (!$promo) {
            echo json_encode(['ok' => false, 'error' => 'Kode promo tidak valid / tidak memenuhi syarat.']);
            return;
        }
        $_SESSION['promo'] = $promo;
        echo json_encode(['ok' => true, 'data' => $promo]);
    }

    private function validatePromo(string $code, int $subtotal, int $userId): ?array
    {
        if ($code === '') {
            return null;
        }
        $st = Database::pdo()->prepare('SELECT * FROM promotions WHERE code = ? AND is_active = 1 LIMIT 1');
        $st->execute([strtoupper($code)]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $now = date('Y-m-d H:i:s');
        if ($row['starts_at'] > $now || $row['ends_at'] < $now) {
            return null;
        }
        if ($subtotal < (int)$row['min_spend']) {
            return null;
        }
        if ((int)$row['used_count'] >= (int)$row['quota']) {
            return null;
        }
        if ((int)$row['per_user_limit'] > 0) {
            $c = Database::pdo()->prepare('SELECT COUNT(*) FROM promotion_redemptions WHERE promotion_id = ? AND user_id = ?');
            $c->execute([$row['id'], $userId]);
            if ((int)$c->fetchColumn() >= (int)$row['per_user_limit']) {
                return null;
            }
        }
        $discount = $row['type'] === 'percent'
            ? (int)round($subtotal * ((int)$row['value'] / 100))
            : min((int)$row['value'], $subtotal);
        if (!empty($row['max_discount']) && $discount > (int)$row['max_discount']) {
            $discount = (int)$row['max_discount'];
        }
        return ['id' => (int)$row['id'], 'code' => $row['code'], 'type' => $row['type'], 'value' => (int)$row['value'], 'discount' => $discount];
    }

    /** POST /checkout/place — create order */
    public function place(array $p = []): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $userId = Auth::id();
        try {
            $cart = CartController::lines();
            if ($cart['lines'] === []) {
                throw new \RuntimeException('Keranjang kosong.');
            }
            $addressId = (int)($_POST['address_id'] ?? 0);
            $bankId = (int)($_POST['bank_id'] ?? 0);
            $courier = trim($_POST['courier'] ?? '');
            $service = trim($_POST['service'] ?? '');
            $note = trim($_POST['note'] ?? '');
            if ($addressId < 1 || $bankId < 1 || $courier === '' || $service === '') {
                throw new \RuntimeException('Alamat, rekening, dan layanan kirim wajib dipilih.');
            }
            // Shipping MUST come from a quoted rate in this session (no fabricated cost)
            $quote = $_SESSION['ship_quote'] ?? null;
            if (!$quote || (time() - $quote['quoted_at']) > 1800) {
                throw new \RuntimeException('Kutipan ongkir kedaluwarsa. Hitung ulang ongkir.');
            }
            $matched = null;
            foreach ($quote['rates'] as $r) {
                if ($r['courier'] === $courier && $r['service'] === $service) {
                    $matched = $r;
                    break;
                }
            }
            if (!$matched) {
                throw new \RuntimeException('Layanan kirim tidak sesuai kutipan RajaOngkir.');
            }
            $destLabel = trim($_POST['destination_label'] ?? '');
            $shipping = [
                'courier' => $courier,
                'service' => $service,
                'cost' => (int)$matched['cost'],
                'etd' => $matched['etd'],
                'origin_id' => $quote['origin_id'],
                'destination_id' => $quote['destination_id'],
                'destination_label' => $destLabel,
            ];
            // Re-validate promo server-side at order time
            $promo = null;
            $promoCode = trim($_POST['promo_code'] ?? '');
            if ($promoCode !== '') {
                $promo = $this->validatePromo($promoCode, $cart['subtotal'], $userId);
                if (!$promo) {
                    throw new \RuntimeException('Promo tidak valid / syarat tidak terpenuhi.');
                }
            }
            $items = array_map(fn($l) => ['variant_id' => $l['variant_id'], 'qty' => $l['qty']], $cart['lines']);
            $idem = trim($_POST['idempotency_key'] ?? '');
            if ($idem === '' || strlen($idem) > 64) {
                $idem = bin2hex(random_bytes(16));
            }
            $svc = new OrderService();
            $order = $svc->create($userId, $addressId, $bankId, $items, $shipping, $promo, $idem, $note);
            CartController::clearPurchased($userId);
            unset($_SESSION['ship_quote'], $_SESSION['promo']);
            Helpers::redirect('/orders/' . $order['order_code'] . '/payment');
        } catch (\Throwable $e) {
            Helpers::flash('error', $e->getMessage());
            Helpers::redirect('/checkout');
        }
    }

    /** GET /orders/{code}/payment — transfer instruction + upload */
    public function paymentPage(array $p): void
    {
        Auth::requireLogin();
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Order tidak ditemukan']);
            return;
        }
        $pdo = Database::pdo();
        $bank = $pdo->query('SELECT * FROM bank_accounts WHERE id = ' . (int)$order['bank_account_id'])->fetch();
        $items = Order::items((int)$order['id']);
        $proofs = Payment::proofsForOrder((int)$order['id']);
        $reviews = Payment::reviewsForOrder((int)$order['id']);
        View::layout('store/payment', [
            'title' => 'Pembayaran ' . $order['order_code'],
            'user' => Auth::user(), 'config' => $this->config,
            'order' => $order, 'bank' => $bank, 'items' => $items,
            'proofs' => $proofs, 'reviews' => $reviews,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ]);
    }

    /** POST /orders/{code}/payment/proof — upload proof */
    public function uploadProof(array $p): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Order tidak ditemukan']);
            return;
        }
        try {
            if (!in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REJECTED'], true)) {
                throw new \RuntimeException('Order ini tidak menerima bukti pembayaran (status: ' . $order['status'] . ').');
            }
            if (strtotime($order['expires_at']) < time()) {
                throw new \RuntimeException('Batas pembayaran sudah berakhir.');
            }
            $svc = UploadService::proofs($this->config);
            $filename = $svc->store($_FILES['proof'] ?? [], (int)$order['id']);
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            try {
                $pdo->prepare(
                    'INSERT INTO payment_proofs (order_id, user_id, filename, mime, size_bytes, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
                )->execute([(int)$order['id'], Auth::id(), $filename, $_FILES['proof']['type'] ?? '', (int)($_FILES['proof']['size'] ?? 0)]);
                // Move to review (allowed from PENDING_PAYMENT or PAYMENT_REJECTED)
                $from = $order['status'];
                $up = $pdo->prepare('UPDATE orders SET status = "PAYMENT_REVIEW", updated_at = NOW() WHERE id = ? AND status = ?');
                $up->execute([(int)$order['id'], $from]);
                if ($up->rowCount() !== 1) {
                    throw new \RuntimeException('Status order berubah. Coba lagi.');
                }
                Order::recordHistory($pdo, (int)$order['id'], $from, 'PAYMENT_REVIEW', Auth::id(), 'Bukti diunggah');
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                @unlink($svc->path($filename));
                throw $e;
            }
            \App\Models\Audit::log(Auth::id(), 'user', 'payment.upload', 'orders', (int)$order['id']);
            Helpers::flash('success', 'Bukti diterima, menunggu verifikasi Admin.');
            Helpers::redirect('/orders/' . $order['order_code'] . '/payment');
        } catch (\Throwable $e) {
            Helpers::flash('error', $e->getMessage());
            Helpers::redirect('/orders/' . $order['order_code'] . '/payment');
        }
    }

    /** GET /proof/{id} — serve private proof file (owner or admin only) */
    public function serveProof(array $p): void
    {
        Auth::requireLogin();
        $id = (int)($p['id'] ?? 0);
        $st = Database::pdo()->prepare('SELECT pp.*, o.user_id AS owner_id FROM payment_proofs pp JOIN orders o ON o.id = pp.order_id WHERE pp.id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            http_response_code(404);
            exit('Not found');
        }
        $role = Auth::role();
        if ((int)$row['owner_id'] !== Auth::id() && !in_array($role, ['admin', 'developer'], true)) {
            http_response_code(403);
            exit('Forbidden');
        }
        // Developers see metadata only, not the file (privacy)
        if ($role === 'developer') {
            http_response_code(403);
            exit('Forbidden');
        }
        $svc = UploadService::proofs($this->config);
        $path = $svc->path($row['filename']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('File hilang');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    public function saveAddress(array $p = []): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $v = new Validator($_POST);
        $v->required('recipient', 'Nama penerima')->required('phone', 'No HP')
            ->required('address_line', 'Alamat')->required('city_label', 'Kota')
            ->required('destination_id', 'Kota tujuan');
        if (!$v->passes()) {
            Helpers::flash('error', implode(' ', $v->errors()));
            Helpers::redirect('/checkout');
        }
        $pdo = Database::pdo();
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        if ($isDefault) {
            $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([Auth::id()]);
        }
        $pdo->prepare(
            'INSERT INTO addresses (user_id, label, recipient, phone, address_line, city_label, destination_id, postal_code, note, is_default, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            Auth::id(), trim($_POST['label'] ?? 'Rumah'), trim($_POST['recipient']), trim($_POST['phone']),
            trim($_POST['address_line']), trim($_POST['city_label']), trim($_POST['destination_id']),
            trim($_POST['postal_code'] ?? ''), trim($_POST['note'] ?? ''), $isDefault,
        ]);
        Helpers::flash('success', 'Alamat tersimpan.');
        Helpers::redirect('/checkout');
    }
}
