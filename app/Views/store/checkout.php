<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Checkout</h1>
<div class="grid md:grid-cols-2 gap-6">
<div>
  <section class="card p-4 mb-4">
    <h2 class="font-bold mb-2">1. Alamat Pengiriman</h2>
    <?php if (!empty($addresses)): ?>
    <div class="space-y-2 mb-3">
      <?php foreach ($addresses as $a): ?>
      <label class="card p-2 flex gap-2 cursor-pointer text-sm">
        <input type="radio" name="addr_pick" value="<?= (int)$a['id'] ?>" data-dest="<?= View::e($a['destination_id']) ?>" data-label="<?= View::e($a['city_label']) ?>" <?= $a['is_default'] ? 'checked' : '' ?>>
        <span><strong><?= View::e($a['recipient']) ?></strong> (<?= View::e($a['phone']) ?>)<br><?= View::e($a['address_line']) ?>, <?= View::e($a['city_label']) ?> <?= View::e($a['postal_code']) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    <?php else: ?><p class="text-sm text-gray-500 mb-2">Belum ada alamat tersimpan.</p><?php endif; ?>
    <details><summary class="text-sm underline cursor-pointer">Tambah alamat baru</summary>
      <form method="post" action="/checkout/address" class="mt-2 space-y-2">
        <?= Csrf::field() ?>
        <input type="text" name="recipient" placeholder="Nama penerima" required>
        <input type="text" name="phone" placeholder="No HP" required>
        <textarea name="address_line" placeholder="Jalan / alamat lengkap" required></textarea>
        <input type="text" id="dest-search" placeholder="Ketik kota tujuan min 3 huruf..." autocomplete="off">
        <ul id="dest-results" class="border rounded bg-white"></ul>
        <input type="hidden" name="destination_id" id="destination_id" required>
        <input type="hidden" name="city_label" id="destination_label" required>
        <input type="text" name="postal_code" placeholder="Kode pos">
        <label class="text-sm"><input type="checkbox" name="is_default" value="1"> Jadikan utama</label>
        <button class="btn-primary px-4 py-1 rounded text-sm" type="submit">Simpan Alamat</button>
      </form>
    </details>
  </section>
  <section class="card p-4 mb-4">
    <h2 class="font-bold mb-2">2. Pengiriman <span class="text-xs font-normal text-gray-500">(mode <?= View::e($raja_mode) ?>)</span></h2>
    <p class="text-sm text-gray-500" id="weight-info"></p>
    <label class="lbl" for="courier">Kurir</label>
    <select id="courier"><option value="">Semua</option><option value="jne">JNE</option><option value="pos">POS</option><option value="tiki">TIKI</option></select>
    <div id="rates" class="space-y-2 mt-2"><p class="text-sm text-gray-500">Pilih alamat untuk menghitung ongkir.</p></div>
  </section>
  <section class="card p-4">
    <h2 class="font-bold mb-2">3. Rekening Tujuan</h2>
    <?php foreach (($banks ?? []) as $b): ?>
      <label class="block text-sm"><input type="radio" name="bank_pick" value="<?= (int)$b['id'] ?>"> <?= View::e($b['bank_name']) ?> — <?= View::e($b['account_number']) ?> a.n <?= View::e($b['account_name']) ?></label>
    <?php endforeach; ?>
  </section>
</div>
<div>
  <section class="card p-4">
    <h2 class="font-bold mb-2">Ringkasan</h2>
    <ul class="text-sm space-y-1">
      <?php foreach ($cart['lines'] as $l): ?>
        <li><?= View::e($l['product_name']) ?> (<?= View::e($l['variant_name']) ?>) x<?= (int)$l['qty'] ?> — <?= Helpers::rupiah($l['line_total']) ?></li>
      <?php endforeach; ?>
    </ul>
    <hr class="my-2">
    <div class="flex gap-2">
      <input type="text" id="promo_code" placeholder="Kode promo (opsional)">
      <button type="button" id="promo-btn" class="px-3 py-1 border rounded text-sm">Pakai</button>
    </div>
    <p id="promo-msg" class="text-sm mt-1"></p>
    <dl class="text-sm mt-2 space-y-1">
      <div class="flex justify-between"><dt>Subtotal</dt><dd><?= Helpers::rupiah($cart['subtotal']) ?></dd></div>
      <div class="flex justify-between"><dt>Ongkir</dt><dd id="t-ship">Rp0</dd></div>
      <div class="flex justify-between"><dt>Diskon</dt><dd id="t-disc">-Rp0</dd></div>
      <div class="flex justify-between font-bold text-base"><dt>Total</dt><dd id="t-total"><?= Helpers::rupiah($cart['subtotal']) ?></dd></div>
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
      <textarea name="note" placeholder="Catatan (opsional)" class="mb-2"></textarea>
      <button type="submit" class="btn-primary px-6 py-2 rounded w-full">Buat Pesanan</button>
    </form>
  </section>
</div>
</div>
<script>
// Wire address/bank picks into hidden order fields
document.querySelectorAll('input[name="addr_pick"]').forEach(function (el) {
  el.addEventListener('change', function () {
    document.getElementById('f_address').value = el.value;
    document.getElementById('destination_id').value = el.getAttribute('data-dest');
    document.getElementById('f_dest_label').value = el.getAttribute('data-label');
  });
});
document.querySelectorAll('input[name="bank_pick"]').forEach(function (el) {
  el.addEventListener('change', function () { document.getElementById('f_bank').value = el.value; });
});
(function init() {
  var a = document.querySelector('input[name="addr_pick"]:checked');
  if (a) { a.dispatchEvent(new Event('change')); }
  var b = document.querySelector('input[name="bank_pick"]');
  if (b) { b.checked = true; b.dispatchEvent(new Event('change')); }
  document.getElementById('promo_code').addEventListener('input', function (e) {
    document.getElementById('f_promo').value = e.target.value;
  });
})();
</script>
