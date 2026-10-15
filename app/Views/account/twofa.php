<?php use App\Core\View; use App\Core\Csrf; ?>
<h1 class="text-2xl font-bold mb-4">Keamanan 2FA</h1>
<?php if (!empty($just_enabled)): ?>
  <div class="card p-4 max-w-md mb-4 border-green-500">
    <p class="font-bold text-green-700 mb-2">2FA aktif. Simpan backup code ini SEKARANG — hanya tampil sekali:</p>
    <ul class="font-mono text-sm space-y-1">
      <?php foreach (($backup_codes ?? []) as $c): ?><li><?= View::e($c) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if ($totp_enabled): ?>
  <div class="card p-4 max-w-md space-y-2">
    <p class="text-sm">Status: <strong class="text-green-700">Aktif</strong> — sisa backup code: <strong><?= (int)$backup_left ?></strong></p>
    <form method="post" action="/account/2fa/disable" class="space-y-2">
      <?= Csrf::field() ?>
      <div><label class="lbl" for="password">Konfirmasi password untuk menonaktifkan</label><input type="password" name="password" id="password" required></div>
      <button class="px-4 py-2 rounded border border-red-600 text-red-600 text-sm" type="submit">Nonaktifkan 2FA</button>
    </form>
  </div>
<?php else: ?>
  <div class="card p-4 max-w-md space-y-3">
    <?php if (!empty($must_enable)): ?><p class="text-sm text-amber-700 font-semibold">Akun <?= View::e($user['role'] ?? '') ?> wajib mengaktifkan 2FA.</p><?php endif; ?>
    <ol class="text-sm list-decimal ml-5 space-y-1">
      <li>Buka aplikasi authenticator (Google/Microsoft Authenticator, 1Password, dsb).</li>
      <li>Tambah akun manual dengan secret berikut:</li>
    </ol>
    <p class="font-mono bg-gray-100 p-2 rounded break-all"><?= View::e($secret ?? '') ?></p>
    <p class="text-xs text-gray-500 break-all">URI: <?= View::e($otpauth_uri ?? '') ?></p>
    <form method="post" action="/account/2fa/enable" class="space-y-2">
      <?= Csrf::field() ?>
      <div><label class="lbl" for="code">Kode 6 digit dari aplikasi</label><input type="text" name="code" id="code" required inputmode="numeric" placeholder="123456"></div>
      <button class="btn-primary px-4 py-2 rounded w-full" type="submit">Aktifkan 2FA</button>
    </form>
  </div>
<?php endif; ?>
