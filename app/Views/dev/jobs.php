<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Background Jobs</h1>
<table class="data card"><thead><tr><th>ID</th><th>Type</th><th>Ref</th><th>Status</th><th>Attempts</th><th>Error</th><th></th></tr></thead><tbody>
<?php foreach (($rows ?? []) as $j): ?>
<tr><td><?= (int)$j['id'] ?></td><td><?= View::e($j['job_type']) ?></td><td><?= View::e((string)$j['ref_id']) ?></td>
<td><?= View::e($j['status']) ?></td><td><?= (int)$j['attempts'] ?>/<?= (int)$j['max_attempts'] ?></td>
<td class="text-xs"><?= View::e(mb_substr($j['last_error'] ?? '', 0, 80)) ?></td>
<td><?php if (in_array($j['status'], ['failed', 'dead'], true)): ?>
<form method="post" action="/dev/jobs/<?= (int)$j['id'] ?>/retry"><?= Csrf::field() ?><button class="text-xs underline" type="submit">Retry</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-gray-500">Tidak ada job.</td></tr><?php endif; ?>
</tbody></table>
