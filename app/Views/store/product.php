<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
$variants = $product['variants'] ?? [];
$hasStock = !empty($variants);
$brand = preg_replace('/^paket kemitraan\s+/i', '', trim($product['name']));
$initial = mb_strtoupper(mb_substr($brand !== '' ? $brand : trim($product['name']), 0, 1));
$totalStock = 0;
foreach ($variants as $v) { $totalStock += (int)$v['stock']; }
$stockTxt = !$hasStock || $totalStock <= 0 ? 'Stok habis' : ($totalStock <= 5 ? 'Sisa ' . $totalStock : 'Stok tersedia');
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="/">Beranda</a> / <a href="/products">Katalog</a> /
    <?php if (!empty($product['category_slug'])): ?><a href="/products?category=<?= View::e($product['category_slug']) ?>"><?= View::e($product['category_name']) ?></a> /<?php endif; ?>
    <span><?= View::e($product['name']) ?></span>
  </nav>

  <div class="pd">
    <div class="pd__gal">
      <div class="pd__media" role="img" aria-label="Gambar <?= View::e($product['name']) ?>">
        <span class="p-ph" aria-hidden="true"><span class="p-ph__mark"><?= View::e($initial) ?></span></span>
      </div>
    </div>

    <div class="pd__body">
      <h1 class="pd__title"><?= View::e($product['name']) ?></h1>
      <p class="pd__meta">SKU: <?= View::e($product['sku']) ?> &middot; Kategori: <?= View::e($product['category_name'] ?? '-') ?> &middot; Berat dasar: <?= (int)($product['weight_gram'] ?? 0) ?> gram</p>
      <p class="pd__price" id="pd-price" aria-live="polite"><?= Helpers::rupiah($product['price']) ?></p>
      <p class="pd__total">Total: <strong id="pd-total"><?= Helpers::rupiah($product['price']) ?></strong> <span class="muted" id="pd-stock"><?= View::e($stockTxt) ?></span></p>

      <form method="post" action="/cart/add" id="pd-form">
        <?= Csrf::field() ?>
        <div class="field">
          <span class="lbl" id="variant-label">Pilih paket</span>
          <?php if ($hasStock): ?>
          <div class="variants" role="radiogroup" aria-labelledby="variant-label">
            <?php foreach ($variants as $i => $v): ?>
              <label class="variant">
                <input type="radio" name="variant_id" value="<?= (int)$v['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>
                  data-price="<?= (int)$v['price'] ?>" data-stock="<?= (int)$v['stock'] ?>" data-name="<?= View::e($v['name']) ?>">
                <span><?= View::e($v['name']) ?> — <?= Helpers::rupiah($v['price']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
            <p class="field__err">Stok habis untuk semua varian.</p>
          <?php endif; ?>
        </div>

        <div class="field">
          <label class="lbl" for="qty">Jumlah</label>
          <div class="stepper">
            <button type="button" id="qty-minus" aria-label="Kurangi jumlah">−</button>
            <input type="number" name="qty" id="qty" value="1" min="1" max="99" inputmode="numeric" <?= $hasStock ? '' : 'disabled' ?>>
            <button type="button" id="qty-plus" aria-label="Tambah jumlah">+</button>
          </div>
        </div>

        <div class="buybar">
          <button type="submit" class="btn btn--ghost" <?= $hasStock ? '' : 'disabled' ?>>Tambah</button>
          <button type="button" class="btn btn--buy" id="buy-now" <?= $hasStock ? '' : 'disabled' ?>>Beli Sekarang</button>
        </div>
        <div class="pd__pad" aria-hidden="true"></div>
      </form>

      <div class="pd__desc"><?= nl2br(View::e($product['description'])) ?></div>

      <div class="pd__ship">
        <div class="pd__ship-row">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
          <span>Pengiriman JNE / POS / TIKI. Ongkir dihitung saat checkout mengikuti berat varian yang dipilih.</span>
        </div>
        <div class="pd__ship-row">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v5c0 5 3.4 8.4 7 10 3.6-1.6 7-5 7-10V6z"/><path d="m9.5 12 2 2 3.5-4"/></svg>
          <span>Berat dan SKU mengikuti varian: Reguler 25.000 gram, Lengkap 40.000 gram. Total bayar = harga varian x jumlah + ongkir.</span>
        </div>
      </div>
    </div>
  </div>
</div>
