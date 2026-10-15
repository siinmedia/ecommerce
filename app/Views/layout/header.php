<?php
use App\Core\Csrf;
use App\Core\View;
$u = $user ?? null;
$isAdmin = $is_admin ?? false;
$isDev = $is_dev ?? false;
$isStaff = $isAdmin || $isDev;
$siteName = ($config['app']['name'] ?? 'Toko');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = function (string ...$prefixes) use ($path): string {
  foreach ($prefixes as $pre) {
    if ($pre === '/' ? $path === '/' : str_starts_with($path, $pre)) return ' is-active';
  }
  return '';
};
// Badge jumlah keranjang: pakai $cart bila ada, else sesi guest, else DB user.
$cartCount = 0;
if (!empty($cart['count'])) { $cartCount = (int)$cart['count']; }
elseif (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) { $cartCount = count($_SESSION['cart']); }
elseif ($u && class_exists(\App\Controllers\CartController::class)) {
  try { $cartCount = (int)\App\Controllers\CartController::lines()['count']; } catch (\Throwable) { $cartCount = 0; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= View::e(($title ?? $siteName) . ' - ' . $siteName) ?></title>
<meta name="description" content="<?= View::e($meta_desc ?? '') ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= View::e($canonical) ?>"><?php endif; ?>
<meta property="og:title" content="<?= View::e($title ?? $siteName) ?>">
<meta property="og:description" content="<?= View::e($meta_desc ?? '') ?>">
<meta property="og:type" content="website">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23002EE6'/%3E%3Ctext x='32' y='44' font-family='sans-serif' font-size='36' font-weight='700' fill='%23fff' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E">
<?php if ($isStaff): ?><script src="https://cdn.tailwindcss.com"></script><?php endif; ?>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a href="#main" class="skip">Lewati ke konten</a>
<header class="s-head">
  <div class="wrap s-head__bar">
    <?php if (!$isStaff): ?>
    <button class="icon-btn" id="drawer-open" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="drawer">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <?php endif; ?>
    <a href="/" class="brand" aria-label="<?= View::e($siteName) ?> - Beranda">
      <span class="brand__mark" aria-hidden="true"><?= View::e(mb_substr($siteName, 0, 1)) ?></span>
      <span class="brand__text"><span class="brand__name"><?= View::e($siteName) ?></span><span class="brand__tag">Belanja Aman</span></span>
    </a>
    <?php if (!$isStaff): ?>
    <form method="get" action="/search" role="search" class="shop-search" aria-label="Pencarian paket">
      <label class="sr" for="site-q">Cari paket kemitraan</label>
      <input class="input shop-search__input" type="search" id="site-q" name="q" placeholder="Cari paket kemitraan..." autocomplete="off" value="<?= View::e($_GET['q'] ?? '') ?>">
      <button class="btn btn--primary shop-search__btn" type="submit" aria-label="Cari">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span class="shop-search__txt">Cari</span>
      </button>
    </form>
    <a href="/cart" class="icon-btn" aria-label="Keranjang belanja<?= $cartCount > 0 ? ', ' . $cartCount . ' jenis barang' : '' ?>">
      <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 12.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6"/></svg>
      <?php if ($cartCount > 0): ?><span class="cart-badge" aria-hidden="true"><?= $cartCount > 99 ? '99+' : (int)$cartCount ?></span><?php endif; ?>
    </a>
    <?php endif; ?>
    <?php if ($isStaff): ?>
      <nav class="row" aria-label="Navigasi staf" style="gap:2px">
        <?php if ($isAdmin): ?>
          <a class="dnav__link<?= $active('/admin') ?>" href="/admin" style="display:block">Dashboard</a>
          <a class="dnav__link" href="/admin/products">Produk</a>
          <a class="dnav__link" href="/admin/orders">Pesanan</a>
          <a class="dnav__link" href="/admin/payments">Verifikasi</a>
          <a class="dnav__link" href="/admin/banks">Bank</a>
        <?php else: ?>
          <a class="dnav__link" href="/dev/health">Health</a>
          <a class="dnav__link" href="/dev/logs">Logs</a>
          <a class="dnav__link" href="/dev/jobs">Jobs</a>
        <?php endif; ?>
      </nav>
    <?php elseif ($u): ?>
      <a href="/account/orders" class="icon-btn head__account" aria-label="Akun saya">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
      </a>
    <?php else: ?>
      <a href="/login" class="btn btn--ghost btn--sm head__account">Masuk</a>
      <a href="/register" class="btn btn--primary btn--sm head__account">Daftar</a>
    <?php endif; ?>
  </div>
  <?php if (!$isStaff): ?>
  <nav class="dnav" aria-label="Navigasi utama">
    <div class="wrap">
      <div class="dnav__list">
        <a class="dnav__link<?= $active('/') ?>" href="/">Beranda</a>
        <a class="dnav__link<?= $active('/products', '/categories', '/search') ?>" href="/products">Katalog</a>
        <a class="dnav__link<?= $active('/cart', '/checkout', '/orders/') ?>" href="/cart">Keranjang</a>
        <?php if ($u): ?>
          <a class="dnav__link<?= $active('/account/') ?>" href="/account/orders">Pesanan Saya</a>
          <a class="dnav__link<?= $active('/account/addresses') ?>" href="/account/addresses">Alamat</a>
          <a class="dnav__link<?= $active('/account/profile', '/account/2fa') ?>" href="/account/profile">Akun</a>
        <?php else: ?>
          <a class="dnav__link<?= $active('/login') ?>" href="/login">Lacak Pesanan</a>
          <a class="dnav__link<?= $active('/register') ?>" href="/register">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
  <?php endif; ?>
</header>
<?php if (!$isStaff): ?>
<button class="scrim" id="scrim" type="button" aria-label="Tutup menu" tabindex="-1"></button>
<aside class="drawer" id="drawer" aria-label="Menu navigasi" aria-hidden="true">
  <div class="drawer__head">
    <span class="brand__mark" aria-hidden="true"><?= View::e(mb_substr($siteName, 0, 1)) ?></span>
    <span class="drawer__title"><?= View::e($siteName) ?></span>
    <button class="icon-btn" id="drawer-close" type="button" aria-label="Tutup menu navigasi">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
  </div>
  <nav class="drawer__nav">
    <p class="drawer__group">Belanja</p>
    <div class="drawer__list">
      <a class="drawer__link<?= $active('/') ?>" href="/"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>Beranda</a>
      <a class="drawer__link<?= $active('/products', '/categories', '/search') ?>" href="/products"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>Katalog</a>
      <a class="drawer__link<?= $active('/cart', '/checkout') ?>" href="/cart"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 12.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6"/></svg>Keranjang<?= $cartCount > 0 ? ' (' . (int)$cartCount . ')' : '' ?></a>
    </div>
    <p class="drawer__group">Bantuan</p>
    <div class="drawer__list">
      <a class="drawer__link<?= $active('/account/orders') ?>" href="<?= $u ? '/account/orders' : '/login' ?>"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>Lacak Pesanan</a>
      <a class="drawer__link" href="/products"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.4 2.3c-.8.3-.9 1-.9 1.7"/><circle cx="12" cy="17" r=".5" fill="currentColor"/></svg>Bantuan</a>
    </div>
    <p class="drawer__group">Akun</p>
    <div class="drawer__list">
      <?php if ($u): ?>
        <a class="drawer__link<?= $active('/account/profile', '/account/2fa') ?>" href="/account/profile"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg><?= View::e(mb_strimwidth($u['name'], 0, 20, '…')) ?></a>
        <a class="drawer__link<?= $active('/account/addresses') ?>" href="/account/addresses"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>Alamat Saya</a>
      <?php else: ?>
        <a class="drawer__link<?= $active('/login') ?>" href="/login"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>Masuk</a>
        <a class="drawer__link<?= $active('/register') ?>" href="/register"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Daftar Akun</a>
      <?php endif; ?>
    </div>
  </nav>
  <div class="drawer__foot">
    <?php if ($u): ?>
      <form method="post" action="/logout"><?= Csrf::field() ?><button class="btn btn--ghost btn--block" type="submit">Keluar (<?= View::e(mb_strimwidth($u['name'], 0, 16, '…')) ?>)</button></form>
    <?php else: ?>
      <a href="/register" class="btn btn--buy btn--block">Daftar Sekarang</a>
    <?php endif; ?>
  </div>
</aside>
<?php endif; ?>
<main id="main">
<?php if (!empty($flash_success)): ?><div class="wrap" style="padding-top:14px"><div class="flash flash-success" role="alert"><?= View::e($flash_success) ?></div></div><?php endif; ?>
<?php if (!empty($flash_error)): ?><div class="wrap" style="padding-top:14px"><div class="flash flash-error" role="alert"><?= View::e($flash_error) ?></div></div><?php endif; ?>
