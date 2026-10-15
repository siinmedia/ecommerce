<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
$firstVariant = [];
try {
  $ids = array_map(fn($p) => (int)$p['id'], $products ?? []);
  $ids = array_values(array_filter($ids));
  if ($ids) {
    $in = implode(',', $ids);
    $st = \App\Core\Database::pdo()->query(
      "SELECT product_id, MIN(id) AS vid FROM product_variants WHERE product_id IN ({$in}) AND status = 'active' GROUP BY product_id"
    );
    foreach ($st->fetchAll() as $r) { $firstVariant[(int)$r['product_id']] = (int)$r['vid']; }
  }
} catch (\Throwable) { $firstVariant = []; }
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Beranda</a> / <span>Katalog</span></nav>
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em">Katalog Produk</h1>
  <p class="muted mb-3">Cari, saring kategori, dan urutkan sesuai kebutuhan.</p>

  <form method="get" action="/products" class="filter" role="search">
    <div class="filter__grid">
      <div class="field" style="margin:0">
        <label class="lbl" for="f-q">Pencarian</label>
        <input class="input" type="search" id="f-q" name="q" placeholder="Cari produk..." value="<?= View::e($filters['q'] ?? '') ?>">
      </div>
      <div class="field" style="margin:0">
        <label class="lbl" for="f-cat">Kategori</label>
        <select class="select" id="f-cat" name="category">
          <option value="">Semua kategori</option>
          <?php foreach (($categories ?? []) as $c): ?>
            <option value="<?= View::e($c['slug']) ?>" <?= (($filters['category'] ?? '') === $c['slug']) ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="margin:0">
        <label class="lbl" for="f-sort">Urutkan</label>
        <select class="select" id="f-sort" name="sort">
          <?php $s = $filters['sort'] ?? 'newest'; ?>
          <option value="newest" <?= $s === 'newest' ? 'selected' : '' ?>>Terbaru</option>
          <option value="price_asc" <?= $s === 'price_asc' ? 'selected' : '' ?>>Harga terendah</option>
          <option value="price_desc" <?= $s === 'price_desc' ? 'selected' : '' ?>>Harga tertinggi</option>
        </select>
      </div>
      <div class="field" style="margin:0">
        <label class="lbl" for="f-min">Min Rp</label>
        <input class="input" type="number" id="f-min" name="min" min="0" inputmode="numeric" placeholder="0" value="<?= View::e($filters['min_price'] ?? '') ?>">
      </div>
      <div class="field" style="margin:0">
        <button class="btn btn--primary btn--block" type="submit">Filter</button>
      </div>
    </div>
  </form>

  <?php if (!empty($filters['q']) && empty($products)): ?>
    <div class="empty">
      <h2>Tidak ada hasil untuk &ldquo;<?= View::e($filters['q']) ?>&rdquo;</h2>
      <p class="muted">Coba kata kunci lain atau <a class="link" href="/products">kembali ke katalog</a>.</p>
    </div>
  <?php endif; ?>

  <p class="result-count" role="status"><?= (int)($total ?? 0) ?> produk ditemukan.</p>

  <div class="grid grid--prods">
    <?php foreach (($products ?? []) as $p):
      $initial = mb_strtoupper(mb_substr(trim($p['name']), 0, 1));
      $disc = !empty($p['compare_price']) && (int)$p['compare_price'] > (int)$p['price']
        ? (int)round((1 - (int)$p['price'] / (int)$p['compare_price']) * 100) : 0;
      $vid = $firstVariant[(int)$p['id']] ?? null;
    ?>
    <article class="p-card">
      <a class="p-media" href="/products/<?= View::e($p['slug']) ?>" aria-label="<?= View::e($p['name']) ?>">
        <span class="p-ph" aria-hidden="true"><span class="p-ph__mark"><?= View::e($initial) ?></span><span class="p-ph__dots"></span></span>
        <?php if ($disc > 0): ?><span class="p-disc">-<?= (int)$disc ?>%</span><?php endif; ?>
      </a>
      <div class="p-body">
        <span class="p-cat"><?= View::e($p['category_name'] ?? '') ?></span>
        <a class="p-name" href="/products/<?= View::e($p['slug']) ?>"><?= View::e($p['name']) ?></a>
        <span class="p-price"><?= Helpers::rupiah($p['price']) ?></span>
        <?php if (!empty($p['compare_price']) && (int)$p['compare_price'] > (int)$p['price']): ?>
          <span class="p-was"><?= Helpers::rupiah($p['compare_price']) ?></span>
        <?php endif; ?>
        <div class="p-foot">
          <?php if ($vid): ?>
          <form method="post" action="/cart/add">
            <?= Csrf::field() ?>
            <input type="hidden" name="variant_id" value="<?= (int)$vid ?>">
            <input type="hidden" name="qty" value="1">
            <button class="btn btn--buy btn--sm btn--block" type="submit">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
              Keranjang
            </button>
          </form>
          <?php else: ?>
            <a class="btn btn--ghost btn--sm btn--block" href="/products/<?= View::e($p['slug']) ?>">Lihat Detail</a>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
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
