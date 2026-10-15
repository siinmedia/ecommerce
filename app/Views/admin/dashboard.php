<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-4">Dashboard Operasional</h1>
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
  <div class="card p-3"><p class="text-sm text-gray-500">Menunggu bayar</p><p class="text-2xl font-bold"><?= (int)$stats['pending_payment'] ?></p></div>
  <div class="card p-3"><p class="text-sm text-gray-500">Verifikasi</p><p class="text-2xl font-bold"><?= (int)$stats['payment_review'] ?></p></div>
  <div class="card p-3"><p class="text-sm text-gray-500">Siap proses</p><p class="text-2xl font-bold"><?= (int)$stats['paid'] ?></p></div>
  <div class="card p-3"><p class="text-sm text-gray-500">Revenue valid</p><p class="text-lg font-bold"><?= Helpers::rupiah($stats['revenue']) ?></p></div>
  <div class="card p-3"><p class="text-sm text-gray-500">Stok rendah</p><p class="text-2xl font-bold"><?= (int)$stats['low_stock'] ?></p></div>
</div>
<h2 class="font-bold mb-2">Order Terbaru</h2>
<table class="data card"><thead><tr><th>Kode</th><th>User</th><th>Total</th><th>Status</th></tr></thead><tbody>
<?php foreach (($recent ?? []) as $o): ?>
<tr><td><a class="underline" href="/admin/orders/<?= View::e($o['order_code']) ?>"><?= View::e($o['order_code']) ?></a></td>
<td><?= View::e($o['user_email']) ?></td><td><?= Helpers::rupiah($o['grand_total']) ?></td><td><?= View::e($o['status']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
