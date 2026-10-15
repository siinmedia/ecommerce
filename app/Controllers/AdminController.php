<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\Validator;
use App\Core\View;
use App\Models\Audit;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\PaymentService;

class AdminController
{
    private array $config;

    public function __construct()
    {
        $this->config = require Database::configPath();
        Auth::requireRole(['admin']);
    }

    private function base(string $title): array
    {
        return [
            'title' => $title, 'user' => Auth::user(), 'config' => $this->config,
            'is_admin' => true,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ];
    }

    public function dashboard(array $p = []): void
    {
        View::layout('admin/dashboard', $this->base('Admin Dashboard') + [
            'stats' => Order::dashboardStats(),
            'recent' => Order::adminList([], 1, 10),
        ]);
    }

    // ---- Products ----
    public function products(array $p = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        View::layout('admin/products', $this->base('Produk') + [
            'products' => Product::adminList($page),
            'page' => $page,
        ]);
    }

    public function productForm(array $p): void
    {
        $id = (int)($p['id'] ?? 0);
        $product = $id ? Product::find($id) : null;
        $variants = $id ? Product::variants($id) : [];
        View::layout('admin/product_form', $this->base($id ? 'Edit Produk' : 'Tambah Produk') + [
            'product' => $product,
            'variants' => $variants,
            'categories' => Product::categories(),
        ]);
    }

    public function productSave(array $p): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'sku' => trim($_POST['sku'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
            'compare_price' => trim($_POST['compare_price'] ?? ''),
            'weight_gram' => trim($_POST['weight_gram'] ?? '0'),
            'status' => in_array($_POST['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_POST['status'] : 'draft',
        ];
        if ($d['slug'] === '') {
            $d['slug'] = Validator::slug($d['name']);
        }
        $v = new Validator($d);
        $v->required('name', 'Nama')->required('slug', 'Slug')->required('sku', 'SKU')
            ->required('price', 'Harga')->numeric('price', 'Harga')->min('price', 0, 'Harga');
        if (!$v->passes()) {
            Helpers::flash('error', implode(' ', $v->errors()));
            Helpers::redirect($id ? "/admin/products/{$id}/edit" : '/admin/products/new');
        }
        try {
            if ($id) {
                $before = Product::find($id);
                Product::update($id, $d);
                Audit::log(Auth::id(), 'admin', 'product.update', 'products', $id, ['price' => $before['price'] ?? null, 'status' => $before['status'] ?? null], ['price' => $d['price'], 'status' => $d['status']]);
            } else {
                $id = Product::create($d);
                Audit::log(Auth::id(), 'admin', 'product.create', 'products', $id, null, ['sku' => $d['sku']]);
            }
            // Variants (arrays: vid[], vname[], vsku[], vprice[], vstock[], vweight[])
            $vids = $_POST['vid'] ?? [];
            foreach (($_POST['vname'] ?? []) as $i => $vn) {
                $vn = trim($vn);
                if ($vn === '') {
                    continue;
                }
                Product::upsertVariant($id, [
                    'id' => (int)($vids[$i] ?? 0),
                    'name' => $vn,
                    'sku' => trim($_POST['vsku'][$i] ?? ''),
                    'price' => trim($_POST['vprice'][$i] ?? $d['price']),
                    'stock' => trim($_POST['vstock'][$i] ?? '0'),
                    'weight_gram' => trim($_POST['vweight'][$i] ?? $d['weight_gram']),
                ]);
            }
            Helpers::flash('success', 'Produk tersimpan.');
            Helpers::redirect('/admin/products');
        } catch (\Throwable $e) {
            Helpers::flash('error', 'Gagal menyimpan: ' . $e->getMessage());
            Helpers::redirect($id ? "/admin/products/{$id}/edit" : '/admin/products/new');
        }
    }

    public function adjustStock(array $p): void
    {
        Csrf::requireValid();
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '' || $delta === 0) {
            Helpers::flash('error', 'Delta dan alasan wajib diisi.');
            Helpers::back('/admin/products');
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM product_variants WHERE id = ? FOR UPDATE');
            $st->execute([$variantId]);
            $vr = $st->fetch();
            if (!$vr) {
                throw new \RuntimeException('Varian tidak ditemukan.');
            }
            $before = (int)$vr['stock'];
            $after = $before + $delta;
            if ($after < 0) {
                throw new \RuntimeException('Stok tidak boleh negatif.');
            }
            $pdo->prepare('UPDATE product_variants SET stock = ? WHERE id = ?')->execute([$after, $variantId]);
            $pdo->prepare(
                'INSERT INTO stock_ledger (variant_id, delta, before_stock, after_stock, reason, actor_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            )->execute([$variantId, $delta, $before, $after, $reason, Auth::id()]);
            $pdo->commit();
            Audit::log(Auth::id(), 'admin', 'stock.adjust', 'product_variants', $variantId, ['stock' => $before], ['stock' => $after, 'reason' => $reason]);
            Helpers::flash('success', "Stok disesuaikan: {$before} -> {$after}.");
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Helpers::flash('error', $e->getMessage());
        }
        Helpers::back('/admin/products');
    }

    // ---- Orders ----
    public function orders(array $p = []): void
    {
        $filters = ['status' => trim($_GET['status'] ?? ''), 'q' => trim($_GET['q'] ?? '')];
        $page = max(1, (int)($_GET['page'] ?? 1));
        View::layout('admin/orders', $this->base('Pesanan') + [
            'orders' => Order::adminList($filters, $page),
            'filters' => $filters, 'page' => $page,
        ]);
    }

    public function orderDetail(array $p): void
    {
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Order tidak ditemukan']);
            return;
        }
        $pdo = Database::pdo();
        $proofs = Payment::proofsForOrder((int)$order['id']);
        $reviews = Payment::reviewsForOrder((int)$order['id']);
        $shipment = \App\Models\Shipment::forOrder((int)$order['id']);
        $addr = $pdo->query('SELECT * FROM order_addresses WHERE order_id = ' . (int)$order['id'])->fetch();
        View::layout('admin/order_detail', $this->base('Order ' . $order['order_code']) + [
            'order' => $order,
            'items' => Order::items((int)$order['id']),
            'history' => Order::history((int)$order['id']),
            'proofs' => $proofs, 'reviews' => $reviews,
            'shipment' => $shipment, 'address' => $addr,
            'transitions' => Order::TRANSITIONS[$order['status']] ?? [],
        ]);
    }

    // ---- Payment verification queue ----
    public function payments(array $p = []): void
    {
        View::layout('admin/verify', $this->base('Verifikasi Pembayaran') + [
            'orders' => Payment::reviewQueue(),
        ]);
    }

    public function verifyPayment(array $p): void
    {
        Csrf::requireValid();
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order) {
            Helpers::flash('error', 'Order tidak ditemukan.');
            Helpers::redirect('/admin/payments');
        }
        try {
            (new PaymentService())->verify(
                (int)$order['id'], Auth::id(),
                $_POST['decision'] ?? '', trim($_POST['note'] ?? '')
            );
            Helpers::flash('success', 'Keputusan tersimpan.');
        } catch (\Throwable $e) {
            Helpers::flash('error', $e->getMessage());
        }
        Helpers::redirect('/admin/orders/' . $order['order_code']);
    }

    public function transition(array $p): void
    {
        Csrf::requireValid();
        $order = Order::findByCode($p['code'] ?? '');
        if (!$order) {
            Helpers::flash('error', 'Order tidak ditemukan.');
            Helpers::redirect('/admin/orders');
        }
        $to = trim($_POST['to'] ?? '');
        try {
            // Waybill input on SHIPPED transition
            if ($to === 'SHIPPED') {
                $waybill = trim($_POST['waybill'] ?? '');
                if ($waybill === '') {
                    throw new \RuntimeException('Nomor resi wajib diisi untuk SHIPPED.');
                }
                $pdo = Database::pdo();
                $pdo->prepare('UPDATE shipments SET waybill = ?, status = "shipped", shipped_at = NOW() WHERE order_id = ?')
                    ->execute([$waybill, (int)$order['id']]);
                $pdo->prepare(
                    'INSERT INTO tracking_events (shipment_id, status, description, event_time, source, created_at)
                     VALUES ((SELECT id FROM shipments WHERE order_id = ?), "SHIPPED", ?, NOW(), "admin", NOW())'
                )->execute([(int)$order['id'], 'Resi ' . $waybill . ' diinput admin']);
            }
            (new OrderService())->transition((int)$order['id'], $to, Auth::id(), 'admin', trim($_POST['note'] ?? ''));
            Helpers::flash('success', "Status -> {$to}.");
        } catch (\Throwable $e) {
            Helpers::flash('error', $e->getMessage());
        }
        Helpers::redirect('/admin/orders/' . $order['order_code']);
    }

    // ---- Banks ----
    public function banks(array $p = []): void
    {
        View::layout('admin/banks', $this->base('Rekening Bank') + ['banks' => Payment::banks(false)]);
    }

    public function bankSave(array $p = []): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $pdo = Database::pdo();
        $d = [
            'bank' => trim($_POST['bank_name'] ?? ''),
            'number' => trim($_POST['account_number'] ?? ''),
            'name' => trim($_POST['account_name'] ?? ''),
            'active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($d['bank'] === '' || $d['number'] === '' || $d['name'] === '') {
            Helpers::flash('error', 'Bank, nomor, dan nama pemilik wajib diisi.');
            Helpers::redirect('/admin/banks');
        }
        if ($id) {
            $before = $pdo->query('SELECT * FROM bank_accounts WHERE id = ' . $id)->fetch();
            $pdo->prepare('UPDATE bank_accounts SET bank_name = ?, account_number = ?, account_name = ?, is_active = ? WHERE id = ?')
                ->execute([$d['bank'], $d['number'], $d['name'], $d['active'], $id]);
            Audit::log(Auth::id(), 'admin', 'bank.update', 'bank_accounts', $id, $before, $d);
        } else {
            $pdo->prepare('INSERT INTO bank_accounts (bank_name, account_number, account_name, is_active, sort_order) VALUES (?, ?, ?, ?, 0)')
                ->execute([$d['bank'], $d['number'], $d['name'], $d['active']]);
            Audit::log(Auth::id(), 'admin', 'bank.create', 'bank_accounts', (int)$pdo->lastInsertId(), null, $d);
        }
        Helpers::flash('success', 'Rekening tersimpan.');
        Helpers::redirect('/admin/banks');
    }

    public function auditLogs(array $p = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        View::layout('admin/audit', $this->base('Audit Log') + ['logs' => Audit::list($page), 'page' => $page]);
    }
}
