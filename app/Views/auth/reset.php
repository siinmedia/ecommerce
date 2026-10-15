<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Reset Password</h1>
<form method="post" action="/reset-password" class="card p-4 max-w-md space-y-3">
  <?= Csrf::field() ?>
  <input type="hidden" name="selector" value="<?= View::e($selector ?? '') ?>">
  <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
  <div><label class="lbl" for="password">Password baru (min 8 karakter)</label><input type="password" name="password" id="password" required minlength="8"></div>
  <button class="btn-primary px-4 py-2 rounded w-full" type="submit">Reset Password</button>
</form>
