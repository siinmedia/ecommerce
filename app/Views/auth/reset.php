<?php use App\Core\View; use App\Core\Csrf; ?>
<div class="wrap page">
  <div class="form-narrow">
    <h1 class="form-title">Reset Password</h1>
    <p class="form-sub">Buat password baru untuk akun Anda.</p>
    <form method="post" action="/reset-password" class="form-card">
      <?= Csrf::field() ?>
      <input type="hidden" name="selector" value="<?= View::e($selector ?? '') ?>">
      <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
      <div class="field">
        <label class="lbl" for="password">Password baru (min 8 karakter) <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="password" name="password" id="password" required minlength="8" autocomplete="new-password">
      </div>
      <button class="btn btn--buy btn--block" type="submit">Reset Password</button>
    </form>
  </div>
</div>
