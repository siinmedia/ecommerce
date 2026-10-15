<?php
use App\Core\View;
$isStaff = ($is_admin ?? false) || ($is_dev ?? false);
$siteName = ($config['app']['name'] ?? 'Toko');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$cartCount = 0;
if (!empty($cart['count'])) { $cartCount = (int)$cart['count']; }
elseif (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) { $cartCount = count($_SESSION['cart']); }
elseif (!empty($user) && class_exists(\App\Controllers\CartController::class)) {
  try { $cartCount = (int)\App\Controllers\CartController::lines()['count']; } catch (\Throwable) { $cartCount = 0; }
}
$bnActive = function (string ...$prefixes) use ($path): string {
  foreach ($prefixes as $pre) {
    if ($pre === '/' ? $path === '/' : str_starts_with($path, $pre)) return ' is-active';
  }
  return '';
};
?>
</main>
<footer class="foot">
  <div class="wrap">
    <div class="foot__grid">
      <div>
        <div class="foot__brand">
          <span class="foot__mark" aria-hidden="true"><?= View::e(mb_substr($siteName, 0, 1)) ?></span>
          <span class="foot__name"><?= View::e($siteName) ?></span>
        </div>
        <p class="foot__desc">Katalog paket kemitraan. Bayar via transfer bank, unggah bukti, pesanan diverifikasi admin.</p>
      </div>
      <details open>
        <summary>Belanja<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
        <div class="foot__links">
          <a href="/">Beranda</a>
          <a href="/products">Katalog Paket</a>
          <a href="/cart">Keranjang</a>
        </div>
      </details>
      <details>
        <summary>Akun<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
        <div class="foot__links">
          <a href="/account/orders">Pesanan Saya</a>
          <a href="/account/addresses">Alamat Saya</a>
          <a href="/login">Masuk</a>
        </div>
      </details>
      <details>
        <summary>Info Toko<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
        <div class="foot__contact">
          <span>Jam operasional: Senin&ndash;Sabtu 09.00&ndash;17.00 WIB.</span>
          <span>Pembayaran: transfer bank, verifikasi maks 1x24 jam.</span>
          <span>Pengiriman: JNE / POS / TIKI.</span>
        </div>
      </details>
    </div>
    <div class="foot__bottom">
      <span>&copy; <?= date('Y') ?> <?= View::e($siteName) ?></span>
      <span>Harga paket bersifat contoh</span>
    </div>
  </div>
</footer>
<?php if (!$isStaff): ?>
<nav class="bnav" aria-label="Navigasi bawah">
  <div class="bnav__list">
    <a class="bnav__link<?= $bnActive('/') ?>" href="/"<?= $path === '/' ? ' aria-current="page"' : '' ?>>
      <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
      Home
    </a>
    <a class="bnav__link<?= $bnActive('/products', '/categories', '/search') ?>" href="/products"<?= str_starts_with($path, '/products') ? ' aria-current="page"' : '' ?>>
      <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
      Katalog
    </a>
    <a class="bnav__link<?= $bnActive('/cart', '/checkout', '/orders/') ?>" href="/cart">
      <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 12.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6"/></svg>
      Cart
      <?php if (!empty($cartCount) && $cartCount > 0): ?><span class="bnav__badge" aria-hidden="true"><?= $cartCount > 99 ? '99+' : (int)$cartCount ?></span><?php endif; ?>
    </a>
    <a class="bnav__link<?= $bnActive('/account/', '/login', '/register') ?>" href="<?= !empty($user) ? '/account/orders' : '/login' ?>">
      <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
      Akun
    </a>
  </div>
</nav>
<?php endif; ?>
<div class="sr" role="status" aria-live="polite" id="live"></div>
<script src="/assets/js/app.js"></script>
</body>
</html>
