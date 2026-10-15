<?php
declare(strict_types=1);

namespace App\Core;

/**
 * TOTP (RFC 6238) murni PHP, tanpa library. SHA1, 30 detik, 6 digit.
 */
class Totp
{
    public const PERIOD = 30;
    public const DIGITS = 6;

    /** 160-bit secret (20 byte) dalam base32 tanpa padding. */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function provisioningUri(string $secret, string $account, string $issuer = 'TokoMVP'): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        return 'otpauth://totp/' . $label
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    /** 10 backup code sekali pakai (plaintext, tampilkan sekali saja). */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // format mudah diketik: 4-4-4 hex, mis. a3f9-01bc-77e2
            $raw = bin2hex(random_bytes(6));
            $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
        }
        return $codes;
    }

    public static function hashBackupCode(string $code): string
    {
        return hash('sha256', str_replace('-', '', strtolower(trim($code))));
    }

    public static function verify(string $secret, string $code, int $window = 1, ?int $now = null): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $now ??= time();
        $slice = (int)floor($now / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $slice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function codeAt(string $secret, int $slice): string
    {
        $key = self::base32Decode($secret);
        // 8-byte big-endian counter
        $msg = pack('N*', 0, $slice);
        $hmac = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hmac[19]) & 0x0f;
        $bin = ((ord($hmac[$offset]) & 0x7f) << 24)
            | (ord($hmac[$offset + 1]) << 16)
            | (ord($hmac[$offset + 2]) << 8)
            | ord($hmac[$offset + 3]);
        return str_pad((string)($bin % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $ch) {
            $bits .= str_pad(decbin(ord($ch)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= $alphabet[bindec($chunk)];
        }
        return $out;
    }

    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(rtrim($b32, '='));
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $len = strlen($b32);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alphabet, $b32[$i]);
            if ($pos === false) {
                throw new \InvalidArgumentException('Base32 tidak valid.');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $out .= chr(bindec($byte));
        }
        return $out;
    }
}
