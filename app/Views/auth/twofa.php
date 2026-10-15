<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Verifikasi 2FA</h1>
<form method="post" action="/login/2fa" class="card p-4 max-w-md space-y-3">
  <?= Csrf::field() ?>
  <p class="text-sm text-gray-600">Masukkan 6 digit kode dari aplikasi authenticator, atau salah satu backup code.</p>
  <div><label class="lbl" for="code">Kode verifikasi</label><input type="text" name="code" id="code" required autocomplete="one-time-code" inputmode="numeric" placeholder="123456"></div>
  <button class="btn-primary px-4 py-2 rounded w-full" type="submit">Verifikasi</button>
</form>
