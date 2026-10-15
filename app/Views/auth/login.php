<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-4">Masuk</h1>
<form method="post" action="/login" class="card p-4 max-w-md space-y-3">
  <?= \App\Core\Csrf::field() ?>
  <div><label class="lbl" for="email">Email</label><input type="email" name="email" id="email" required value="<?= \App\Core\Helpers::old('email') ?>"></div>
  <div><label class="lbl" for="password">Password</label><input type="password" name="password" id="password" required></div>
  <button class="btn-primary px-4 py-2 rounded w-full" type="submit">Masuk</button>
  <p class="text-sm">Belum punya akun? <a class="underline" href="/register">Daftar</a></p>
</form>
