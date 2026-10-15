<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Beranda</a> / <span>Keranjang</span></nav>
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em">Keranjang</h1>

  <ol class="steps" aria-label="Langkah belanja">
    <li class="steps__item is-now" aria-current="step"><span class="steps__num">1</span><span>Keranjang</span></li>
    <li class="steps__item"><span class="steps__num">2</span><span>Checkout</span></li>
    <li class="steps__item"><span class="steps__num">3</span><span>Bayar</span></li>
  </ol>

  <?php if (empty($cart['lines'])): ?>
    <div class="empty">
      <h2>Keranjang kosong</h2>
      <p class="muted mb-3">Yuk isi dengan produk pilihan.</p>
      <a href="/products" class="btn btn--buy">Belanja Sekarang</a>
    </div>
  <?php else: ?>
    <div class="cartlines">
      <?php foreach ($cart['lines'] as $l): ?>
        <div class="cartline">
          <div>
            <a class="cartline__name" href="/products/<?= View::e($l['product_slug']) ?>"><?= View::e($l['product_name']) ?></a>
            <p class="cartline__var"><?= View::e($l['variant_name']) ?> &middot; <?= Helpers::rupiah($l['price']) ?> /pcs</p>
            <?php if ($l['over_stock']): ?><p><span class="badge badge-failed">Stok sisa <?= (int)$l['stock'] ?></span></p><?php endif; ?>
          </div>
          <div class="cartline__row">
            <form method="post" action="/cart/update" class="qty-mini" aria-label="Ubah jumlah <?= View::e($l['product_name']) ?>">
              <?= Csrf::field() ?>
              <input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>">
              <label class="sr" for="qty-<?= (int)$l['variant_id'] ?>">Jumlah</label>
              <input class="input" type="number" id="qty-<?= (int)$l['variant_id'] ?>" name="qty" value="<?= (int)$l['qty'] ?>" min="0" max="99" inputmode="numeric">
              <button class="btn btn--ghost btn--sm" type="submit">Ubah</button>
            </form>
            <strong><?= Helpers::rupiah($l['line_total']) ?></strong>
            <form method="post" action="/cart/update">
              <?= Csrf::field() ?>
              <input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>">
              <input type="hidden" name="qty" value="0">
              <button class="link link--danger" type="submit" data-confirm="Hapus barang ini dari keranjang?">Hapus</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel mt-4">
      <div class="spread">
        <form method="post" action="/cart/clear"><?= Csrf::field() ?><button class="link" type="submit" data-confirm="Kosongkan seluruh keranjang?">Kosongkan</button></form>
        <p style="font-size:18px">Subtotal: <strong><?= Helpers::rupiah($cart['subtotal']) ?></strong></p>
      </div>
      <div class="mt-3"><a href="/checkout" class="btn btn--buy btn--lg btn--block">Lanjut Checkout</a></div>
    </div>
  <?php endif; ?>
</div>
