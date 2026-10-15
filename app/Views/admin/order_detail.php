<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-1">Order <?= View::e($order['order_code']) ?></h1>
<p class="mb-4"><span class="badge badge-neutral"><?= View::e($order['status']) ?></span> Total <strong><?= Helpers::rupiah($order['grand_total']) ?></strong></p>
<div class="grid md:grid-cols-2 gap-4">
<section class="card p-4">
  <h2 class="font-bold mb-2">Item & Alamat</h2>
  <ul class="text-sm"><?php foreach (($items ?? []) as $it): ?><li><?= View::e($it['product_name']) ?> (<?= View::e($it['variant_name']) ?>) x<?= (int)$it['qty'] ?> — <?= Helpers::rupiah($it['line_total']) ?></li><?php endforeach; ?></ul>
  <?php if (!empty($address)): ?><p class="text-sm mt-2"><strong><?= View::e($address['recipient']) ?></strong> <?= View::e($address['phone']) ?><br><?= View::e($address['address_line']) ?>, <?= View::e($address['city_label']) ?></p><?php endif; ?>
  <p class="text-sm mt-2">Kirim: <?= View::e(strtoupper($order['courier'] . ' ' . $order['courier_service'])) ?> — <?= Helpers::rupiah($order['shipping_cost']) ?><?php if ($shipment && !empty($shipment['waybill'])): ?> | Resi: <strong><?= View::e($shipment['waybill']) ?></strong><?php endif; ?></p>
  <h2 class="font-bold mt-4 mb-1">Timeline</h2>
  <ol class="text-sm"><?php foreach (($history ?? []) as $h): ?><li><?= View::e($h['created_at']) ?> — <?= View::e($h['from_status'] ?? '-') ?> → <strong><?= View::e($h['to_status']) ?></strong> <?= View::e($h['note'] ?? '') ?></li><?php endforeach; ?></ol>
</section>
<section class="card p-4">
  <h2 class="font-bold mb-2">Bukti Pembayaran</h2>
  <?php foreach (($proofs ?? []) as $pf): ?>
    <p class="text-sm"><a class="underline" href="/proof/<?= (int)$pf['id'] ?>" target="_blank"><?= View::e($pf['filename']) ?></a> (<?= View::e($pf['created_at']) ?>)</p>
  <?php endforeach; ?>
  <?php if (empty($proofs)): ?><p class="text-sm text-gray-500">Belum ada bukti.</p><?php endif; ?>
  <?php foreach (($reviews ?? []) as $r): ?>
    <p class="text-sm mt-1"><span class="badge badge-neutral"><?= View::e($r['decision']) ?></span> oleh <?= View::e($r['admin_email'] ?? '-') ?>: <?= View::e($r['note']) ?></p>
  <?php endforeach; ?>
  <?php if ($order['status'] === 'PAYMENT_REVIEW'): ?>
  <form method="post" action="/admin/orders/<?= View::e($order['order_code']) ?>/verify" class="mt-3 space-y-2">
    <?= Csrf::field() ?>
    <textarea name="note" placeholder="Catatan / alasan tolak (wajib jika tolak)"></textarea>
    <div class="flex gap-2">
      <button name="decision" value="approve" class="btn-primary px-4 py-1 rounded text-sm" type="submit" onclick="return confirm('Setujui pembayaran ini?')">Setujui (PAID)</button>
      <button name="decision" value="reject" class="px-4 py-1 rounded text-sm border border-red-400 text-red-600" type="submit">Tolak</button>
    </div>
  </form>
  <?php endif; ?>
  <?php if (!empty($transitions)): ?>
  <h2 class="font-bold mt-4 mb-2">Ubah Status</h2>
  <form method="post" action="/admin/orders/<?= View::e($order['order_code']) ?>/transition" class="space-y-2">
    <?= Csrf::field() ?>
    <select name="to"><?php foreach ($transitions as $t): ?><option value="<?= View::e($t) ?>"><?= View::e($t) ?></option><?php endforeach; ?></select>
    <input type="text" name="waybill" placeholder="No resi (wajib untuk SHIPPED)">
    <input type="text" name="note" placeholder="Catatan">
    <button class="px-4 py-1 border rounded text-sm" type="submit">Terapkan</button>
  </form>
  <?php endif; ?>
</section>
</div>
