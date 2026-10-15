<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold mb-4">Integration Logs</h1>
<form method="get" action="/dev/logs" class="flex gap-2 mb-3">
  <input type="text" name="correlation" placeholder="Cari correlation/endpoint" value="<?= View::e($correlation ?? '') ?>" style="max-width:280px">
  <button class="px-3 py-1 border rounded text-sm" type="submit">Cari</button>
</form>
<table class="data card"><thead><tr><th>ID</th><th>Provider</th><th>Endpoint</th><th>Correlation</th><th>HTTP</th><th>Waktu</th></tr></thead><tbody>
<?php foreach (($rows ?? []) as $r): ?>
<tr><td><?= (int)$r['id'] ?></td><td><?= View::e($r['provider']) ?></td><td class="text-xs"><?= View::e($r['endpoint']) ?></td>
<td class="text-xs"><?= View::e($r['correlation_id']) ?></td><td><?= View::e((string)$r['response_code']) ?></td><td class="text-xs"><?= View::e($r['created_at']) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?><tr><td colspan="6" class="text-gray-500">Belum ada request tercatat (mode mock tidak memanggil API eksternal).</td></tr><?php endif; ?>
</tbody></table>
