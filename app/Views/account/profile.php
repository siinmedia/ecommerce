<?php use App\Core\View; use App\Core\Csrf; ?>
<div class="wrap page">
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em" class="mb-3">Profil</h1>
  <div class="acc">
    <nav class="accnav" aria-label="Menu akun">
      <a href="/account/orders">Pesanan Saya</a>
      <a href="/account/addresses">Alamat Saya</a>
      <a href="/account/profile" class="is-active" aria-current="page">Profil</a>
      <a href="/account/2fa">Keamanan 2FA</a>
    </nav>
    <div>
      <form method="post" action="/account/profile" class="form-card" style="max-width:560px">
        <?= Csrf::field() ?>
        <div class="field">
          <label class="lbl" for="pf-name">Nama <span class="req" aria-hidden="true">*</span></label>
          <input class="input" type="text" id="pf-name" name="name" required autocomplete="name" value="<?= View::e($profile['name'] ?? '') ?>">
        </div>
        <div class="field">
          <label class="lbl" for="pf-email">Email</label>
          <input class="input" type="email" id="pf-email" value="<?= View::e($profile['email'] ?? '') ?>" disabled>
          <span class="field__hint">Email tidak dapat diubah.</span>
        </div>
        <div class="field">
          <label class="lbl" for="pf-phone">No HP</label>
          <input class="input" type="text" id="pf-phone" name="phone" autocomplete="tel" inputmode="tel" value="<?= View::e($profile['phone'] ?? '') ?>">
        </div>
        <button class="btn btn--buy" type="submit">Simpan</button>
      </form>
    </div>
  </div>
</div>
