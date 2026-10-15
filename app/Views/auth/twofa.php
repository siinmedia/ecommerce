<?php use App\Core\View; use App\Core\Csrf; ?>
<div class="wrap page">
  <div class="form-narrow">
    <h1 class="form-title">Verifikasi 2FA</h1>
    <p class="form-sub">Masukkan 6 digit kode dari aplikasi authenticator, atau salah satu backup code.</p>
    <form method="post" action="/login/2fa" class="form-card">
      <?= Csrf::field() ?>
      <div class="field">
        <label class="lbl" for="code">Kode verifikasi <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="text" name="code" id="code" required autocomplete="one-time-code" inputmode="numeric" placeholder="123456" maxlength="16">
      </div>
      <button class="btn btn--buy btn--block" type="submit">Verifikasi</button>
    </form>
  </div>
</div>
