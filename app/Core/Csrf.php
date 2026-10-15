<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireValid(): void
    {
        if (!self::validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo View::render('errors/419', ['title' => 'Sesi kedaluwarsa']);
            exit;
        }
    }
}
