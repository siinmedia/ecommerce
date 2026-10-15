<?php use App\Core\View; ?>
<div class="card p-4 mb-4 flex flex-wrap gap-2 items-center">
  <?php foreach (($products ?? []) as $prod): ?>
  <?php endforeach; ?>
  <div class="w-full"><h1 class="text-2xl font-bold mb-1"><?= View::e($title ?? 'Belanja') ?></h1>
  <p class="text-gray-600">Produk pilihan, bayar via transfer bank, pengiriman seluruh Indonesia.</p></div>
</div>
<?php if (!empty($categories)): ?>
<nav class="flex flex-wrap gap-2 mb-4" aria-label="Kategori">
  <?php foreach ($categories as $c): ?>
    <a class="badge badge-neutral" href="/categories/<?= View::e($c['slug']) ?>"><?= View::e($c['name']) ?></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
  <?php foreach (($products ?? []) as $p): ?>
  <article class="card p-3">
    <a href="/products/<?= View::e($p['slug']) ?>" class="block">
      <h2 class="font-semibold"><?= View::e($p['name']) ?></h2>
      <p class="text-sm text-gray-500"><?= View::e($p['category_name'] ?? '') ?></p>
      <p class="font-bold text-blue-700 mt-1"><?= \App\Core\Helpers::rupiah($p['price']) ?></p>
    </a>
  </article>
  <?php endforeach; ?>
</div>
<?php if (empty($products)): ?><p class="text-gray-500 mt-4">Belum ada produk.</p><?php endif; ?>
<div class="mt-6"><a href="/products" class="btn-primary px-4 py-2 rounded inline-block">Lihat Semua Produk</a></div>
