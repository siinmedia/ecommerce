<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\Totp;
use App\Core\Validator;
use App\Core\View;

class AuthController
{
    private array $config;

    private const MAX_FAIL_IP = 10;          // 10 gagal / 10 mnt per IP
    private const IP_WINDOW = 600;
    private const MAX_FAIL_ACCOUNT = 5;      // 5 gagal / 15 mnt per akun
    private const ACCOUNT_WINDOW = 900;

    /** Blocklist exact-match (lowercase). Tidak berisi password seed. */
    private const BLOCKED_PASSWORDS = [
        'password', 'password1', 'password12', 'password123',
        '12345678', '123456789', '1234567890', '00000000', '11111111',
        'qwerty', 'qwerty123', 'qwertyuiop', 'abc123', 'letmein',
        'welcome', 'welcome123', 'changeme', 'test1234', 'demo1234',
        'admin123', 'adminadmin', 'user123', 'useruser',
        'toko123', 'tokoku', 'tokoonline', 'toko12345',
        'indonesia', 'indonesia123', 'rahasia', 'rahasia123',
        'selamat', 'selamat123', 'sukses123', 'mudah123',
    ];

    public function __construct()
    {
        $this->config = require Database::configPath();
    }

    public static function isBlockedPassword(string $plain): bool
    {
        return in_array(strtolower(trim($plain)), self::BLOCKED_PASSWORDS, true);
    }

    private function base(string $title): array
    {
        return [
            'title' => $title, 'user' => Auth::user(), 'config' => $this->config,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ];
    }

    // ================= LOGIN =================

    public function showLogin(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect(Auth::role() === 'admin' ? '/admin' : '/');
        }
        View::layout('auth/login', $this->base('Masuk'));
    }

    private function clientIp(): string
    {
        return substr(trim($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45);
    }

    private function loginBlocked(string $ip, string $email): bool
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ' . self::IP_WINDOW . ' SECOND)'
        );
        $st->execute([$ip]);
        if ((int)$st->fetchColumn() >= self::MAX_FAIL_IP) {
            return true;
        }
        if ($email !== '') {
            $st = $pdo->prepare(
                'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ' . self::ACCOUNT_WINDOW . ' SECOND)'
            );
            $st->execute([$email]);
            if ((int)$st->fetchColumn() >= self::MAX_FAIL_ACCOUNT) {
                return true;
            }
        }
        return false;
    }

    private function recordLoginAttempt(string $ip, string $email, bool $success): void
    {
        Database::pdo()->prepare(
            'INSERT INTO login_attempts (ip, email, attempted_at, success) VALUES (?, ?, NOW(), ?)'
        )->execute([$ip, $email, $success ? 1 : 0]);
    }

    public function login(array $p = []): void
    {
        Csrf::requireValid();
        $ip = $this->clientIp();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = $_POST['password'] ?? '';

        // Pesan tetap generik (tidak membocorkan email terdaftar / status blokir).
        if ($this->loginBlocked($ip, $email)) {
            Helpers::keepOld(['email' => $email]);
            Helpers::flash('error', 'Email atau password salah.');
            Helpers::redirect('/login');
        }
        $user = \App\Models\User::findByEmail($email);
        if (!$user || $user['status'] !== 'active' || !password_verify($pass, $user['password_hash'])) {
            $this->recordLoginAttempt($ip, $email, false);
            Helpers::keepOld(['email' => $email]);
            Helpers::flash('error', 'Email atau password salah.');
            Helpers::redirect('/login');
        }
        $this->recordLoginAttempt($ip, $email, true);

        // Akun internal dengan 2FA aktif -> tantangan kode dulu (sesi parsial).
        if ((int)($user['totp_enabled'] ?? 0) === 1 && !empty($user['totp_secret'])) {
            $_SESSION['2fa_pending'] = [
                'user_id' => (int)$user['id'],
                'expires' => time() + 300,
            ];
            Helpers::clearOld();
            Helpers::redirect('/login/2fa');
        }

        $this->finishLogin($user);
    }

    private function finishLogin(array $user): void
    {
        Auth::login($user);
        CartController::mergeAfterLogin((int)$user['id']);
        Helpers::clearOld();
        unset($_SESSION['2fa_pending']);
        $next = Helpers::safeLocalPath($_SESSION['redirect_after_login'] ?? '/', '/');
        unset($_SESSION['redirect_after_login']);
        Helpers::redirect($next);
    }

    // ================= 2FA CHALLENGE =================

    public function showTwoFa(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        $pending = $_SESSION['2fa_pending'] ?? null;
        if (!$pending || $pending['expires'] < time()) {
            unset($_SESSION['2fa_pending']);
            Helpers::redirect('/login');
        }
        View::layout('auth/twofa', $this->base('Verifikasi 2FA'));
    }

    public function verifyTwoFa(array $p = []): void
    {
        Csrf::requireValid();
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        $pending = $_SESSION['2fa_pending'] ?? null;
        if (!$pending || $pending['expires'] < time()) {
            unset($_SESSION['2fa_pending']);
            Helpers::flash('error', 'Sesi verifikasi kedaluwarsa. Masuk ulang.');
            Helpers::redirect('/login');
        }
        $user = \App\Models\User::find((int)$pending['user_id']);
        if (!$user || $user['status'] !== 'active' || (int)($user['totp_enabled'] ?? 0) !== 1 || empty($user['totp_secret'])) {
            unset($_SESSION['2fa_pending']);
            Helpers::flash('error', 'Email atau password salah.');
            Helpers::redirect('/login');
        }
        $code = trim($_POST['code'] ?? '');
        $ok = Totp::verify($user['totp_secret'], $code);
        if (!$ok && preg_match('/^[0-9a-fA-F-]{10,}$/', $code)) {
            // Coba sebagai backup code (sekali pakai).
            $ok = \App\Models\User::consumeBackupCode((int)$user['id'], Totp::hashBackupCode($code));
        }
        if (!$ok) {
            \App\Models\Audit::log((int)$user['id'], $user['role'], 'auth.2fa_fail', 'users', (int)$user['id']);
            Helpers::flash('error', 'Kode verifikasi salah.');
            Helpers::redirect('/login/2fa');
        }
        \App\Models\Audit::log((int)$user['id'], $user['role'], 'auth.2fa_ok', 'users', (int)$user['id']);
        $this->finishLogin($user);
    }

    // ================= REGISTER =================

    public function showRegister(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        View::layout('auth/register', $this->base('Daftar'));
    }

    public function register(array $p = []): void
    {
        Csrf::requireValid();
        $v = new Validator($_POST);
        $v->required('name', 'Nama')->required('email', 'Email')->email('email')
            ->required('password', 'Password')->minLength('password', 8, 'Password');
        Helpers::keepOld(['name' => $_POST['name'] ?? '', 'email' => $_POST['email'] ?? '']);
        if (!$v->passes()) {
            Helpers::flash('error', implode(' ', $v->errors()));
            Helpers::redirect('/register');
        }
        if (self::isBlockedPassword($_POST['password'])) {
            Helpers::flash('error', 'Password terlalu umum. Gunakan kombinasi yang lebih kuat.');
            Helpers::redirect('/register');
        }
        $email = strtolower(trim($_POST['email']));
        if (\App\Models\User::findByEmail($email)) {
            Helpers::flash('error', 'Email atau password salah.');
            Helpers::redirect('/register');
        }
        $id = \App\Models\User::create(trim($_POST['name']), $email, Auth::hashPassword($_POST['password']));
        $user = \App\Models\User::find($id);
        Auth::login($user);
        CartController::mergeAfterLogin($id);
        Helpers::clearOld();
        Helpers::flash('success', 'Akun dibuat. Selamat belanja!');
        $next = Helpers::safeLocalPath($_SESSION['redirect_after_login'] ?? '/', '/');
        unset($_SESSION['redirect_after_login']);
        Helpers::redirect($next);
    }

    public function logout(array $p = []): void
    {
        Csrf::requireValid();
        Auth::logout();
        Helpers::redirect('/');
    }

    // ================= LUPA / RESET PASSWORD =================

    public function showForgot(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        View::layout('auth/forgot', $this->base('Lupa Password'));
    }

    public function forgot(array $p = []): void
    {
        Csrf::requireValid();
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        $email = strtolower(trim($_POST['email'] ?? ''));
        $user = $email !== '' ? \App\Models\User::findByEmail($email) : null;
        if ($user && $user['status'] === 'active') {
            $pdo = Database::pdo();
            // Satu token aktif per user: hapus yang lama.
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
            $selector = bin2hex(random_bytes(12)); // 24 char
            $token = bin2hex(random_bytes(32));    // 64 char hex di URL
            $pdo->prepare(
                'INSERT INTO password_resets (user_id, selector, token_hash, expires_at, created_ip, created_at)
                 VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, NOW())'
            )->execute([$user['id'], $selector, hash('sha256', $token), $this->clientIp()]);
            \App\Models\Audit::log((int)$user['id'], $user['role'], 'auth.reset_request', 'users', (int)$user['id']);
            // Tanpa mailer: di mode debug tampilkan link sekali (membantu operasional lokal).
            if (!empty($this->config['app']['debug'])) {
                $link = rtrim($this->config['app']['site_url'], '/') . '/reset-password?selector=' . $selector . '&token=' . $token;
                Helpers::flash('success', 'Link reset (mode debug): ' . $link);
                Helpers::redirect('/forgot-password');
            }
        }
        // Pesan selalu generik — tidak membocorkan apakah email terdaftar.
        Helpers::flash('success', 'Jika email terdaftar, link reset sudah dibuat dan berlaku 1 jam.');
        Helpers::redirect('/forgot-password');
    }

    private function findValidReset(string $selector, string $token): ?array
    {
        if (!preg_match('/^[0-9a-f]{24}$/i', $selector) || !preg_match('/^[0-9a-f]{64}$/i', $token)) {
            return null;
        }
        $st = Database::pdo()->prepare(
            'SELECT pr.*, u.status, u.email FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.selector = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1'
        );
        $st->execute([$selector]);
        $row = $st->fetch();
        if (!$row || $row['status'] !== 'active') {
            return null;
        }
        if (!hash_equals($row['token_hash'], hash('sha256', strtolower($token)))) {
            return null;
        }
        return $row;
    }

    public function showReset(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        $row = $this->findValidReset($_GET['selector'] ?? '', $_GET['token'] ?? '');
        if (!$row) {
            Helpers::flash('error', 'Link reset tidak valid atau sudah kedaluwarsa.');
            Helpers::redirect('/forgot-password');
        }
        View::layout('auth/reset', $this->base('Reset Password') + [
            'selector' => $_GET['selector'],
            'token' => $_GET['token'],
        ]);
    }

    public function reset(array $p = []): void
    {
        Csrf::requireValid();
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        $row = $this->findValidReset($_POST['selector'] ?? '', $_POST['token'] ?? '');
        if (!$row) {
            Helpers::flash('error', 'Link reset tidak valid atau sudah kedaluwarsa.');
            Helpers::redirect('/forgot-password');
        }
        $pass = $_POST['password'] ?? '';
        if (mb_strlen($pass) < 8) {
            Helpers::flash('error', 'Password minimal 8 karakter.');
            Helpers::redirect('/reset-password?selector=' . urlencode($_POST['selector']) . '&token=' . urlencode($_POST['token']));
        }
        if (self::isBlockedPassword($pass)) {
            Helpers::flash('error', 'Password terlalu umum. Gunakan kombinasi yang lebih kuat.');
            Helpers::redirect('/reset-password?selector=' . urlencode($_POST['selector']) . '&token=' . urlencode($_POST['token']));
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Kunci baris + pastikan sekali pakai (tidak dipakai konkuren).
            $st = $pdo->prepare('SELECT * FROM password_resets WHERE selector = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE');
            $st->execute([$row['selector']]);
            $locked = $st->fetch();
            if (!$locked || !hash_equals($locked['token_hash'], hash('sha256', strtolower($_POST['token'] ?? '')))) {
                throw new \RuntimeException('Link reset tidak valid atau sudah dipakai.');
            }
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([Auth::hashPassword($pass), $row['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$locked['id']]);
            // Invalidate SEMUA sesi lain user ini.
            $pdo->prepare('UPDATE users SET session_version = session_version + 1 WHERE id = ?')->execute([$row['user_id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Helpers::flash('error', $e->getMessage());
            Helpers::redirect('/forgot-password');
        }
        Auth::logout();
        \App\Models\Audit::log((int)$row['user_id'], 'user', 'auth.reset_ok', 'users', (int)$row['user_id']);
        Helpers::flash('success', 'Password berhasil direset. Silakan masuk dengan password baru.');
        Helpers::redirect('/login');
    }
}
