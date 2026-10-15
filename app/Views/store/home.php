<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\View;
// Peta varian pertama per produk untuk tombol quick-add (tanpa ubah controller).
$firstVariant = [];
try {
  $ids = array_map(fn($p) => (int)$p['id'], $products ?? []);
  $ids = array_values(array_filter($ids));
  if ($ids) {
    $in = implode(',', $ids);
    $st = \App\Core\Database::pdo()->query(
      "SELECT product_id, MIN(id) AS vid FROM product_variants WHERE product_id IN ({$in}) AND status = 'active' GROUP BY product_id"
    );
    foreach ($st->fetchAll() as $r) { $firstVariant[(int)$r['product_id']] = (int)$r['vid']; }
  }
} catch (\Throwable) { $firstVariant = []; }
$card = function (array $p) use ($firstVariant): void {
  $initial = mb_strtoupper(mb_substr(trim($p['name']), 0, 1));
  $disc = !empty($p['compare_price']) && (int)$p['compare_price'] > (int)$p['price']
    ? (int)round((1 - (int)$p['price'] / (int)$p['compare_price']) * 100) : 0;
  $vid = $firstVariant[(int)$p['id']] ?? null;
?>
  <article class="p-card">
    <a class="p-media" href="/products/<?= View::e($p['slug']) ?>" aria-label="<?= View::e($p['name']) ?>">
      <span class="p-ph" aria-hidden="true"><span class="p-ph__mark"><?= View::e($initial) ?></span><span class="p-ph__dots"></span></span>
      <?php if ($disc > 0): ?><span class="p-disc">-<?= (int)$disc ?>%</span><?php endif; ?>
    </a>
    <div class="p-body">
      <span class="p-cat"><?= View::e($p['category_name'] ?? '') ?></span>
      <a class="p-name" href="/products/<?= View::e($p['slug']) ?>"><?= View::e($p['name']) ?></a>
      <span class="p-price"><?= Helpers::rupiah($p['price']) ?></span>
      <?php if (!empty($p['compare_price']) && (int)$p['compare_price'] > (int)$p['price']): ?>
        <span class="p-was"><?= Helpers::rupiah($p['compare_price']) ?></span>
      <?php endif; ?>
      <div class="p-foot">
        <?php if ($vid): ?>
        <form method="post" action="/cart/add">
          <?= Csrf::field() ?>
          <input type="hidden" name="variant_id" value="<?= (int)$vid ?>">
          <input type="hidden" name="qty" value="1">
          <button class="btn btn--buy btn--sm btn--block" type="submit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Keranjang
          </button>
        </form>
        <?php else: ?>
          <a class="btn btn--ghost btn--sm btn--block" href="/products/<?= View::e($p['slug']) ?>">Lihat Detail</a>
        <?php endif; ?>
      </div>
    </div>
  </article>
<?php }; ?>

<section class="hero" aria-labelledby="hero-h">
  <div class="wrap hero__inner">
    <div class="hero__copy">
      <span class="eyebrow">Toko Online Terpercaya</span>
      <h1 class="hero__title" id="hero-h">Belanja Mudah, <span class="hl">Bayar Transfer,</span> Sampai Aman</h1>
      <p class="lead">Produk pilihan original dengan harga jelas. Bayar via transfer bank, unggah bukti, pesanan diverifikasi dan dikirim ke seluruh Indonesia.</p>
      <div class="hero__actions">
        <a href="/products" class="btn btn--buy btn--lg">Belanja Sekarang</a>
        <a href="/products?sort=price_asc" class="btn btn--ghost btn--lg">Lihat Promo</a>
      </div>
      <div class="hero__stats" role="list" aria-label="Keunggulan toko">
        <div role="listitem"><span class="hero__stat-num">100%</span><span class="hero__stat-label">Produk original</span></div>
        <div role="listitem"><span class="hero__stat-num">24 jam</span><span class="hero__stat-label">Verifikasi bayar</span></div>
        <div role="listitem"><span class="hero__stat-num">34</span><span class="hero__stat-label">Provinsi terjangkau</span></div>
      </div>
    </div>
    <div class="hero__media">
      <div class="hero__frame" role="img" aria-label="Ilustrasi paket belanja toko">
        <div class="hero__pattern" aria-hidden="true"><span class="hero__pattern-mark"><?= View::e(mb_substr($siteName ?? 'T', 0, 1)) ?></span></div>
        <span class="hero__bar" aria-hidden="true"></span>
        <span class="hero__corner" aria-hidden="true"></span>
      </div>
      <div class="hero__badge">
        <span class="hero__badge-icon" aria-hidden="true">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v5c0 5 3.4 8.4 7 10 3.6-1.6 7-5 7-10V6z"/><path d="m9.5 12 2 2 3.5-4"/></svg>
        </span>
        <span><span class="hero__badge-title">Transaksi Aman</span><span class="hero__badge-text">Transfer manual + verifikasi admin.</span></span>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($categories)): ?>
<section class="sec" aria-labelledby="cat-h">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow">Kategori</span>
      <h2 class="sec-title" id="cat-h">Belanja per Kategori</h2>
    </div>
    <nav class="grid grid--cats" aria-label="Kategori produk">
      <?php foreach ($categories as $c): ?>
        <a class="cat-card" href="/categories/<?= View::e($c['slug']) ?>">
          <span class="cat-card__icon" aria-hidden="true">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
          </span>
          <span><?= View::e($c['name']) ?><small>Lihat produk</small></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</section>
<?php endif; ?>

<section class="sec sec--alt" aria-labelledby="feat-h">
  <div class="wrap">
    <div class="sec-head spread">
      <div><span class="eyebrow">Pilihan Teratas</span>
      <h2 class="sec-title" id="feat-h">Produk Unggulan</h2></div>
      <a href="/products" class="btn btn--ghost btn--sm">Semua Produk</a>
    </div>
    <?php if (!empty($products)): ?>
      <div class="grid grid--prods">
        <?php foreach ($products as $p) { $card($p); } ?>
      </div>
    <?php else: ?>
      <div class="empty"><h2>Belum ada produk</h2><p class="muted">Stok sedang disiapkan. Kembali lagi nanti.</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="sec" aria-labelledby="promo-h">
  <div class="wrap">
    <div class="promo">
      <span class="eyebrow" style="color:var(--cta)">Transfer Manual</span>
      <h2 id="promo-h">Bayar Transfer, Tanpa Ribet</h2>
      <p>Checkout, transfer ke rekening resmi toko, lalu unggah bukti pembayaran. Admin memverifikasi maksimal 1x24 jam dan pesanan langsung diproses.</p>
      <div class="promo__steps">
        <div class="promo__step"><strong>Langkah 1</strong>Pilih produk, isi alamat, pilih kurir.</div>
        <div class="promo__step"><strong>Langkah 2</strong>Transfer tepat sesuai nominal ke BCA / Mandiri.</div>
        <div class="promo__step"><strong>Langkah 3</strong>Unggah bukti, pantau status di akun.</div>
      </div>
      <a href="/products" class="btn btn--buy btn--lg">Mulai Belanja</a>
    </div>
  </div>
</section>

<section class="sec sec--alt" aria-labelledby="usp-h">
  <div class="wrap">
    <div class="sec-head"><span class="eyebrow">Kenapa Kami</span>
    <h2 class="sec-title" id="usp-h">Belanja Tenang di Sini</h2></div>
    <div class="usp">
      <div class="usp__item">
        <span class="usp__icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v5c0 5 3.4 8.4 7 10 3.6-1.6 7-5 7-10V6z"/><path d="m9.5 12 2 2 3.5-4"/></svg></span>
        <div><h3>100% Original</h3><p>Semua produk bergaransi keaslian. Cacat datang? Tukar baru.</p></div>
      </div>
      <div class="usp__item">
        <span class="usp__icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg></span>
        <div><h3>Packing Aman</h3><p>Bubble wrap + kardus tebal untuk setiap paket elektronik dan pecah belah.</p></div>
      </div>
      <div class="usp__item">
        <span class="usp__icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13a8 8 0 0 1 16 0"/><rect x="3" y="13" width="4" height="7" rx="1.5"/><rect x="17" y="13" width="4" height="7" rx="1.5"/><path d="M20 20a4 4 0 0 1-4 2h-2"/></svg></span>
        <div><h3>CS Responsif</h3><p>Butuh bantuan order atau lacak paket? Tim kami siap setiap hari kerja.</p></div>
      </div>
    </div>
  </div>
</section>
