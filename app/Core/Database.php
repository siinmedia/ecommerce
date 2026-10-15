<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(array $config = []): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $cfg = $config ?: require self::configPath();
        $db = $cfg['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            (int)($db['port'] ?? 3306),
            $db['name'],
            $db['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$pdo = $pdo;
        return $pdo;
    }

    public static function configPath(): string
    {
        $p = dirname(__DIR__) . '/Config/config.php';
        if (!file_exists($p)) {
            $p = dirname(__DIR__) . '/Config/config.example.php';
        }
        return $p;
    }

    /** For tests: inject an existing PDO (e.g. different DB). */
    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
