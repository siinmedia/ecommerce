<?php use App\Core\View; use App\Core\Helpers; ?>
<div class="wrap page">
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em" class="mb-3">Pesanan Saya</h1>
  <div class="acc">
    <nav class="accnav" aria-label="Menu akun">
      <a href="/account/orders" class="is-active" aria-current="page">Pesanan Saya</a>
      <a href="/account/addresses">Alamat Saya</a>
      <a href="/account/profile">Profil</a>
      <a href="/account/2fa">Keamanan 2FA</a>
    </nav>
    <div>
      <?php if (empty($orders)): ?>
        <div class="empty"><h2>Belum ada pesanan</h2><p class="muted mb-3">Mulai belanja dan lacak semuanya di sini.</p><a href="/products" class="btn btn--buy">Belanja</a></div>
      <?php endif; ?>
      <?php foreach (($orders ?? []) as $o): ?>
        <div class="order-row">
          <div class="order-row__main">
            <a class="order-row__code" href="/account/orders/<?= View::e($o['order_code']) ?>"><?= View::e($o['order_code']) ?></a>
            <p class="muted" style="font-size:13.5px"><?= View::e($o['created_at']) ?></p>
          </div>
          <span class="badge badge-neutral"><?= View::e($o['status']) ?></span>
          <strong><?= Helpers::rupiah($o['grand_total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
