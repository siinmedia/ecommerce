<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Beranda</a> / <a href="/cart">Keranjang</a> / <span>Checkout</span></nav>
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em">Checkout</h1>

  <ol class="steps" aria-label="Langkah belanja">
    <li class="steps__item is-done"><span class="steps__num" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg></span><span>Keranjang</span></li>
    <li class="steps__item is-now" aria-current="step"><span class="steps__num">2</span><span>Checkout</span></li>
    <li class="steps__item"><span class="steps__num">3</span><span>Bayar</span></li>
  </ol>

  <div class="co">
    <div>
      <section class="panel" aria-labelledby="co-addr">
        <h2 id="co-addr">1. Alamat Pengiriman</h2>
        <?php if (!empty($addresses)): ?>
          <div class="mb-3">
            <?php foreach ($addresses as $a): ?>
              <label class="pick">
                <span class="row" style="align-items:flex-start">
                  <input type="radio" name="addr_pick" value="<?= (int)$a['id'] ?>" data-dest="<?= View::e($a['destination_id']) ?>" data-label="<?= View::e($a['city_label']) ?>" <?= $a['is_default'] ? 'checked' : '' ?>>
                  <span><strong><?= View::e($a['recipient']) ?></strong> (<?= View::e($a['phone']) ?>)<br><?= View::e($a['address_line']) ?>, <?= View::e($a['city_label']) ?> <?= View::e($a['postal_code']) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="muted mb-2" style="font-size:14.5px">Belum ada alamat tersimpan.</p>
        <?php endif; ?>
        <details>
          <summary class="link">Tambah alamat baru</summary>
          <form method="post" action="/checkout/address" class="mt-3">
            <?= Csrf::field() ?>
            <div class="field"><label class="lbl" for="n-recipient">Nama penerima <span class="req" aria-hidden="true">*</span></label><input class="input" type="text" id="n-recipient" name="recipient" required autocomplete="name"></div>
            <div class="field"><label class="lbl" for="n-phone">No HP <span class="req" aria-hidden="true">*</span></label><input class="input" type="text" id="n-phone" name="phone" required autocomplete="tel" inputmode="tel"></div>
            <div class="field"><label class="lbl" for="n-line">Jalan / alamat lengkap <span class="req" aria-hidden="true">*</span></label><textarea class="textarea" id="n-line" name="address_line" required></textarea></div>
            <div class="field">
              <label class="lbl" for="dest-search">Kota tujuan <span class="req" aria-hidden="true">*</span></label>
              <input class="input" type="text" id="dest-search" placeholder="Ketik kota tujuan min 3 huruf..." autocomplete="off" role="combobox" aria-expanded="false" aria-controls="dest-results">
              <ul id="dest-results" class="dest-results mt-2"></ul>
              <input type="hidden" name="destination_id" id="destination_id" required>
              <input type="hidden" name="city_label" id="destination_label" required>
            </div>
            <div class="field"><label class="lbl" for="n-postal">Kode pos</label><input class="input" type="text" id="n-postal" name="postal_code" inputmode="numeric"></div>
            <div class="field"><label class="row" style="font-size:14.5px"><input type="checkbox" name="is_default" value="1" style="width:20px;height:20px;accent-color:var(--blue)"> Jadikan alamat utama</label></div>
            <button class="btn btn--primary" type="submit">Simpan Alamat</button>
          </form>
        </details>
      </section>

      <section class="panel" aria-labelledby="co-ship">
        <h2 id="co-ship">2. Pengiriman <span class="muted" style="font-size:12.5px;font-weight:400">(mode <?= View::e($raja_mode) ?>)</span></h2>
        <p class="muted" id="weight-info" style="font-size:14px"></p>
        <div class="field mt-2">
          <label class="lbl" for="courier">Kurir</label>
          <select class="select" id="courier"><option value="">Semua</option><option value="jne">JNE</option><option value="pos">POS</option><option value="tiki">TIKI</option></select>
        </div>
        <div id="rates"><p class="muted" style="font-size:14.5px">Pilih alamat untuk menghitung ongkir.</p></div>
      </section>

      <section class="panel" aria-labelledby="co-bank">
        <h2 id="co-bank">3. Rekening Tujuan</h2>
        <?php foreach (($banks ?? []) as $b): ?>
          <label class="pick">
            <span class="row" style="align-items:flex-start">
              <input type="radio" name="bank_pick" value="<?= (int)$b['id'] ?>">
              <span><?= View::e($b['bank_name']) ?> — <strong><?= View::e($b['account_number']) ?></strong> a.n <?= View::e($b['account_name']) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </section>
    </div>

    <div>
      <aside class="panel summary" aria-labelledby="co-sum">
        <h2 id="co-sum">Ringkasan</h2>
        <ul class="mb-3" style="font-size:14.5px;display:grid;gap:6px">
          <?php foreach ($cart['lines'] as $l): ?>
            <li><?= View::e($l['product_name']) ?> (<?= View::e($l['variant_name']) ?>) x<?= (int)$l['qty'] ?> — <strong><?= Helpers::rupiah($l['line_total']) ?></strong></li>
          <?php endforeach; ?>
        </ul>
        <hr class="divider">
        <div class="field">
          <label class="lbl" for="promo_code">Kode promo (opsional)</label>
          <div class="row" style="flex-wrap:nowrap">
            <input class="input" type="text" id="promo_code" placeholder="cth: HEMAT10" autocomplete="off">
            <button type="button" id="promo-btn" class="btn btn--ghost" style="flex:none">Pakai</button>
          </div>
          <p id="promo-msg" class="mt-2" style="font-size:14px" role="status"></p>
        </div>
        <dl>
          <div class="sumline"><dt>Subtotal</dt><dd><?= Helpers::rupiah($cart['subtotal']) ?></dd></div>
          <div class="sumline"><dt>Ongkir</dt><dd id="t-ship">Rp0</dd></div>
          <div class="sumline"><dt>Diskon</dt><dd id="t-disc">-Rp0</dd></div>
          <div class="sumline sumline--total"><dt>Total</dt><dd id="t-total"><?= Helpers::rupiah($cart['subtotal']) ?></dd></div>
        </dl>
        <form method="post" action="/checkout/place" id="place-order" class="mt-3">
          <?= Csrf::field() ?>
          <input type="hidden" name="address_id" id="f_address">
          <input type="hidden" name="bank_id" id="f_bank">
          <input type="hidden" name="courier" id="f_courier">
          <input type="hidden" name="service" id="f_service">
          <input type="hidden" name="destination_label" id="f_dest_label">
          <input type="hidden" name="idempotency_key" id="idempotency_key">
          <input type="hidden" id="f_subtotal" value="<?= (int)$cart['subtotal'] ?>">
          <input type="hidden" id="f_ship_cost" value="0">
          <input type="hidden" id="f_discount" value="0">
          <input type="hidden" name="promo_code" id="f_promo">
          <div class="field"><label class="lbl" for="f-note">Catatan (opsional)</label><textarea class="textarea" id="f-note" name="note" placeholder="Contoh: titip packing ekstra"></textarea></div>
          <button type="submit" class="btn btn--buy btn--lg btn--block">Buat Pesanan</button>
        </form>
      </aside>
    </div>
  </div>
</div>
