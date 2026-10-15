<?php
use App\Core\Csrf;
use App\Core\View;
$u = $user ?? null;
$isAdmin = $is_admin ?? false;
$isDev = $is_dev ?? false;
$isStaff = $isAdmin || $isDev;
$siteName = ($config['app']['name'] ?? 'TokoMVP');
$cssVersion = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1';
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
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23D92727'/%3E%3Ctext x='32' y='44' font-family='sans-serif' font-size='36' font-weight='700' fill='%23FFC928' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E">
<?php if ($isStaff): ?><script src="https://cdn.tailwindcss.com"></script><?php endif; ?>
<link rel="stylesheet" href="/assets/css/app.css?v=<?= (int)$cssVersion ?>">
</head>
<body<?= $cartCount > 0 && !$isStaff ? ' class="has-cart"' : '' ?>>
<a href="#main" class="skip">Lewati ke konten</a>
<header class="s-head">
  <div class="wrap s-head__bar">
    <a href="/" class="brand" aria-label="<?= View::e($siteName) ?> - Beranda">
      <span class="brand__mark" aria-hidden="true"><?= View::e(mb_substr($siteName, 0, 1)) ?></span>
      <span class="brand__text"><span class="brand__name"><?= View::e($siteName) ?></span><span class="brand__tag">Pilih paket &middot; pesan mudah</span></span>
    </a>
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
    <?php else: ?>
      <?php if ($u): ?>
        <a href="/account/orders" class="icon-btn" aria-label="Akun saya">
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
        </a>
      <?php else: ?>
        <a href="/login" class="head__login">Masuk</a>
      <?php endif; ?>
      <a href="/cart" class="icon-btn" aria-label="Keranjang belanja<?= $cartCount > 0 ? ', ' . $cartCount . ' jenis barang' : '' ?>">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 12.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6"/></svg>
        <?php if ($cartCount > 0): ?><span class="cart-badge" aria-hidden="true"><?= $cartCount > 99 ? '99+' : (int)$cartCount ?></span><?php endif; ?>
      </a>
    <?php endif; ?>
  </div>
  <?php if (!$isStaff): ?>
  <div class="menu-bar">
    <div class="wrap menu-bar__row">
      <form method="get" action="/search" role="search" class="menu-search" aria-label="Pencarian paket">
        <svg class="menu-search__ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <label class="sr" for="site-q">Cari paket kemitraan</label>
        <input type="search" id="site-q" name="q" placeholder="Cari paket..." autocomplete="off" value="<?= View::e($_GET['q'] ?? '') ?>">
        <button type="submit" aria-label="Cari">Cari</button>
      </form>
      <a class="menu-bar__cat" href="/products">Katalog</a>
    </div>
  </div>
  <?php endif; ?>
</header>
<main id="main">
<?php if (!empty($flash_success)): ?><div class="wrap" style="padding-top:14px"><div class="flash flash-success" role="alert"><?= View::e($flash_success) ?></div></div><?php endif; ?>
<?php if (!empty($flash_error)): ?><div class="wrap" style="padding-top:14px"><div class="flash flash-error" role="alert"><?= View::e($flash_error) ?></div></div><?php endif; ?>
