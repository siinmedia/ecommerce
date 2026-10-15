<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Profil</h1>
<form method="post" action="/account/profile" class="card p-4 max-w-md space-y-3">
  <?= Csrf::field() ?>
  <div><label class="lbl">Nama</label><input type="text" name="name" required value="<?= View::e($profile['name'] ?? '') ?>"></div>
  <div><label class="lbl">Email</label><input type="email" value="<?= View::e($profile['email'] ?? '') ?>" disabled></div>
  <div><label class="lbl">No HP</label><input type="text" name="phone" value="<?= View::e($profile['phone'] ?? '') ?>"></div>
  <button class="btn-primary px-4 py-2 rounded" type="submit">Simpan</button>
</form>
