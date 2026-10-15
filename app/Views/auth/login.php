<?php use App\Core\View; use App\Core\Helpers; ?>
<div class="wrap page">
  <div class="form-narrow">
    <h1 class="form-title">Masuk</h1>
    <p class="form-sub">Masuk untuk checkout lebih cepat dan lacak pesanan.</p>
    <form method="post" action="/login" class="form-card">
      <?= \App\Core\Csrf::field() ?>
      <div class="field">
        <label class="lbl" for="email">Email <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="email" name="email" id="email" required autocomplete="email" value="<?= View::e(Helpers::old('email')) ?>">
      </div>
      <div class="field">
        <label class="lbl" for="password">Password <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="password" name="password" id="password" required autocomplete="current-password">
        <span class="field__hint"><a class="link" href="/forgot-password">Lupa password?</a></span>
      </div>
      <button class="btn btn--buy btn--block" type="submit">Masuk</button>
      <p class="mt-3 center" style="font-size:14.5px">Belum punya akun? <a class="link" href="/register">Daftar</a></p>
    </form>
  </div>
</div>
