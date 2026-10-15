<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-4">Pesanan</h1>
<form method="get" action="/admin/orders" class="flex gap-2 mb-3">
  <input type="text" name="q" placeholder="Cari kode/email" value="<?= View::e($filters['q'] ?? '') ?>" style="max-width:220px">
  <select name="status" style="max-width:200px">
    <option value="">Semua status</option>
    <?php foreach (['PENDING_PAYMENT','PAYMENT_REVIEW','PAYMENT_REJECTED','PAID','PROCESSING','SHIPPED','DELIVERED','COMPLETED','EXPIRED','CANCELLED'] as $s): ?>
      <option value="<?= $s ?>" <?= (($filters['status'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <button class="px-3 py-1 border rounded text-sm" type="submit">Filter</button>
</form>
<table class="data card"><thead><tr><th>Kode</th><th>User</th><th>Total</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>
<?php foreach (($orders ?? []) as $o): ?>
<tr><td><a class="underline" href="/admin/orders/<?= View::e($o['order_code']) ?>"><?= View::e($o['order_code']) ?></a></td>
<td><?= View::e($o['user_email']) ?></td><td><?= Helpers::rupiah($o['grand_total']) ?></td><td><?= View::e($o['status']) ?></td><td class="text-xs"><?= View::e($o['created_at']) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($orders)): ?><tr><td colspan="5" class="text-gray-500">Tidak ada order.</td></tr><?php endif; ?>
</tbody></table>
