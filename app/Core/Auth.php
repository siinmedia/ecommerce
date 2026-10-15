<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public const IDLE_TIMEOUT = 1800;   // 30 menit
    public const ABSOLUTE_LIFETIME = 43200; // 12 jam
    public const REGEN_INTERVAL = 900;  // regenerasi ID tiap 15 menit

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        // Penjagaan ringan tanpa DB: idle + absolute lifetime.
        $now = time();
        if (($now - (int)($_SESSION['auth_last_activity'] ?? $now)) > self::IDLE_TIMEOUT) {
            self::logout();
            return false;
        }
        if (($now - (int)($_SESSION['auth_created'] ?? $now)) > self::ABSOLUTE_LIFETIME) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $now = time();
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'session_version' => (int)($user['session_version'] ?? 0),
        ];
        $_SESSION['auth_created'] = $now;
        $_SESSION['auth_last_activity'] = $now;
        $_SESSION['auth_regen_at'] = $now;
    }

    public static function logout(): void
    {
        unset(
            $_SESSION['user'],
            $_SESSION['auth_created'],
            $_SESSION['auth_last_activity'],
            $_SESSION['auth_regen_at'],
            $_SESSION['2fa_pending']
        );
        session_regenerate_id(true);
    }

    /**
     * Dipanggil sekali per request (di front controller setelah session_start).
     * Menegakkan idle timeout, absolute lifetime, rotasi ID, dan session_version
     * (untuk memaksa logout sesi lama setelah reset password).
     */
    public static function tick(): void
    {
        if (!isset($_SESSION['user'])) {
            return;
        }
        $now = time();
        if (($now - (int)($_SESSION['auth_last_activity'] ?? $now)) > self::IDLE_TIMEOUT) {
            self::logout();
            return;
        }
        if (($now - (int)($_SESSION['auth_created'] ?? $now)) > self::ABSOLUTE_LIFETIME) {
            self::logout();
            return;
        }
        if (($now - (int)($_SESSION['auth_regen_at'] ?? $now)) > self::REGEN_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['auth_regen_at'] = $now;
        }
        $_SESSION['auth_last_activity'] = $now;
        // Tolak sesi yang versinya sudah tidak cocok (mis. setelah reset password).
        try {
            $st = Database::pdo()->prepare('SELECT session_version FROM users WHERE id = ? LIMIT 1');
            $st->execute([(int)$_SESSION['user']['id']]);
            $row = $st->fetch();
            if (!$row || (int)$row['session_version'] !== (int)($_SESSION['user']['session_version'] ?? -1)) {
                self::logout();
            }
        } catch (\Throwable) {
            // DB down: biarkan global error handler yang menangani di layer dispatch.
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            Helpers::redirect('/login');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Akses ditolak']);
            exit;
        }
    }

    public static function hashPassword(string $plain): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($plain, $algo);
    }
}
