<?php
use App\Core\View;
// Beranda = katalog langsung. Data: $products (terbaru), $categories.
// Peta varian pertama + total stok per produk (tanpa ubah controller).
$map = [];
try {
  $ids = array_values(array_filter(array_map(fn($p) => (int)$p['id'], $products ?? [])));
  if ($ids) {
    $in = implode(',', $ids);
    $st = \App\Core\Database::pdo()->query(
      "SELECT product_id, MIN(id) AS vid, COALESCE(SUM(stock),0) AS stock FROM product_variants WHERE product_id IN ({$in}) AND status = 'active' GROUP BY product_id"
    );
    foreach ($st->fetchAll() as $r) { $map[(int)$r['product_id']] = ['vid' => (int)$r['vid'], 'stock' => (int)$r['stock']]; }
  }
} catch (\Throwable) { $map = []; }
// Urutan strip kategori tetap: Semua + 7 kategori kemitraan.
$catOrder = ['mie', 'kopi', 'teh', 'camilan', 'ayam', 'roti-dessert', 'minuman'];
$cats = array_values($categories ?? []);
usort($cats, fn($a, $b) => (array_search($a['slug'], $catOrder) <=> array_search($b['slug'], $catOrder)));
$catIcon = [
  'mie' => '<path d="M4 12h16a8 8 0 0 1-16 0z"/><path d="M8 12V6m3 6V5m3 7V6m3 6v-3"/>',
  'kopi' => '<path d="M5 9h11v6a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5z"/><path d="M16 10h2a2.5 2.5 0 0 1 0 5h-2"/>',
  'teh' => '<path d="M7 9h10l-1.5 10h-7z"/><path d="M9 9c0-3 6-3 6 0"/>',
  'camilan' => '<path d="M6 9h12l-1.5 9h-9z"/><path d="M9 5l6 0M12 5V3"/>',
  'ayam' => '<circle cx="10" cy="14" r="5"/><path d="M14 11l5-5m-2 0h3v3"/>',
  'roti-dessert' => '<path d="M5 13a4 4 0 0 1 4-4h8a4 4 0 0 1 0 8H9a4 4 0 0 1-4-4z"/>',
  'minuman' => '<path d="M10 3h4v4l2 4v8a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-8l2-4z"/><path d="M10 12h4"/>',
];
?>
<div class="wrap page shop">
  <div class="shop-head">
    <div>
      <h1 class="shop-title">Katalog Paket Kemitraan</h1>
      <p class="muted shop-sub">Pilih brand, lihat paket, tambah ke keranjang.</p>
    </div>
    <a href="/products" class="btn btn--ghost btn--sm shop-all">Semua Paket</a>
  </div>

  <nav class="catstrip" aria-label="Kategori paket">
    <a class="catchip is-active" href="/products" aria-current="page">
      <span class="catchip__icon" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span>
      <span>Semua</span>
    </a>
    <?php foreach ($cats as $c): ?>
      <a class="catchip" href="/products?category=<?= View::e($c['slug']) ?>">
        <span class="catchip__icon" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $catIcon[$c['slug']] ?? '<circle cx="12" cy="12" r="8"/>' ?></svg></span>
        <span><?= View::e($c['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sortbar" role="navigation" aria-label="Urutkan">
    <span class="sortbar__label">Urutkan</span>
    <a href="/products?sort=newest">Terbaru</a>
    <a href="/products?sort=price_asc">Termurah</a>
    <a href="/products?sort=price_desc">Termahal</a>
  </div>

  <?php if (!empty($products)): ?>
    <p class="result-count" role="status">Menampilkan <?= count($products) ?> paket terbaru.</p>
    <div class="grid grid--prods">
      <?php foreach ($products as $p):
        $m = $map[(int)$p['id']] ?? ['vid' => null, 'stock' => 0];
        echo View::render('store/_card', ['p' => $p, 'vid' => $m['vid'], 'stock' => $m['stock']]);
      endforeach; ?>
    </div>
    <div class="center mt-4"><a href="/products" class="btn btn--primary">Lihat Semua Paket</a></div>
  <?php else: ?>
    <div class="empty"><h2>Belum ada paket</h2><p class="muted">Stok sedang disiapkan. Kembali lagi nanti.</p></div>
  <?php endif; ?>
</div>
