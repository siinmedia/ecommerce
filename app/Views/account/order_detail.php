<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="/account/orders">Pesanan Saya</a> / <span><?= View::e($order['order_code']) ?></span></nav>
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em">Order <?= View::e($order['order_code']) ?></h1>
  <p class="mb-3"><span class="badge badge-neutral"><?= View::e($order['status']) ?></span> Total <strong><?= Helpers::rupiah($order['grand_total']) ?></strong></p>

  <div class="co">
    <div>
      <section class="panel" aria-labelledby="od-item">
        <h2 id="od-item">Item</h2>
        <ul style="font-size:14.5px;display:grid;gap:6px">
          <?php foreach (($items ?? []) as $it): ?><li><?= View::e($it['product_name']) ?> (<?= View::e($it['variant_name']) ?>) x<?= (int)$it['qty'] ?> — <strong><?= Helpers::rupiah($it['line_total']) ?></strong></li><?php endforeach; ?>
        </ul>
      </section>
      <section class="panel" aria-labelledby="od-time">
        <h2 id="od-time">Timeline Status</h2>
        <?php if (!empty($history)): ?>
        <ol class="timeline">
          <?php foreach ($history as $h): ?>
            <li><time><?= View::e($h['created_at']) ?></time><?= View::e($h['from_status'] ?? 'CREATED') ?> → <strong><?= View::e($h['to_status']) ?></strong><?php if (!empty($h['note'])): ?> <span class="muted">(<?= View::e($h['note']) ?>)</span><?php endif; ?></li>
          <?php endforeach; ?>
        </ol>
        <?php else: ?>
          <p class="muted" style="font-size:14.5px">Belum ada riwayat.</p>
        <?php endif; ?>
      </section>
    </div>
    <div>
      <aside class="panel summary" aria-labelledby="od-ship">
        <h2 id="od-ship">Pengiriman</h2>
        <?php if ($shipment): ?>
          <p style="font-size:14.5px">Kurir: <strong><?= View::e(strtoupper($shipment['courier'] . ' ' . $shipment['service'])) ?></strong></p>
          <p style="font-size:14.5px">Resi: <strong><?= View::e($shipment['waybill'] ?? '-') ?></strong></p>
          <?php if (!empty($shipment['waybill'])): ?><a class="link" href="/account/orders/<?= View::e($order['order_code']) ?>/track">Perbarui pelacakan</a><?php endif; ?>
          <h3 class="mt-3 mb-2" style="font-size:15px">Tracking</h3>
          <ul style="font-size:14px;display:grid;gap:6px">
            <?php foreach (($events ?? []) as $ev): ?><li><?= View::e($ev['event_time']) ?> — <?= View::e($ev['description']) ?></li><?php endforeach; ?>
            <?php if (empty($events)): ?><li class="muted">Belum ada event.</li><?php endif; ?>
          </ul>
        <?php else: ?>
          <p class="muted" style="font-size:14.5px">Belum ada data kirim.</p>
        <?php endif; ?>
        <hr class="divider">
        <div class="row">
          <?php if (in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REJECTED'], true)): ?>
            <a href="/orders/<?= View::e($order['order_code']) ?>/payment" class="btn btn--buy btn--sm">Bayar / Upload Bukti</a>
          <?php endif; ?>
          <?php if (in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REVIEW', 'PAYMENT_REJECTED'], true)): ?>
            <form method="post" action="/account/orders/<?= View::e($order['order_code']) ?>/cancel"><?= Csrf::field() ?><button class="link link--danger" type="submit" data-confirm="Batalkan pesanan ini?">Batalkan</button></form>
          <?php endif; ?>
          <form method="post" action="/account/orders/<?= View::e($order['order_code']) ?>/reorder"><?= Csrf::field() ?><button class="link" type="submit">Beli Lagi</button></form>
        </div>
      </aside>
    </div>
  </div>
</div>
