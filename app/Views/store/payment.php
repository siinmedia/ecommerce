<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
$canPay = in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REJECTED'], true);
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Beranda</a> / <a href="/account/orders">Pesanan</a> / <span><?= View::e($order['order_code']) ?></span></nav>
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em;overflow-wrap:anywhere">Pembayaran <?= View::e($order['order_code']) ?></h1>
  <p class="mb-3"><span class="badge badge-pending"><?= View::e($order['status']) ?></span>
    <span class="muted" style="font-size:14px">Batas bayar: <?= View::e($order['expires_at']) ?></span></p>

  <ol class="steps" aria-label="Langkah belanja">
    <li class="steps__item is-done"><span class="steps__num" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg></span><span>Keranjang</span></li>
    <li class="steps__item is-done"><span class="steps__num" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg></span><span>Checkout</span></li>
    <li class="steps__item is-now" aria-current="step"><span class="steps__num">3</span><span>Bayar</span></li>
  </ol>

  <div class="co">
    <div>
      <section class="panel" aria-labelledby="pay-ins">
        <h2 id="pay-ins">Instruksi Transfer</h2>
        <span class="lbl">Total transfer</span>
        <div class="copyrow">
          <span class="copyrow__val"><?= Helpers::rupiah($order['grand_total']) ?></span>
          <button type="button" class="copy-btn" data-copy="<?= (int)$order['grand_total'] ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
            Salin nominal
          </button>
        </div>
        <?php if ($bank): ?>
          <span class="lbl">Rekening tujuan</span>
          <div class="copyrow">
            <span class="copyrow__val" style="font-size:15px"><strong><?= View::e($bank['bank_name']) ?> <?= View::e($bank['account_number']) ?></strong><br><span class="muted" style="font-weight:400;font-size:13.5px">a.n <?= View::e($bank['account_name']) ?></span></span>
            <button type="button" class="copy-btn" data-copy="<?= View::e($bank['account_number']) ?>">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
              Salin norek
            </button>
          </div>
        <?php endif; ?>
        <span class="lbl">Kode order</span>
        <div class="copyrow">
          <span class="copyrow__val mono" style="font-size:15px"><?= View::e($order['order_code']) ?></span>
          <button type="button" class="copy-btn" data-copy="<?= View::e($order['order_code']) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
            Salin kode
          </button>
        </div>
        <p class="muted mt-3" style="font-size:14px">Transfer dari bank mana pun, lalu unggah bukti di bawah. Cantumkan kode order di berita transfer bila memungkinkan.</p>
      </section>

      <section class="panel" aria-labelledby="pay-item">
        <h2 id="pay-item">Item Pesanan</h2>
        <ul style="font-size:14.5px;display:grid;gap:6px">
          <?php foreach (($items ?? []) as $it): ?>
            <li><?= View::e($it['product_name']) ?> (<?= View::e($it['variant_name']) ?>) x<?= (int)$it['qty'] ?> — <strong><?= Helpers::rupiah($it['line_total']) ?></strong></li>
          <?php endforeach; ?>
        </ul>
        <hr class="divider">
        <p style="font-size:14px">Subtotal <?= Helpers::rupiah($order['subtotal']) ?> &middot; Diskon <?= Helpers::rupiah($order['discount']) ?> &middot; Ongkir <?= Helpers::rupiah($order['shipping_cost']) ?> (<?= View::e(strtoupper($order['courier'] . ' ' . $order['courier_service'])) ?>)</p>
      </section>
    </div>

    <div>
      <aside class="panel summary" aria-labelledby="pay-proof">
        <h2 id="pay-proof">Bukti Pembayaran</h2>
        <?php foreach (($reviews ?? []) as $r): ?>
          <p class="mb-2" style="font-size:14px"><span class="badge <?= $r['decision'] === 'PAID' ? 'badge-paid' : 'badge-rejected' ?>"><?= View::e($r['decision']) ?></span> <?= View::e($r['note']) ?></p>
        <?php endforeach; ?>
        <?php if ($canPay): ?>
          <form method="post" action="/orders/<?= View::e($order['order_code']) ?>/payment/proof" enctype="multipart/form-data" id="proof-form">
            <?= Csrf::field() ?>
            <div class="field">
              <label class="lbl" for="proof">File bukti <span class="req" aria-hidden="true">*</span></label>
              <span class="field__hint" style="margin:0 0 8px">JPG / PNG / WebP / PDF, maks 3MB.</span>
              <label class="drop" for="proof">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin:0 auto 8px"><path d="M12 16V4m0 0 4 4m-4-4-4 4"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                <span style="font-size:14.5px;font-weight:600">Ketuk untuk pilih file</span>
                <input type="file" name="proof" id="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required style="position:absolute;opacity:0;pointer-events:none">
              </label>
              <div class="fileprev" id="fileprev" aria-live="polite">
                <img id="fileprev-img" alt="" hidden>
                <div style="flex:1;min-width:0">
                  <strong id="fileprev-name" style="font-size:14px;word-break:break-all"></strong>
                  <span class="muted" id="fileprev-size" style="font-size:13px"></span>
                  <span class="fileprev__bar" aria-hidden="true"><span class="fileprev__fill" id="fileprev-fill"></span></span>
                </div>
              </div>
            </div>
            <button class="btn btn--buy btn--block" type="submit">Unggah Bukti</button>
          </form>
        <?php else: ?>
          <p class="muted" style="font-size:14.5px">Bukti sudah diproses (status: <?= View::e($order['status']) ?>).</p>
        <?php endif; ?>
        <h3 class="mt-4 mb-2" style="font-size:15px">Riwayat upload</h3>
        <ul style="font-size:14px">
          <?php foreach (($proofs ?? []) as $pf): ?>
            <li><?= View::e($pf['created_at']) ?> — <?= View::e($pf['filename']) ?> (<?= (int)($pf['size_bytes'] / 1024) ?> KB)</li>
          <?php endforeach; ?>
          <?php if (empty($proofs)): ?><li class="muted">Belum ada.</li><?php endif; ?>
        </ul>
      </aside>
    </div>
  </div>
</div>
