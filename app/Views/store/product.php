<?php use App\Core\View; use App\Core\Helpers; use App\Core\Csrf; ?>
<nav class="text-sm text-gray-500 mb-3" aria-label="Breadcrumb">
  <a href="/">Beranda</a> / <a href="/products">Produk</a> /
  <?php if (!empty($product['category_slug'])): ?><a href="/categories/<?= View::e($product['category_slug']) ?>"><?= View::e($product['category_name']) ?></a> /<?php endif; ?>
  <span><?= View::e($product['name']) ?></span>
</nav>
<div class="grid md:grid-cols-2 gap-6">
  <div class="card p-6">
    <h1 class="text-2xl font-bold"><?= View::e($product['name']) ?></h1>
    <p class="text-sm text-gray-500">SKU: <?= View::e($product['sku']) ?> | Kategori: <?= View::e($product['category_name'] ?? '-') ?></p>
    <p class="text-3xl font-bold text-blue-700 my-3" id="pd-price"><?= Helpers::rupiah($product['price']) ?></p>
    <div class="prose max-w-none text-sm"><?= nl2br(View::e($product['description'])) ?></div>
    <script type="application/ld+json">
    <?= json_encode([
      '@context' => 'https://schema.org', '@type' => 'Product',
      'name' => $product['name'], 'sku' => $product['sku'],
      'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IDR', 'price' => (int)$product['price'], 'availability' => 'https://schema.org/InStock'],
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    </script>
  </div>
  <div class="card p-6">
    <form method="post" action="/cart/add">
      <?= Csrf::field() ?>
      <label class="lbl" for="variant_id">Variasi</label>
      <select name="variant_id" id="variant_id" required>
        <?php foreach (($product['variants'] ?? []) as $v): ?>
          <option value="<?= (int)$v['id'] ?>" data-price="<?= (int)$v['price'] ?>" data-stock="<?= (int)$v['stock'] ?>">
            <?= View::e($v['name']) ?> — <?= Helpers::rupiah($v['price']) ?> (stok <?= (int)$v['stock'] ?>)
          </option>
        <?php endforeach; ?>
        <?php if (empty($product['variants'])): ?>
          <option value="" disabled>Stok habis</option>
        <?php endif; ?>
      </select>
      <label class="lbl mt-3" for="qty">Jumlah</label>
      <input type="number" name="qty" id="qty" value="1" min="1" max="99" required>
      <button type="submit" class="btn-primary px-4 py-2 rounded mt-4 w-full" <?= empty($product['variants']) ? 'disabled' : '' ?>>Tambah ke Keranjang</button>
    </form>
  </div>
</div>
