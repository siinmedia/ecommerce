<?php use App\Core\View; use App\Core\Helpers; ?>
<div class="wrap page">
  <div class="form-narrow">
    <h1 class="form-title">Daftar Akun</h1>
    <p class="form-sub">Satu akun untuk belanja, lacak paket, dan riwayat order.</p>
    <form method="post" action="/register" class="form-card">
      <?= \App\Core\Csrf::field() ?>
      <div class="field">
        <label class="lbl" for="name">Nama <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="text" name="name" id="name" required autocomplete="name" value="<?= View::e(Helpers::old('name')) ?>">
      </div>
      <div class="field">
        <label class="lbl" for="email">Email <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="email" name="email" id="email" required autocomplete="email" value="<?= View::e(Helpers::old('email')) ?>">
      </div>
      <div class="field">
        <label class="lbl" for="password">Password (min 8 karakter) <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="password" name="password" id="password" required minlength="8" autocomplete="new-password">
      </div>
      <button class="btn btn--buy btn--block" type="submit">Daftar</button>
      <p class="mt-3 center" style="font-size:14.5px">Sudah punya akun? <a class="link" href="/login">Masuk</a></p>
    </form>
  </div>
</div>
