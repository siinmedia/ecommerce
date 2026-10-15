<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Keranjang</h1>
<?php if (empty($cart['lines'])): ?>
  <div class="card p-6 text-center"><p>Keranjang kosong.</p><a href="/products" class="underline">Belanja sekarang</a></div>
<?php else: ?>
<table class="data card">
  <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cart['lines'] as $l): ?>
    <tr>
      <td><a class="underline" href="/products/<?= View::e($l['product_slug']) ?>"><?= View::e($l['product_name']) ?></a><br><span class="text-sm text-gray-500"><?= View::e($l['variant_name']) ?></span>
        <?php if ($l['over_stock']): ?><br><span class="badge badge-failed">Stok sisa <?= (int)$l['stock'] ?></span><?php endif; ?></td>
      <td><?= Helpers::rupiah($l['price']) ?></td>
      <td>
        <form method="post" action="/cart/update" class="flex gap-1">
          <?= Csrf::field() ?>
          <input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>">
          <input type="number" name="qty" value="<?= (int)$l['qty'] ?>" min="0" max="99" style="width:70px">
          <button class="text-sm underline" type="submit">Ubah</button>
        </form>
      </td>
      <td><?= Helpers::rupiah($l['line_total']) ?></td>
      <td>
        <form method="post" action="/cart/update"><?= Csrf::field() ?>
          <input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>"><input type="hidden" name="qty" value="0">
          <button class="text-sm text-red-600" type="submit">Hapus</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="flex justify-between items-center mt-4">
  <form method="post" action="/cart/clear"><?= Csrf::field() ?><button class="text-sm text-gray-500 underline" type="submit">Kosongkan</button></form>
  <div class="text-right">
    <p class="text-lg">Subtotal: <strong><?= Helpers::rupiah($cart['subtotal']) ?></strong></p>
    <a href="/checkout" class="btn-primary px-6 py-2 rounded inline-block mt-2">Checkout</a>
  </div>
</div>
<?php endif; ?>
