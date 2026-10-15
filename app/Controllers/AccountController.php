<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\View;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Services\OrderService;
use App\Services\RealRajaOngkir;

class AccountController
{
    private array $config;

    public function __construct()
    {
        $this->config = require Database::configPath();
        Auth::requireLogin();
    }

    private function base(string $title): array
    {
        return [
            'title' => $title, 'user' => Auth::user(), 'config' => $this->config,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ];
    }

    public function orders(array $p = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        View::layout('account/orders', $this->base('Pesanan Saya') + [
            'orders' => Order::forUser(Auth::id(), $page),
            'page' => $page,
        ]);
    }

    public function orderDetail(array $p): void
    {
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Order tidak ditemukan']);
            return;
        }
        $shipment = Shipment::forOrder((int)$order['id']);
        $events = $shipment ? Shipment::trackingEvents((int)$shipment['id']) : [];
        View::layout('account/order_detail', $this->base('Order ' . $order['order_code']) + [
            'order' => $order,
            'items' => Order::items((int)$order['id']),
            'history' => Order::history((int)$order['id']),
            'shipment' => $shipment,
            'events' => $events,
            'reviews' => Payment::reviewsForOrder((int)$order['id']),
        ]);
    }

    public function reorder(array $p): void
    {
        Csrf::requireValid();
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            Helpers::flash('error', 'Order tidak ditemukan.');
            Helpers::redirect('/account/orders');
        }
        $added = 0;
        foreach (Order::items((int)$order['id']) as $it) {
            $v = \App\Models\Product::findVariant((int)$it['variant_id']);
            if (!$v || $v['status'] !== 'active') {
                continue;
            }
            $_POST = ['_csrf' => $_POST['_csrf'] ?? '', 'variant_id' => $v['id'], 'qty' => $it['qty']];
            // reuse cart add path via DB/session directly
            if (Auth::check()) {
                $pdo = Database::pdo();
                $st = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
                $st->execute([Auth::id()]);
                $cartId = $st->fetch()['id'] ?? null;
                if (!$cartId) {
                    $pdo->prepare('INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())')->execute([Auth::id()]);
                    $cartId = (int)$pdo->lastInsertId();
                }
                $pdo->prepare(
                    'INSERT INTO cart_items (cart_id, variant_id, qty) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE qty = LEAST(99, qty + VALUES(qty))'
                )->execute([$cartId, $v['id'], min(99, (int)$it['qty'])]);
                $added++;
            }
        }
        Helpers::flash($added ? 'success' : 'error', $added ? 'Item dimasukkan kembali ke keranjang.' : 'Tidak ada item yang bisa dibeli ulang.');
        Helpers::redirect('/cart');
    }

    public function track(array $p): void
    {
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Order tidak ditemukan']);
            return;
        }
        $shipment = Shipment::forOrder((int)$order['id']);
        if (!$shipment || empty($shipment['waybill'])) {
            Helpers::flash('error', 'Nomor resi belum tersedia.');
            Helpers::redirect('/account/orders/' . $order['order_code']);
        }
        try {
            $raja = RealRajaOngkir::make($this->config['rajaongkir']);
            $live = $raja->track($shipment['waybill'], $shipment['courier']);
            // Persist live events (idempotent by time+desc)
            $pdo = Database::pdo();
            foreach ($live['events'] as $ev) {
                $chk = $pdo->prepare('SELECT id FROM tracking_events WHERE shipment_id = ? AND event_time = ? AND description = ? LIMIT 1');
                $chk->execute([$shipment['id'], $ev['time'], $ev['desc']]);
                if (!$chk->fetch()) {
                    $pdo->prepare(
                        'INSERT INTO tracking_events (shipment_id, status, description, event_time, source, created_at)
                         VALUES (?, ?, ?, ?, "rajaongkir", NOW())'
                    )->execute([$shipment['id'], $live['status'], $ev['desc'], $ev['time']]);
                }
            }
            Helpers::flash('success', 'Status pelacakan diperbarui.');
        } catch (\Throwable $e) {
            Helpers::flash('error', 'Pelacakan gagal: ' . $e->getMessage());
        }
        Helpers::redirect('/account/orders/' . $order['order_code']);
    }

    public function cancel(array $p): void
    {
        Csrf::requireValid();
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            Helpers::flash('error', 'Order tidak ditemukan.');
            Helpers::redirect('/account/orders');
        }
        try {
            (new OrderService())->transition((int)$order['id'], 'CANCELLED', Auth::id(), 'user', 'Dibatalkan user');
            Helpers::flash('success', 'Order dibatalkan.');
        } catch (\Throwable $e) {
            Helpers::flash('error', $e->getMessage());
        }
        Helpers::redirect('/account/orders/' . $order['order_code']);
    }

    public function addresses(array $p = []): void
    {
        $st = Database::pdo()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
        $st->execute([Auth::id()]);
        View::layout('account/addresses', $this->base('Alamat Saya') + ['addresses' => $st->fetchAll()]);
    }

    public function profile(array $p = []): void
    {
        View::layout('account/profile', $this->base('Profil') + ['profile' => \App\Models\User::find(Auth::id())]);
    }

    public function updateProfile(array $p = []): void
    {
        Csrf::requireValid();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '') {
            Helpers::flash('error', 'Nama wajib diisi.');
            Helpers::redirect('/account/profile');
        }
        Database::pdo()->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, Auth::id()]);
        $_SESSION['user']['name'] = $name;
        \App\Models\Audit::log(Auth::id(), 'user', 'profile.update', 'users', Auth::id());
        Helpers::flash('success', 'Profil diperbarui.');
        Helpers::redirect('/account/profile');
    }

    // ================= 2FA (wajib untuk admin/developer, opsional untuk user) =================

    public function twoFa(array $p = []): void
    {
        $me = \App\Models\User::find(Auth::id());
        $data = $this->base('Keamanan 2FA') + [
            'totp_enabled' => (int)($me['totp_enabled'] ?? 0) === 1,
            'backup_left' => \App\Models\User::countUnusedBackupCodes(Auth::id()),
            'must_enable' => in_array(Auth::role(), ['admin', 'developer'], true),
        ];
        if (!$data['totp_enabled']) {
            // Siapkan secret sementara untuk enroll (regenerasi tiap kunjungan).
            $secret = \App\Core\Totp::generateSecret();
            $_SESSION['totp_enroll_secret'] = $secret;
            $data['secret'] = $secret;
            $data['otpauth_uri'] = \App\Core\Totp::provisioningUri($secret, $me['email']);
        }
        View::layout('account/twofa', $data);
    }

    public function enableTwoFa(array $p = []): void
    {
        Csrf::requireValid();
        $me = \App\Models\User::find(Auth::id());
        $secret = $_SESSION['totp_enroll_secret'] ?? null;
        if (!$secret || (int)($me['totp_enabled'] ?? 0) === 1) {
            Helpers::flash('error', 'Sesi enroll kedaluwarsa. Muat ulang halaman.');
            Helpers::redirect('/account/2fa');
        }
        $code = trim($_POST['code'] ?? '');
        if (!\App\Core\Totp::verify($secret, $code)) {
            Helpers::flash('error', 'Kode verifikasi salah. Coba lagi.');
            Helpers::redirect('/account/2fa');
        }
        \App\Models\User::setTotpSecret(Auth::id(), $secret);
        \App\Models\User::enableTotp(Auth::id());
        $codes = \App\Core\Totp::generateBackupCodes(10);
        \App\Models\User::storeBackupCodes(Auth::id(), array_map([\App\Core\Totp::class, 'hashBackupCode'], $codes));
        unset($_SESSION['totp_enroll_secret']);
        \App\Models\Audit::log(Auth::id(), Auth::role(), 'auth.2fa_enable', 'users', Auth::id());
        // Tampilkan backup code SEKALI (render langsung, tidak disimpan plain di mana pun).
        View::layout('account/twofa', $this->base('Keamanan 2FA') + [
            'totp_enabled' => true,
            'just_enabled' => true,
            'backup_codes' => $codes,
            'backup_left' => 10,
            'must_enable' => in_array(Auth::role(), ['admin', 'developer'], true),
        ]);
    }

    public function disableTwoFa(array $p = []): void
    {
        Csrf::requireValid();
        $me = \App\Models\User::find(Auth::id());
        $pass = $_POST['password'] ?? '';
        if (!$me || !password_verify($pass, $me['password_hash'])) {
            Helpers::flash('error', 'Password salah.');
            Helpers::redirect('/account/2fa');
        }
        \App\Models\User::disableTotp(Auth::id());
        \App\Models\Audit::log(Auth::id(), Auth::role(), 'auth.2fa_disable', 'users', Auth::id());
        Helpers::flash('success', '2FA dinonaktifkan.');
        Helpers::redirect('/account/2fa');
    }
}
