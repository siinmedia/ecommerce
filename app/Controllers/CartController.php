<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\View;
use App\Models\Product;

class CartController
{
    /** Guest cart lives in session; logged-in user cart lives in DB (carts/cart_items). */
    public static function sessionCart(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    public static function saveSessionCart(array $cart): void
    {
        $_SESSION['cart'] = $cart;
    }

    /** Merge guest session cart into DB cart after login. */
    public static function mergeAfterLogin(int $userId): void
    {
        $guest = self::sessionCart();
        if ($guest === []) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $cartId = self::userCartId($pdo, $userId);
            foreach ($guest as $variantId => $qty) {
                $st = $pdo->prepare(
                    'INSERT INTO cart_items (cart_id, variant_id, qty) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE qty = LEAST(99, qty + VALUES(qty))'
                );
                $st->execute([$cartId, (int)$variantId, (int)$qty]);
            }
            $pdo->commit();
            unset($_SESSION['cart']);
        } catch (\Throwable) {
            $pdo->rollBack();
        }
    }

    private static function userCartId(\PDO $pdo, int $userId): int
    {
        $st = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        if ($row = $st->fetch()) {
            return (int)$row['id'];
        }
        $pdo->prepare('INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())')->execute([$userId]);
        return (int)$pdo->lastInsertId();
    }

    /** Returns normalized cart lines with server-side price/stock. */
    public static function lines(): array
    {
        $raw = [];
        if (Auth::check()) {
            $st = Database::pdo()->prepare(
                'SELECT ci.variant_id, ci.qty FROM cart_items ci
                 JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?'
            );
            $st->execute([Auth::id()]);
            foreach ($st->fetchAll() as $r) {
                $raw[(int)$r['variant_id']] = (int)$r['qty'];
            }
        } else {
            $raw = self::sessionCart();
        }
        $lines = [];
        $subtotal = 0;
        foreach ($raw as $variantId => $qty) {
            $v = Product::findVariant((int)$variantId);
            if (!$v || $v['status'] !== 'active') {
                continue;
            }
            $p = Product::find((int)$v['product_id']);
            if (!$p || $p['status'] !== 'published') {
                continue;
            }
            $qty = max(1, min(99, (int)$qty));
            $lineTotal = (int)$v['price'] * $qty;
            $subtotal += $lineTotal;
            $lines[] = [
                'variant_id' => (int)$v['id'], 'qty' => $qty,
                'price' => (int)$v['price'], 'line_total' => $lineTotal,
                'stock' => (int)$v['stock'], 'variant_name' => $v['name'],
                'sku' => $v['sku'], 'product_name' => $p['name'],
                'product_slug' => $p['slug'], 'over_stock' => $qty > (int)$v['stock'],
            ];
        }
        return ['lines' => $lines, 'subtotal' => $subtotal, 'count' => count($lines)];
    }

    public function show(array $p = []): void
    {
        $cart = self::lines();
        $config = require Database::configPath();
        View::layout('store/cart', [
            'title' => 'Keranjang Belanja', 'user' => Auth::user(), 'config' => $config,
            'cart' => $cart,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ]);
    }

    public function add(array $p = []): void
    {
        Csrf::requireValid();
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $qty = max(1, min(99, (int)($_POST['qty'] ?? 1)));
        $v = Product::findVariant($variantId);
        if (!$v || $v['status'] !== 'active') {
            Helpers::flash('error', 'Varian tidak tersedia.');
            Helpers::back('/products');
        }
        $prod = Product::find((int)$v['product_id']);
        if (!$prod || $prod['status'] !== 'published') {
            Helpers::flash('error', 'Produk tidak tersedia.');
            Helpers::back('/products');
        }
        if ((int)$v['stock'] < $qty) {
            Helpers::flash('error', 'Stok tidak mencukupi (sisa ' . $v['stock'] . ').');
            Helpers::back('/products/' . $prod['slug']);
        }
        if (Auth::check()) {
            $pdo = Database::pdo();
            $cartId = self::userCartId($pdo, Auth::id());
            $st = $pdo->prepare(
                'INSERT INTO cart_items (cart_id, variant_id, qty) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE qty = LEAST(99, qty + VALUES(qty))'
            );
            $st->execute([$cartId, $variantId, $qty]);
        } else {
            $cart = self::sessionCart();
            $cart[$variantId] = min(99, ($cart[$variantId] ?? 0) + $qty);
            self::saveSessionCart($cart);
        }
        Helpers::flash('success', 'Ditambahkan ke keranjang.');
        Helpers::redirect('/cart');
    }

    public function update(array $p = []): void
    {
        Csrf::requireValid();
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $qty = max(0, min(99, (int)($_POST['qty'] ?? 0)));
        if (Auth::check()) {
            $pdo = Database::pdo();
            $cartId = self::userCartId($pdo, Auth::id());
            if ($qty === 0) {
                $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND variant_id = ?')->execute([$cartId, $variantId]);
            } else {
                $pdo->prepare('UPDATE cart_items SET qty = ? WHERE cart_id = ? AND variant_id = ?')->execute([$qty, $cartId, $variantId]);
            }
        } else {
            $cart = self::sessionCart();
            if ($qty === 0) {
                unset($cart[$variantId]);
            } else {
                $cart[$variantId] = $qty;
            }
            self::saveSessionCart($cart);
        }
        Helpers::redirect('/cart');
    }

    public function clear(array $p = []): void
    {
        Csrf::requireValid();
        if (Auth::check()) {
            $pdo = Database::pdo();
            $cartId = self::userCartId($pdo, Auth::id());
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
        } else {
            unset($_SESSION['cart']);
        }
        Helpers::redirect('/cart');
    }

    /** Clear purchased items after successful order. */
    public static function clearPurchased(int $userId): void
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        if ($row = $st->fetch()) {
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$row['id']]);
        }
    }
}
