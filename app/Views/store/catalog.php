<?php
use App\Core\View;
// Katalog: filter q + category + min/max + sort (semua didukung StoreController::catalog).
// Peta SEMUA varian aktif per produk untuk pill varian di bottom sheet quick-view.
$vmap = [];
try {
  $ids = array_values(array_filter(array_map(fn($p) => (int)$p['id'], $products ?? [])));
  if ($ids) {
    $in = implode(',', $ids);
    $st = \App\Core\Database::pdo()->query(
      "SELECT id, product_id, name, price, stock FROM product_variants WHERE product_id IN ({$in}) AND status = 'active' ORDER BY id"
    );
    foreach ($st->fetchAll() as $r) { $vmap[(int)$r['product_id']][] = $r; }
  }
} catch (\Throwable) { $vmap = []; }
$f = $filters ?? ['q' => '', 'category' => '', 'min_price' => '', 'max_price' => '', 'sort' => 'newest'];
$sort = $f['sort'] ?? 'newest';
if (!in_array($sort, ['newest', 'price_asc', 'price_desc'], true)) { $sort = 'newest'; }
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
  'minuman' => '<path d="M10 3h4v4l2 4v8a2 2 0 0 1-2 2h-4z"/><path d="M10 12h4"/>',
];
$chipQs = function (string $cat) use ($f): string {
  $q = ['q' => $f['q'] ?? '', 'min' => $f['min_price'] ?? '', 'max' => $f['max_price'] ?? '', 'sort' => $f['sort'] ?? 'newest'];
  if ($cat !== '') { $q['category'] = $cat; }
  return http_build_query(array_filter($q, fn($v) => $v !== ''));
};
$sortQs = function (string $s) use ($f): string {
  $q = ['q' => $f['q'] ?? '', 'category' => $f['category'] ?? '', 'min' => $f['min_price'] ?? '', 'max' => $f['max_price'] ?? '', 'sort' => $s];
  return http_build_query(array_filter($q, fn($v) => $v !== ''));
};
?>
<div class="wrap page shop">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Beranda</a> / <span>Katalog</span></nav>
  <div class="catalog__head">
    <h1 class="catalog__title">Katalog Paket</h1>
    <p class="catalog__hint">Ketuk kartu untuk pratinjau cepat, lalu tambah ke keranjang.</p>
  </div>

  <form method="get" action="/products" class="shop-filter" role="search">
    <input type="hidden" name="category" value="<?= View::e($f['category'] ?? '') ?>">
    <input type="hidden" name="sort" value="<?= View::e($sort) ?>">
    <div class="shop-filter__row">
      <label class="sr" for="f-q">Cari paket</label>
      <input class="input" type="search" id="f-q" name="q" placeholder="Cari paket..." value="<?= View::e($f['q'] ?? '') ?>">
      <label class="sr" for="f-min">Harga minimum</label>
      <input class="input shop-filter__num" type="number" id="f-min" name="min" min="0" inputmode="numeric" placeholder="Min Rp" value="<?= View::e($f['min_price'] ?? '') ?>">
      <label class="sr" for="f-max">Harga maksimum</label>
      <input class="input shop-filter__num" type="number" id="f-max" name="max" min="0" inputmode="numeric" placeholder="Maks Rp" value="<?= View::e($f['max_price'] ?? '') ?>">
      <button class="btn btn--primary" type="submit">Filter</button>
    </div>
  </form>

  <nav class="catstrip" aria-label="Kategori paket">
    <a class="catchip<?= ($f['category'] ?? '') === '' ? ' is-active' : '' ?>" href="/products<?= ($qs = $chipQs('')) !== '' ? '?' . View::e($qs) : '' ?>"<?= ($f['category'] ?? '') === '' ? ' aria-current="page"' : '' ?>>
      <span class="catchip__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span>
      <span>Semua</span>
    </a>
    <?php foreach ($cats as $c):
      $on = ($f['category'] ?? '') === $c['slug']; ?>
      <a class="catchip<?= $on ? ' is-active' : '' ?>" href="/products?<?= View::e($chipQs($c['slug'])) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
        <span class="catchip__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $catIcon[$c['slug']] ?? '<circle cx="12" cy="12" r="8"/>' ?></svg></span>
        <span><?= View::e($c['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sortbar" role="navigation" aria-label="Urutkan">
    <span class="sortbar__label">Urutkan</span>
    <a class="<?= $sort === 'newest' ? 'is-active' : '' ?>" <?= $sort === 'newest' ? 'aria-current="true"' : '' ?> href="/products?<?= View::e($sortQs('newest')) ?>">Terbaru</a>
    <a class="<?= $sort === 'price_asc' ? 'is-active' : '' ?>" <?= $sort === 'price_asc' ? 'aria-current="true"' : '' ?> href="/products?<?= View::e($sortQs('price_asc')) ?>">Termurah</a>
    <a class="<?= $sort === 'price_desc' ? 'is-active' : '' ?>" <?= $sort === 'price_desc' ? 'aria-current="true"' : '' ?> href="/products?<?= View::e($sortQs('price_desc')) ?>">Termahal</a>
  </div>

  <?php if (!empty($f['q']) && empty($products)): ?>
    <div class="empty">
      <h2>Tidak ada hasil untuk &ldquo;<?= View::e($f['q']) ?>&rdquo;</h2>
      <p class="muted">Coba kata kunci lain atau <a class="link" href="/products">kembali ke katalog</a>.</p>
    </div>
  <?php endif; ?>

  <p class="result-count" role="status"><?= (int)($total ?? 0) ?> paket ditemukan.</p>

  <div class="mgrid">
    <?php foreach (($products ?? []) as $p):
      echo View::render('store/_card', ['p' => $p, 'variants' => $vmap[(int)$p['id']] ?? []]);
    endforeach; ?>
  </div>

  <?php
  $totalPages = max(1, (int)ceil(($total ?? 0) / ($perPage ?? 12)));
  if ($totalPages > 1):
    $qs = $_GET; ?>
  <nav class="pager" aria-label="Pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): $qs['page'] = $i; ?>
      <a class="<?= $i === ($page ?? 1) ? 'is-here' : '' ?>" <?= $i === ($page ?? 1) ? 'aria-current="page"' : '' ?> href="/products?<?= View::e(http_build_query($qs)) ?>"><?= (int)$i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>
