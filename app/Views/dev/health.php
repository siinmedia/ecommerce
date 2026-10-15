<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold mb-4">System Health</h1>
<div class="grid md:grid-cols-2 gap-3">
<?php foreach (($checks ?? []) as $name => $c): ?>
  <div class="card p-3"><p class="font-semibold"><?= View::e($name) ?> <?= $c['ok'] ? '<span class="badge badge-success">OK</span>' : '<span class="badge badge-failed">FAIL</span>' ?></p>
  <p class="text-sm text-gray-600"><?= View::e($c['detail']) ?></p></div>
<?php endforeach; ?>
</div>
<h2 class="font-bold mt-4 mb-2">Counts</h2>
<ul class="text-sm"><?php foreach (($counts ?? []) as $k => $v): ?><li><?= View::e($k) ?>: <?= (int)$v ?></li><?php endforeach; ?></ul>
<p class="text-xs text-gray-500 mt-4">API key dan data pribadi tidak pernah ditampilkan di console ini.</p>
