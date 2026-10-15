<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $hash, string $role = 'user'): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, "active", NOW())'
        );
        $st->execute([$name, $email, $hash, $role]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function updatePassword(int $id, string $hash): void
    {
        Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    }

    /** Naikkan versi sesi agar semua sesi aktif user dipaksa logout. */
    public static function bumpSessionVersion(int $id): int
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET session_version = session_version + 1 WHERE id = ?')->execute([$id]);
        $st = $pdo->prepare('SELECT session_version FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }

    public static function setTotpSecret(int $id, string $secret): void
    {
        Database::pdo()->prepare('UPDATE users SET totp_secret = ?, totp_enabled = 0 WHERE id = ?')->execute([$secret, $id]);
    }

    public static function enableTotp(int $id): void
    {
        Database::pdo()->prepare('UPDATE users SET totp_enabled = 1 WHERE id = ? AND totp_secret IS NOT NULL')->execute([$id]);
    }

    public static function disableTotp(int $id): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM totp_backup_codes WHERE user_id = ?')->execute([$id]);
    }

    public static function storeBackupCodes(int $id, array $hashes): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM totp_backup_codes WHERE user_id = ?')->execute([$id]);
        $st = $pdo->prepare('INSERT INTO totp_backup_codes (user_id, code_hash, created_at) VALUES (?, ?, NOW())');
        foreach ($hashes as $h) {
            $st->execute([$id, $h]);
        }
    }

    public static function countUnusedBackupCodes(int $id): int
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM totp_backup_codes WHERE user_id = ? AND used_at IS NULL');
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }

    /** Konsumsi satu backup code (sekali pakai). True bila valid & belum dipakai. */
    public static function consumeBackupCode(int $id, string $hash): bool
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'SELECT id FROM totp_backup_codes WHERE user_id = ? AND code_hash = ? AND used_at IS NULL LIMIT 1 FOR UPDATE'
            );
            $st->execute([$id, $hash]);
            $row = $st->fetch();
            if (!$row) {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare('UPDATE totp_backup_codes SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
            $pdo->commit();
            return true;
        } catch (\Throwable) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function paginateCustomers(int $page = 1, int $perPage = 20): array
    {
        $off = ($page - 1) * $perPage;
        $st = Database::pdo()->prepare(
            "SELECT id, name, email, phone, status, created_at FROM users WHERE role = 'user' ORDER BY id DESC LIMIT :l OFFSET :o"
        );
        $st->bindValue(':l', $perPage, PDO::PARAM_INT);
        $st->bindValue(':o', $off, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
