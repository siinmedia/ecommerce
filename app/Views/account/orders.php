<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-4">Pesanan Saya</h1>
<?php if (empty($orders)): ?><div class="card p-6 text-center"><p>Belum ada pesanan.</p><a class="underline" href="/products">Belanja</a></div><?php endif; ?>
<div class="space-y-2">
<?php foreach (($orders ?? []) as $o): ?>
  <div class="card p-3 flex items-center gap-3">
    <div><a class="font-semibold underline" href="/account/orders/<?= View::e($o['order_code']) ?>"><?= View::e($o['order_code']) ?></a>
      <p class="text-sm text-gray-500"><?= View::e($o['created_at']) ?></p></div>
    <span class="flex-1"></span>
    <span class="badge badge-neutral"><?= View::e($o['status']) ?></span>
    <strong><?= Helpers::rupiah($o['grand_total']) ?></strong>
  </div>
<?php endforeach; ?>
</div>
