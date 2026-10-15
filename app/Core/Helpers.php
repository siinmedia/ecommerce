<?php
declare(strict_types=1);

namespace App\Core;

class Helpers
{
    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    /** Hanya path lokal: diawali SATU slash; tolak `//`, backslash, skema, whitespace. */
    public static function safeLocalPath(mixed $url, string $fallback = '/'): string
    {
        if (!is_string($url) || $url === '') {
            return $fallback;
        }
        $url = trim($url);
        if (!str_starts_with($url, '/')) {
            return $fallback;
        }
        if (str_starts_with($url, '//')) {
            return $fallback;
        }
        if (str_contains($url, '\\')) {
            return $fallback;
        }
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $url)) {
            return $fallback;
        }
        if (preg_match('/[\s\x00-\x1f\x7f]/', $url)) {
            return $fallback;
        }
        return $url;
    }

    public static function back(string $fallback = '/'): void
    {
        $fallback = self::safeLocalPath($fallback, '/');
        $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
        if (is_string($ref) && trim($ref) !== '') {
            $parts = parse_url(trim($ref));
            if (is_array($parts)) {
                // URL absolut ke host lain -> abaikan sepenuhnya (pakai fallback).
                if (isset($parts['host'])) {
                    $reqHost = strtolower(trim($_SERVER['HTTP_HOST'] ?? ''));
                    $refHost = strtolower($parts['host']);
                    // Terima juga tanpa port eksplisit bila host dasar sama.
                    $base = static fn(string $h): string => strtolower(preg_replace('/:\d+$/', '', $h));
                    if ($reqHost === '' || $base($refHost) !== $base($reqHost)) {
                        self::redirect($fallback);
                    }
                }
                if (isset($parts['path'])) {
                    $candidate = $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
                    self::redirect(self::safeLocalPath($candidate, $fallback));
                }
            }
            self::redirect(self::safeLocalPath($ref, $fallback));
        }
        self::redirect($fallback);
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
            return null;
        }
        $v = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $v;
    }

    /** Nilai mentah old input — WAJIB di-escape di view via View::e(). */
    public static function old(string $key, string $default = ''): string
    {
        return (string)($_SESSION['_old'][$key] ?? $default);
    }

    public static function keepOld(array $data): void
    {
        $_SESSION['_old'] = $data;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old']);
    }

    public static function rupiah(int|float $n): string
    {
        return 'Rp' . number_format((float)$n, 0, ',', '.');
    }

    public static function orderCode(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    public static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        [$u, $d] = $parts;
        $u = strlen($u) <= 2 ? str_repeat('*', strlen($u)) : $u[0] . str_repeat('*', strlen($u) - 2) . substr($u, -1);
        return $u . '@' . $d;
    }

    public static function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($phone, 0, 2) . str_repeat('*', $len - 5) . substr($phone, -3);
    }

    public static function siteUrl(array $config, string $path = ''): string
    {
        return rtrim($config['app']['site_url'], '/') . $path;
    }
}
