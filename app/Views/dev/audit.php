<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold mb-4">Audit Teknis (read-only)</h1>
<table class="data card"><thead><tr><th>ID</th><th>Waktu</th><th>Actor</th><th>Aksi</th><th>Entitas</th></tr></thead><tbody>
<?php foreach (($logs ?? []) as $l): ?>
<tr><td><?= (int)$l['id'] ?></td><td class="text-xs"><?= View::e($l['created_at']) ?></td>
<td class="text-xs"><?= View::e($l['actor_email'] ?? '-') ?> (<?= View::e($l['actor_role']) ?>)</td>
<td><?= View::e($l['action']) ?></td><td><?= View::e($l['entity']) ?>#<?= View::e((string)($l['entity_id'] ?? '-')) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($logs)): ?><tr><td colspan="5" class="text-gray-500">Belum ada log.</td></tr><?php endif; ?>
</tbody></table>
