<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Lupa Password</h1>
<form method="post" action="/forgot-password" class="card p-4 max-w-md space-y-3">
  <?= Csrf::field() ?>
  <p class="text-sm text-gray-600">Masukkan email akun. Jika terdaftar, link reset berlaku 1 jam akan dibuat.</p>
  <div><label class="lbl" for="email">Email</label><input type="email" name="email" id="email" required></div>
  <button class="btn-primary px-4 py-2 rounded w-full" type="submit">Kirim Link Reset</button>
  <p class="text-sm"><a class="underline" href="/login">Kembali masuk</a></p>
</form>
