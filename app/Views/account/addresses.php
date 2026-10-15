<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold mb-4">Alamat Saya</h1>
<div class="space-y-2">
<?php foreach (($addresses ?? []) as $a): ?>
  <div class="card p-3 text-sm"><strong><?= View::e($a['recipient']) ?></strong> <?= $a['is_default'] ? '<span class="badge badge-success">Utama</span>' : '' ?><br>
  <?= View::e($a['address_line']) ?>, <?= View::e($a['city_label']) ?> <?= View::e($a['postal_code']) ?><br><?= View::e($a['phone']) ?></div>
<?php endforeach; ?>
<?php if (empty($addresses)): ?><p class="text-gray-500">Belum ada alamat. Tambah saat checkout.</p><?php endif; ?>
</div>
