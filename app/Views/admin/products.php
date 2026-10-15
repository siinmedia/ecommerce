<?php use App\Core\View; use App\Core\Helpers; ?>
<div class="flex items-center mb-4"><h1 class="text-2xl font-bold">Produk</h1><span class="flex-1"></span><a href="/admin/products/new" class="btn-primary px-4 py-1 rounded text-sm">+ Tambah</a></div>
<table class="data card"><thead><tr><th>ID</th><th>SKU</th><th>Nama</th><th>Harga</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach (($products ?? []) as $p): ?>
<tr><td><?= (int)$p['id'] ?></td><td><?= View::e($p['sku']) ?></td><td><?= View::e($p['name']) ?></td>
<td><?= Helpers::rupiah($p['price']) ?></td><td><?= View::e($p['status']) ?></td>
<td><a class="underline text-sm" href="/admin/products/<?= (int)$p['id'] ?>/edit">Edit</a></td></tr>
<?php endforeach; ?>
</tbody></table>
