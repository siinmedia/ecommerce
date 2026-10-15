<?php use App\Core\View; use App\Core\Helpers; ?>
<h1 class="text-2xl font-bold mb-4">Daftar Akun</h1>
<form method="post" action="/register" class="card p-4 max-w-md space-y-3">
  <?= \App\Core\Csrf::field() ?>
  <div><label class="lbl" for="name">Nama</label><input type="text" name="name" id="name" required value="<?= Helpers::old('name') ?>"></div>
  <div><label class="lbl" for="email">Email</label><input type="email" name="email" id="email" required value="<?= Helpers::old('email') ?>"></div>
  <div><label class="lbl" for="password">Password (min 8 karakter)</label><input type="password" name="password" id="password" required minlength="8"></div>
  <button class="btn-primary px-4 py-2 rounded w-full" type="submit">Daftar</button>
  <p class="text-sm">Sudah punya akun? <a class="underline" href="/login">Masuk</a></p>
</form>
