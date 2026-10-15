<?php use App\Core\View; use App\Core\Csrf; ?>
<div class="wrap page">
  <h1 style="font-size:var(--fs-h2);color:var(--blue-ink);letter-spacing:-.02em" class="mb-3">Keamanan 2FA</h1>
  <div class="acc">
    <nav class="accnav" aria-label="Menu akun">
      <a href="/account/orders">Pesanan Saya</a>
      <a href="/account/addresses">Alamat Saya</a>
      <a href="/account/profile">Profil</a>
      <a href="/account/2fa" class="is-active" aria-current="page">Keamanan 2FA</a>
    </nav>
    <div>
      <?php if (!empty($just_enabled)): ?>
        <div class="form-card mb-3" style="max-width:560px;border-color:var(--success)">
          <p class="mb-2" style="font-weight:700;color:var(--success)">2FA aktif. Simpan backup code ini SEKARANG — hanya tampil sekali:</p>
          <ul class="codes">
            <?php foreach (($backup_codes ?? []) as $c): ?><li><?= View::e($c) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if ($totp_enabled): ?>
        <div class="form-card" style="max-width:560px">
          <p style="font-size:15px">Status: <strong style="color:var(--success)">Aktif</strong> — sisa backup code: <strong><?= (int)$backup_left ?></strong></p>
          <hr class="divider">
          <form method="post" action="/account/2fa/disable">
            <?= Csrf::field() ?>
            <div class="field">
              <label class="lbl" for="password">Konfirmasi password untuk menonaktifkan <span class="req" aria-hidden="true">*</span></label>
              <input class="input" type="password" name="password" id="password" required autocomplete="current-password">
            </div>
            <button class="btn btn--danger-ghost" type="submit">Nonaktifkan 2FA</button>
          </form>
        </div>
      <?php else: ?>
        <div class="form-card" style="max-width:560px">
          <?php if (!empty($must_enable)): ?><p class="mb-2" style="font-size:14.5px;color:var(--warn-tx);font-weight:700">Akun <?= View::e($user['role'] ?? '') ?> wajib mengaktifkan 2FA.</p><?php endif; ?>
          <ol style="font-size:14.5px;list-style:decimal;margin-left:20px;display:grid;gap:6px" class="mb-3">
            <li>Buka aplikasi authenticator (Google/Microsoft Authenticator, 1Password, dsb).</li>
            <li>Tambah akun manual dengan secret berikut:</li>
          </ol>
          <p class="mono codes mb-2"><?= View::e($secret ?? '') ?></p>
          <p class="muted mono mb-3" style="font-size:12px">URI: <?= View::e($otpauth_uri ?? '') ?></p>
          <form method="post" action="/account/2fa/enable">
            <?= Csrf::field() ?>
            <div class="field">
              <label class="lbl" for="code">Kode 6 digit dari aplikasi <span class="req" aria-hidden="true">*</span></label>
              <input class="input" type="text" name="code" id="code" required inputmode="numeric" placeholder="123456">
            </div>
            <button class="btn btn--buy btn--block" type="submit">Aktifkan 2FA</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
