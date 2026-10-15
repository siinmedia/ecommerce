<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-1">Order <?= View::e($order['order_code']) ?></h1>
<p class="mb-4"><span class="badge badge-neutral"><?= View::e($order['status']) ?></span> Total <strong><?= Helpers::rupiah($order['grand_total']) ?></strong></p>
<div class="grid md:grid-cols-2 gap-4">
<section class="card p-4">
  <h2 class="font-bold mb-2">Item</h2>
  <ul class="text-sm space-y-1">
    <?php foreach (($items ?? []) as $it): ?><li><?= View::e($it['product_name']) ?> (<?= View::e($it['variant_name']) ?>) x<?= (int)$it['qty'] ?> — <?= Helpers::rupiah($it['line_total']) ?></li><?php endforeach; ?>
  </ul>
  <h2 class="font-bold mt-4 mb-2">Timeline</h2>
  <ol class="text-sm space-y-1">
    <?php foreach (($history ?? []) as $h): ?><li><?= View::e($h['created_at']) ?> — <?= View::e($h['from_status'] ?? 'CREATED') ?> → <strong><?= View::e($h['to_status']) ?></strong><?php if ($h['note']): ?> (<?= View::e($h['note']) ?>)<?php endif; ?></li><?php endforeach; ?>
  </ol>
</section>
<section class="card p-4">
  <h2 class="font-bold mb-2">Pengiriman</h2>
  <?php if ($shipment): ?>
    <p class="text-sm">Kurir: <strong><?= View::e(strtoupper($shipment['courier'] . ' ' . $shipment['service'])) ?></strong></p>
    <p class="text-sm">Resi: <strong><?= View::e($shipment['waybill'] ?? '-') ?></strong></p>
    <?php if (!empty($shipment['waybill'])): ?><a class="text-sm underline" href="/account/orders/<?= View::e($order['order_code']) ?>/track">Perbarui pelacakan</a><?php endif; ?>
    <h3 class="font-semibold mt-3">Tracking</h3>
    <ul class="text-sm"><?php foreach (($events ?? []) as $ev): ?><li><?= View::e($ev['event_time']) ?> — <?= View::e($ev['description']) ?></li><?php endforeach; ?>
    <?php if (empty($events)): ?><li class="text-gray-500 text-sm">Belum ada event.</li><?php endif; ?></ul>
  <?php else: ?><p class="text-sm text-gray-500">Belum ada data kirim.</p><?php endif; ?>
  <div class="flex gap-2 mt-4">
    <?php if (in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REJECTED'], true)): ?>
      <a href="/orders/<?= View::e($order['order_code']) ?>/payment" class="btn-primary px-4 py-1 rounded text-sm">Bayar / Upload Bukti</a>
    <?php endif; ?>
    <?php if (in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REVIEW', 'PAYMENT_REJECTED'], true)): ?>
      <form method="post" action="/account/orders/<?= View::e($order['order_code']) ?>/cancel"><?= Csrf::field() ?><button class="text-sm text-red-600 underline" type="submit">Batalkan</button></form>
    <?php endif; ?>
    <form method="post" action="/account/orders/<?= View::e($order['order_code']) ?>/reorder"><?= Csrf::field() ?><button class="text-sm underline" type="submit">Beli Lagi</button></form>
  </div>
</section>
</div>
