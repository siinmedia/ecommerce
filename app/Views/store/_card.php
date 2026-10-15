<?php
use App\Core\View;
/**
 * Kartu produk katalog ala etalase jajanan — partial bersama home + catalog.
 * Variabel: $p (row produk + category_name), $variants (list varian aktif: id/name/price/stock).
 * Klik kartu -> bottom sheet quick-view (JS). Tautan Detail untuk no-JS/keyboard.
 * Hanya data nyata: tanpa coret harga, diskon %, terjual, rating.
 */
$brand = preg_replace('/^paket kemitraan\s+/i', '', trim($p['name'] ?? ''));
$initial = mb_strtoupper(mb_substr($brand !== '' ? $brand : trim($p['name'] ?? '?'), 0, 1));
$variants = array_values($variants ?? []);
$totalStock = 0;
foreach ($variants as $v) { $totalStock += (int)$v['stock']; }
$hasStock = $totalStock > 0;
$stockTxt = !$hasStock ? 'Habis' : ($totalStock <= 10 ? 'Sisa ' . $totalStock : 'Stok tersedia');
$desc = trim(strip_tags($p['description'] ?? ''));
if (mb_strlen($desc) > 140) { $desc = mb_substr($desc, 0, 140) . '…'; }
$vdata = array_map(fn($v) => ['id' => (int)$v['id'], 'name' => (string)$v['name'], 'price' => (int)$v['price'], 'stock' => (int)$v['stock']], $variants);
$slug = (string)($p['slug'] ?? '');
?>
<article class="mcard" tabindex="0" role="button"
  data-slug="<?= View::e($slug) ?>"
  data-name="<?= View::e($p['name']) ?>"
  data-cat="<?= View::e($p['category_name'] ?? '') ?>"
  data-desc="<?= View::e($desc) ?>"
  data-variants="<?= View::e(json_encode($vdata)) ?>"
  aria-label="<?= View::e($p['name']) ?>. Ketuk untuk pratinjau cepat.">
  <div class="mcard__img" aria-hidden="true">
    <span class="mcard__ph"><?= View::e($initial) ?></span>
    <?php if (!empty($p['category_name'])): ?><span class="mcard__cat"><?= View::e($p['category_name']) ?></span><?php endif; ?>
  </div>
  <div class="mcard__body">
    <h3 class="mcard__title"><?= View::e($p['name']) ?></h3>
    <?php if ($desc !== ''): ?><p class="mcard__desc"><?= View::e($desc) ?></p><?php endif; ?>
    <div class="mcard__foot">
      <div class="mcard__price">
        <span class="mcard__price-now"><?= \App\Core\Helpers::rupiah($p['price']) ?></span>
        <span class="mcard__stock<?= $hasStock ? '' : ' is-out' ?>"><?= View::e($stockTxt) ?></span>
      </div>
      <a class="mcard__detail" href="/products/<?= View::e($slug) ?>">Detail</a>
    </div>
  </div>
</article>
