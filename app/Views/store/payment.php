<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-1">Pembayaran <?= View::e($order['order_code']) ?></h1>
<p class="mb-4"><span class="badge badge-pending"><?= View::e($order['status']) ?></span>
  <span class="text-sm text-gray-500">Batas bayar: <?= View::e($order['expires_at']) ?></span></p>
<div class="grid md:grid-cols-2 gap-6">
  <section class="card p-4">
    <h2 class="font-bold mb-2">Instruksi Transfer</h2>
    <dl class="text-sm space-y-2">
      <div><dt class="text-gray-500">Total transfer</dt><dd class="text-2xl font-bold"><?= Helpers::rupiah($order['grand_total']) ?></dd>
        <button data-copy="<?= (int)$order['grand_total'] ?>" class="text-xs underline">Salin nominal</button></div>
      <?php if ($bank): ?>
      <div><dt class="text-gray-500">Rekening tujuan</dt>
        <dd><strong><?= View::e($bank['bank_name']) ?> <?= View::e($bank['account_number']) ?></strong> a.n <?= View::e($bank['account_name']) ?></dd>
        <button data-copy="<?= View::e($bank['account_number']) ?>" class="text-xs underline">Salin norek</button></div>
      <?php endif; ?>
      <div><dt class="text-gray-500">Kode order</dt><dd><strong><?= View::e($order['order_code']) ?></strong></dd>
        <button data-copy="<?= View::e($order['order_code']) ?>" class="text-xs underline">Salin kode</button></div>
    </dl>
    <p class="text-sm text-gray-500 mt-3">Transfer dari bank mana pun, lalu unggah bukti di bawah. Cantumkan kode order di berita transfer bila memungkinkan.</p>
  </section>
  <section class="card p-4">
    <h2 class="font-bold mb-2">Bukti Pembayaran</h2>
    <?php foreach (($reviews ?? []) as $r): ?>
      <p class="text-sm mb-1"><span class="badge <?= $r['decision'] === 'PAID' ? 'badge-paid' : 'badge-rejected' ?>"><?= View::e($r['decision']) ?></span> <?= View::e($r['note']) ?></p>
    <?php endforeach; ?>
    <?php if (in_array($order['status'], ['PENDING_PAYMENT', 'PAYMENT_REJECTED'], true)): ?>
    <form method="post" action="/orders/<?= View::e($order['order_code']) ?>/payment/proof" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <label class="lbl" for="proof">File (jpg/png/webp/pdf, maks 3MB)</label>
      <input type="file" name="proof" id="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
      <button class="btn-primary px-4 py-2 rounded mt-2" type="submit">Unggah Bukti</button>
    </form>
    <?php else: ?>
      <p class="text-sm text-gray-500">Bukti sudah diproses (status: <?= View::e($order['status']) ?>).</p>
    <?php endif; ?>
    <h3 class="font-semibold mt-4 mb-1">Riwayat upload</h3>
    <ul class="text-sm">
      <?php foreach (($proofs ?? []) as $pf): ?>
        <li><?= View::e($pf['created_at']) ?> — <?= View::e($pf['filename']) ?> (<?= (int)($pf['size_bytes'] / 1024) ?> KB)</li>
      <?php endforeach; ?>
      <?php if (empty($proofs)): ?><li class="text-gray-500">Belum ada.</li><?php endif; ?>
    </ul>
  </section>
</div>
<section class="card p-4 mt-4">
  <h2 class="font-bold mb-2">Item</h2>
  <ul class="text-sm">
    <?php foreach (($items ?? []) as $it): ?>
      <li><?= View::e($it['product_name']) ?> (<?= View::e($it['variant_name']) ?>) x<?= (int)$it['qty'] ?> — <?= Helpers::rupiah($it['line_total']) ?></li>
    <?php endforeach; ?>
  </ul>
  <p class="text-sm mt-2">Subtotal <?= Helpers::rupiah($order['subtotal']) ?> | Diskon <?= Helpers::rupiah($order['discount']) ?> | Ongkir <?= Helpers::rupiah($order['shipping_cost']) ?> (<?= View::e(strtoupper($order['courier'] . ' ' . $order['courier_service'])) ?>)</p>
</section>
