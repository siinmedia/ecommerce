<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Rekening Bank</h1>
<div class="grid md:grid-cols-2 gap-4">
<div>
<table class="data card"><thead><tr><th>Bank</th><th>Norek</th><th>Pemilik</th><th>Aktif</th></tr></thead><tbody>
<?php foreach (($banks ?? []) as $b): ?>
<tr><td><?= View::e($b['bank_name']) ?></td><td><?= View::e($b['account_number']) ?></td><td><?= View::e($b['account_name']) ?></td><td><?= $b['is_active'] ? 'Ya' : 'Tidak' ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<form method="post" action="/admin/banks/save" class="card p-4 space-y-2">
  <?= Csrf::field() ?>
  <input type="hidden" name="id" value="0">
  <div><label class="lbl">Bank</label><input type="text" name="bank_name" required placeholder="BCA"></div>
  <div><label class="lbl">Nomor rekening</label><input type="text" name="account_number" required></div>
  <div><label class="lbl">Nama pemilik</label><input type="text" name="account_name" required></div>
  <label class="text-sm"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
  <button class="btn-primary px-4 py-1 rounded text-sm" type="submit">Tambah Rekening</button>
</form>
</div>
