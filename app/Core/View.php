<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static string $basePath = '';

    public static function init(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function render(string $view, array $data = []): string
    {
        $file = self::$basePath . '/' . str_replace('.', '/', $view) . '.php';
        // allow slash notation too
        if (!file_exists($file)) {
            $file = self::$basePath . '/' . $view . '.php';
        }
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }

    public static function e(?string $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }

    public static function layout(string $contentView, array $data = []): void
    {
        $data['content'] = self::render($contentView, $data);
        echo self::render('layout/header', $data);
        echo $data['content'];
        echo self::render('layout/footer', $data);
    }
}
