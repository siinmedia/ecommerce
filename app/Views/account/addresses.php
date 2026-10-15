<?php use App\Core\View; ?>
<div class="wrap page">
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em" class="mb-3">Alamat Saya</h1>
  <div class="acc">
    <nav class="accnav" aria-label="Menu akun">
      <a href="/account/orders">Pesanan Saya</a>
      <a href="/account/addresses" class="is-active" aria-current="page">Alamat Saya</a>
      <a href="/account/profile">Profil</a>
      <a href="/account/2fa">Keamanan 2FA</a>
    </nav>
    <div>
      <?php foreach (($addresses ?? []) as $a): ?>
        <div class="order-row">
          <div class="order-row__main" style="font-size:14.5px">
            <strong><?= View::e($a['recipient']) ?></strong>
            <?= $a['is_default'] ? '<span class="badge badge-success">Utama</span>' : '' ?><br>
            <span><?= View::e($a['address_line']) ?>, <?= View::e($a['city_label']) ?> <?= View::e($a['postal_code']) ?></span><br>
            <span class="muted"><?= View::e($a['phone']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($addresses)): ?>
        <div class="empty"><h2>Belum ada alamat</h2><p class="muted">Tambah alamat saat checkout.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
