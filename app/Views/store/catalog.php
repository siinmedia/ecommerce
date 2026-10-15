<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-4">Katalog Produk</h1>
<form method="get" action="/products" class="card p-4 mb-4 grid md:grid-cols-5 gap-2">
  <input type="text" name="q" placeholder="Cari produk..." value="<?= View::e($filters['q'] ?? '') ?>" aria-label="Cari produk">
  <select name="category" aria-label="Kategori">
    <option value="">Semua kategori</option>
    <?php foreach (($categories ?? []) as $c): ?>
      <option value="<?= View::e($c['slug']) ?>" <?= (($filters['category'] ?? '') === $c['slug']) ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="sort" aria-label="Urutkan">
    <?php $s = $filters['sort'] ?? 'newest'; ?>
    <option value="newest" <?= $s === 'newest' ? 'selected' : '' ?>>Terbaru</option>
    <option value="price_asc" <?= $s === 'price_asc' ? 'selected' : '' ?>>Harga terendah</option>
    <option value="price_desc" <?= $s === 'price_desc' ? 'selected' : '' ?>>Harga tertinggi</option>
  </select>
  <input type="number" name="min" placeholder="Min Rp" value="<?= View::e($filters['min_price'] ?? '') ?>" aria-label="Harga minimum">
  <button class="btn-primary px-4 py-2 rounded" type="submit">Filter</button>
</form>
<?php if (!empty($filters['q']) && empty($products)): ?>
  <div class="card p-6 text-center">
    <p class="font-semibold">Tidak ada hasil untuk "<?= View::e($filters['q']) ?>"</p>
    <p class="text-sm text-gray-500">Coba kata kunci lain atau <a class="underline" href="/products">kembali ke katalog</a>.</p>
  </div>
<?php endif; ?>
<p class="text-sm text-gray-500 mb-2"><?= (int)($total ?? 0) ?> produk ditemukan.</p>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
  <?php foreach (($products ?? []) as $p): ?>
  <article class="card p-3">
    <a href="/products/<?= View::e($p['slug']) ?>">
      <h2 class="font-semibold"><?= View::e($p['name']) ?></h2>
      <p class="text-sm text-gray-500"><?= View::e($p['category_name'] ?? '') ?></p>
      <p class="font-bold text-blue-700 mt-1"><?= Helpers::rupiah($p['price']) ?></p>
    </a>
  </article>
  <?php endforeach; ?>
</div>
<?php
$totalPages = max(1, (int)ceil(($total ?? 0) / ($perPage ?? 12)));
if ($totalPages > 1):
  $qs = $_GET; ?>
<nav class="flex gap-2 mt-6" aria-label="Pagination">
  <?php for ($i = 1; $i <= $totalPages; $i++): $qs['page'] = $i; ?>
    <a class="px-3 py-1 rounded border <?= $i === ($page ?? 1) ? 'bg-blue-700 text-white' : 'bg-white' ?>" href="/products?<?= View::e(http_build_query($qs)) ?>"><?= (int)$i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
