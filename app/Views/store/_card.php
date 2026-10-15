<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
/**
 * Kartu produk katalog — partial bersama home + catalog.
 * Variabel: $p (row produk), $vid (int|null varian pertama), $stock (int total stok varian aktif).
 * Hanya data nyata: tanpa coret harga, diskon %, terjual, rating.
 */
$brand = preg_replace('/^paket kemitraan\s+/i', '', trim($p['name'] ?? ''));
$initial = mb_strtoupper(mb_substr($brand !== '' ? $brand : trim($p['name'] ?? '?'), 0, 1));
$stock = (int)($stock ?? 0);
$stockCls = $stock <= 0 ? ' is-out' : ($stock <= 5 ? ' is-low' : '');
$stockTxt = $stock <= 0 ? 'Habis' : ($stock <= 5 ? 'Sisa ' . $stock : 'Stok tersedia');
?>
<article class="p-card">
  <a class="p-media" href="/products/<?= View::e($p['slug']) ?>" aria-label="<?= View::e($p['name']) ?>">
    <span class="p-ph" aria-hidden="true"><span class="p-ph__mark"><?= View::e($initial) ?></span></span>
  </a>
  <div class="p-body">
    <span class="p-cat"><?= View::e($p['category_name'] ?? '') ?></span>
    <a class="p-name" href="/products/<?= View::e($p['slug']) ?>"><?= View::e($p['name']) ?></a>
    <span class="p-price"><?= Helpers::rupiah($p['price']) ?></span>
    <span class="p-stock<?= $stockCls ?>"><?= View::e($stockTxt) ?></span>
    <div class="p-foot">
      <?php if (!empty($vid) && $stock > 0): ?>
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
