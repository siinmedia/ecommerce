<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-1">Verifikasi Pembayaran</h1>
<p class="text-sm text-gray-500 mb-4">Locking berbasis database: order yang sudah diproses tidak bisa diverifikasi ulang (optimistic lock + conditional update).</p>
<?php if (empty($orders)): ?><div class="card p-6 text-center text-gray-500">Antrean kosong. Semua bukti sudah diproses.</div><?php endif; ?>
<div class="space-y-2">
<?php foreach (($orders ?? []) as $o): ?>
  <div class="card p-3 flex items-center gap-3">
    <div><a class="font-semibold underline" href="/admin/orders/<?= View::e($o['order_code']) ?>"><?= View::e($o['order_code']) ?></a>
      <p class="text-sm text-gray-500"><?= View::e($o['user_name']) ?> (<?= View::e($o['user_email']) ?>) — <?= (int)$o['proof_count'] ?> bukti</p></div>
    <span class="flex-1"></span>
    <strong><?= Helpers::rupiah($o['grand_total']) ?></strong>
    <a href="/admin/orders/<?= View::e($o['order_code']) ?>" class="btn-primary px-3 py-1 rounded text-sm">Periksa</a>
  </div>
<?php endforeach; ?>
</div>
