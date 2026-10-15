<?php
use App\Core\Csrf;
use App\Core\View;
$u = $user ?? null;
$isAdmin = $is_admin ?? false;
$isDev = $is_dev ?? false;
$siteName = ($config['app']['name'] ?? 'TokoMVP');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= View::e(($title ?? $siteName) . ' - ' . $siteName) ?></title>
<meta name="description" content="<?= View::e($meta_desc ?? '') ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= View::e($canonical) ?>"><?php endif; ?>
<meta property="og:title" content="<?= View::e($title ?? $siteName) ?>">
<meta property="og:description" content="<?= View::e($meta_desc ?? '') ?>">
<meta property="og:type" content="website">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-2 focus:bg-white">Lewati ke konten</a>
<header class="bg-white border-b sticky top-0 z-10">
  <nav class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-4" aria-label="Navigasi utama">
    <a href="/" class="font-bold text-lg text-blue-700"><?= View::e($siteName) ?></a>
    <?php if ($isAdmin): ?>
      <a href="/admin" class="text-sm">Dashboard</a>
      <a href="/admin/products" class="text-sm">Produk</a>
      <a href="/admin/orders" class="text-sm">Pesanan</a>
      <a href="/admin/payments" class="text-sm">Verifikasi</a>
      <a href="/admin/banks" class="text-sm">Bank</a>
      <a href="/admin/audit-logs" class="text-sm">Audit</a>
    <?php elseif ($isDev): ?>
      <a href="/dev/health" class="text-sm">Health</a>
      <a href="/dev/logs" class="text-sm">Logs</a>
      <a href="/dev/jobs" class="text-sm">Jobs</a>
      <a href="/dev/audit-logs" class="text-sm">Audit</a>
    <?php else: ?>
      <a href="/products" class="text-sm">Katalog</a>
      <a href="/cart" class="text-sm">Keranjang</a>
      <?php if ($u): ?>
        <a href="/account/orders" class="text-sm">Pesanan Saya</a>
        <a href="/account/addresses" class="text-sm">Alamat</a>
      <?php endif; ?>
    <?php endif; ?>
    <span class="flex-1"></span>
    <?php if ($u): ?>
      <span class="text-sm text-gray-600"><?= View::e($u['name']) ?> (<?= View::e($u['role']) ?>)</span>
      <form method="post" action="/logout" class="inline"><?= Csrf::field() ?><button class="text-sm text-red-600" type="submit">Keluar</button></form>
    <?php else: ?>
      <a href="/login" class="text-sm">Masuk</a>
      <a href="/register" class="text-sm bg-blue-700 text-white px-3 py-1 rounded">Daftar</a>
    <?php endif; ?>
  </nav>
</header>
<main id="main" class="max-w-6xl mx-auto px-4 py-6 flex-1 w-full">
<?php if (!empty($flash_success)): ?><div class="flash flash-success" role="alert"><?= View::e($flash_success) ?></div><?php endif; ?>
<?php if (!empty($flash_error)): ?><div class="flash flash-error" role="alert"><?= View::e($flash_error) ?></div><?php endif; ?>
