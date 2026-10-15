<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
$isStaff = ($is_admin ?? false) || ($is_dev ?? false);
$siteName = ($config['app']['name'] ?? 'Toko');
// Ringkasan cart untuk bottom sheet (data yang sama dengan halaman /cart).
$sheetCart = ['lines' => [], 'subtotal' => 0, 'count' => 0];
if (!$isStaff && class_exists(\App\Controllers\CartController::class)) {
  try { $sheetCart = \App\Controllers\CartController::lines(); } catch (\Throwable) { $sheetCart = ['lines' => [], 'subtotal' => 0, 'count' => 0]; }
}
$sheetCount = (int)($sheetCart['count'] ?? 0);
?>
</main>
<footer class="foot">
  <div class="wrap">
    <div class="foot__grid">
      <div>
        <div class="foot__brand">
          <span class="foot__mark" aria-hidden="true"><?= View::e(mb_substr($siteName, 0, 1)) ?></span>
          <span class="foot__name"><?= View::e($siteName) ?></span>
        </div>
        <p class="foot__desc">Katalog paket kemitraan. Bayar via transfer bank, unggah bukti, pesanan diverifikasi admin.</p>
      </div>
      <div class="foot__col">
        <h2 class="foot__h">Belanja</h2>
        <div class="foot__links">
          <a href="/">Beranda</a>
          <a href="/products">Katalog</a>
          <a href="/cart">Keranjang</a>
        </div>
      </div>
      <div class="foot__col">
        <h2 class="foot__h">Akun</h2>
        <div class="foot__links">
          <?php if (!empty($user)): ?>
            <a href="/account/orders">Pesanan Saya</a>
            <a href="/account/addresses">Alamat Saya</a>
            <form method="post" action="/logout" class="foot__logout"><?= Csrf::field() ?><button class="foot__linkbtn" type="submit">Keluar</button></form>
          <?php else: ?>
            <a href="/login">Masuk</a>
            <a href="/register">Daftar</a>
            <a href="/login">Lacak Pesanan</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="foot__col">
        <h2 class="foot__h">Kebijakan</h2>
        <div class="foot__contact">
          <span>Pembayaran transfer bank, verifikasi maks 1x24 jam.</span>
          <span>Pengiriman JNE / POS / TIKI, ongkir dihitung saat checkout.</span>
          <span>Jam operasional Senin&ndash;Sabtu 09.00&ndash;17.00 WIB.</span>
        </div>
      </div>
    </div>
    <div class="foot__bottom">
      <span>&copy; <?= date('Y') ?> <?= View::e($siteName) ?></span>
      <span>Harga paket bersifat contoh</span>
    </div>
  </div>
</footer>
<?php if (!$isStaff): ?>
<?php if ($sheetCount > 0): ?>
<div class="cartbar" id="cartbar">
  <button class="cartbar__btn" type="button" id="cart-open" aria-label="Lihat ringkasan keranjang, <?= (int)$sheetCount ?> jenis barang">
    <span class="cartbar__icon" aria-hidden="true">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 12.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6"/></svg>
    </span>
    <span class="cartbar__meta">
      <span class="cartbar__count"><?= (int)$sheetCount ?> jenis barang</span>
      <span class="cartbar__total"><?= Helpers::rupiah($sheetCart['subtotal']) ?></span>
    </span>
    <span class="cartbar__go">Lihat</span>
  </button>
</div>
<?php endif; ?>
<div class="sheet sheet--cart" id="cart-sheet" aria-hidden="true">
  <div class="sheet__scrim" data-close></div>
  <div class="sheet__panel sheet__panel--cart" role="dialog" aria-modal="true" aria-labelledby="cart-title">
    <button class="sheet__close" type="button" data-close aria-label="Tutup ringkasan keranjang">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
    <div class="sheet__scroll">
      <h2 class="sheet__h" id="cart-title">Keranjang</h2>
      <?php if (empty($sheetCart['lines'])): ?>
        <p class="sheet__sub">Belum ada item. Pilih paket dulu.</p>
      <?php else: ?>
        <p class="sheet__sub"><?= (int)$sheetCount ?> jenis barang</p>
        <ul class="cart-list">
          <?php foreach ($sheetCart['lines'] as $l): ?>
            <li class="cart-row">
              <span class="cart-row__img" aria-hidden="true"><?= View::e(mb_strtoupper(mb_substr(trim($l['product_name']), 0, 1))) ?></span>
              <span class="cart-row__body">
                <span class="cart-row__name"><?= View::e($l['product_name']) ?></span>
                <span class="cart-row__opts"><?= View::e($l['variant_name']) ?> &times;<?= (int)$l['qty'] ?></span>
              </span>
              <strong class="cart-row__price"><?= Helpers::rupiah($l['line_total']) ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="sheet__foot">
      <div class="sheet__total">
        <span class="sheet__total-label">Subtotal</span>
        <span class="sheet__total-val"><?= Helpers::rupiah($sheetCart['subtotal']) ?></span>
      </div>
      <div class="sheet__actions">
        <a class="btn btn--ghost btn--block" href="/cart">Keranjang</a>
        <a class="btn btn--buy btn--block" href="/checkout">Checkout</a>
      </div>
    </div>
  </div>
</div>
<div class="sheet" id="qv-sheet" aria-hidden="true">
  <div class="sheet__scrim" data-close></div>
  <div class="sheet__panel" role="dialog" aria-modal="true" aria-labelledby="qv-title">
    <button class="sheet__close" type="button" data-close aria-label="Tutup pratinjau produk">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
    <div class="order-hero" aria-hidden="true">
      <span class="order-hero__ph" id="qv-ph">?</span>
      <span class="order-hero__cat" id="qv-cat"></span>
    </div>
    <div class="sheet__scroll">
      <h2 class="order-title" id="qv-title"></h2>
      <p class="order-desc" id="qv-desc"></p>
      <p class="order-price"><span class="order-price__now" id="qv-price"></span></p>
      <form method="post" action="/cart/add" id="qv-form">
        <?= Csrf::field() ?>
        <div class="field">
          <span class="lbl" id="qv-variant-label">Pilih paket</span>
          <div class="variants" id="qv-variants" role="radiogroup" aria-labelledby="qv-variant-label"></div>
        </div>
        <div class="opt-row">
          <span class="opt-row__label">Jumlah</span>
          <div class="stepper">
            <button type="button" id="qv-minus" aria-label="Kurangi jumlah">&minus;</button>
            <input type="hidden" name="qty" id="qv-qty" value="1">
            <span class="stepper__val" id="qv-qty-val" aria-live="polite">1</span>
            <button type="button" id="qv-plus" aria-label="Tambah jumlah">+</button>
          </div>
        </div>
        <p class="muted" id="qv-stock" style="font-size:13.5px;margin-top:8px"></p>
      </form>
    </div>
    <div class="sheet__foot">
      <div class="sheet__total">
        <span class="sheet__total-label">Total</span>
        <span class="sheet__total-val" id="qv-total">Rp0</span>
      </div>
      <div class="sheet__actions">
        <button class="btn btn--buy btn--block" type="submit" form="qv-form" id="qv-add">Tambah ke Keranjang</button>
        <a class="btn btn--ghost btn--block" id="qv-detail" href="/products">Detail</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="sr" role="status" aria-live="polite" id="live"></div>
<script src="/assets/js/app.js"></script>
</body>
</html>
