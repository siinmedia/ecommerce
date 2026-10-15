<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4"><?= View::e($title ?? 'Produk') ?></h1>
<form method="post" action="/admin/products/save" class="space-y-3 max-w-2xl">
  <?= Csrf::field() ?>
  <input type="hidden" name="id" value="<?= (int)($product['id'] ?? 0) ?>">
  <div><label class="lbl">Nama</label><input type="text" name="name" required value="<?= View::e($product['name'] ?? '') ?>"></div>
  <div class="grid grid-cols-2 gap-2">
    <div><label class="lbl">Slug (kosongkan = otomatis)</label><input type="text" name="slug" value="<?= View::e($product['slug'] ?? '') ?>"></div>
    <div><label class="lbl">SKU (unik)</label><input type="text" name="sku" required value="<?= View::e($product['sku'] ?? '') ?>"></div>
  </div>
  <div><label class="lbl">Kategori</label>
    <select name="category_id"><option value="0">—</option>
    <?php foreach (($categories ?? []) as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
    <?php endforeach; ?></select></div>
  <div><label class="lbl">Deskripsi</label><textarea name="description" rows="4"><?= View::e($product['description'] ?? '') ?></textarea></div>
  <div class="grid grid-cols-3 gap-2">
    <div><label class="lbl">Harga</label><input type="number" name="price" required min="0" value="<?= View::e((string)($product['price'] ?? '')) ?>"></div>
    <div><label class="lbl">Harga coret (opsional)</label><input type="number" name="compare_price" min="0" value="<?= View::e((string)($product['compare_price'] ?? '')) ?>"></div>
    <div><label class="lbl">Berat (gram)</label><input type="number" name="weight_gram" min="0" value="<?= View::e((string)($product['weight_gram'] ?? '0')) ?>"></div>
  </div>
  <div><label class="lbl">Status</label>
    <select name="status">
      <?php foreach (['draft', 'published', 'archived'] as $s): ?>
        <option value="<?= $s ?>" <?= (($product['status'] ?? 'draft') === $s) ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?></select></div>
  <h2 class="font-bold pt-2">Varian (nama | SKU | harga | stok | berat gr)</h2>
  <div id="variants" class="space-y-1">
    <?php foreach (($variants ?? []) as $vn): ?>
      <div class="grid grid-cols-5 gap-1">
        <input type="hidden" name="vid[]" value="<?= (int)$vn['id'] ?>">
        <input type="text" name="vname[]" value="<?= View::e($vn['name']) ?>" placeholder="Nama">
        <input type="text" name="vsku[]" value="<?= View::e($vn['sku']) ?>" placeholder="SKU">
        <input type="number" name="vprice[]" value="<?= (int)$vn['price'] ?>" placeholder="Harga">
        <input type="number" name="vstock[]" value="<?= (int)$vn['stock'] ?>" placeholder="Stok">
      </div>
    <?php endforeach; ?>
    <div class="grid grid-cols-5 gap-1">
      <input type="hidden" name="vid[]" value="0">
      <input type="text" name="vname[]" placeholder="Nama varian baru">
      <input type="text" name="vsku[]" placeholder="SKU">
      <input type="number" name="vprice[]" placeholder="Harga">
      <input type="number" name="vstock[]" placeholder="Stok">
    </div>
  </div>
  <button class="btn-primary px-6 py-2 rounded" type="submit">Simpan</button>
</form>
<?php if (!empty($product)): ?>
<section class="card p-4 mt-6 max-w-2xl">
  <h2 class="font-bold mb-2">Penyesuaian Stok (tercatat di ledger)</h2>
  <?php foreach (($variants ?? []) as $vn): ?>
  <form method="post" action="/admin/stock/adjust" class="flex gap-1 items-center text-sm mb-1">
    <?= Csrf::field() ?>
    <input type="hidden" name="variant_id" value="<?= (int)$vn['id'] ?>">
    <span class="w-40"><?= View::e($vn['name']) ?> (<?= (int)$vn['stock'] ?>)</span>
    <input type="number" name="delta" placeholder="+/-" style="width:80px" required>
    <input type="text" name="reason" placeholder="Alasan" required>
    <button class="underline" type="submit">Sesuaikan</button>
  </form>
  <?php endforeach; ?>
</section>
<?php endif; ?>
