<?php use App\Core\View; use App\Core\Csrf; ?>
<div class="wrap page">
  <div class="form-narrow">
    <h1 class="form-title">Lupa Password</h1>
    <p class="form-sub">Masukkan email akun. Jika terdaftar, link reset berlaku 1 jam akan dibuat.</p>
    <form method="post" action="/forgot-password" class="form-card">
      <?= Csrf::field() ?>
      <div class="field">
        <label class="lbl" for="email">Email <span class="req" aria-hidden="true">*</span></label>
        <input class="input" type="email" name="email" id="email" required autocomplete="email">
      </div>
      <button class="btn btn--buy btn--block" type="submit">Kirim Link Reset</button>
      <p class="mt-3 center" style="font-size:14.5px"><a class="link" href="/login">Kembali masuk</a></p>
    </form>
  </div>
</div>
