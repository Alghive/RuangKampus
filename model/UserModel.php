<?php
namespace App\Models;

use Throwable;

final class UserModel extends BaseModel
{
    protected const TABLE = 'users';
    protected const PRIMARY_KEY = 'id_user';

    public static function findActiveByUsername(string $username): ?array
    {
        $statement = self::connection()->prepare(
            'SELECT `id_user`, `username`, `nama_lengkap`, `password_hash`, `role`, ' .
            '`failed_login_attempts`, `locked_until` ' .
            'FROM `users` WHERE `username` = ? AND `is_active` = 1 LIMIT 1'
        );
        $statement->bind_param('s', $username);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $user;
    }

    public static function create(string $username, string $name, string $passwordHash, string $role): bool
    {
        if (!in_array($role, ['admin', 'operator', 'viewer'], true)) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `users` (`username`, `nama_lengkap`, `password_hash`, `role`) VALUES (?, ?, ?, ?)'
            );
            $statement->bind_param('ssss', $username, $name, $passwordHash, $role);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function allForManagement(): array
    {
        $result = self::connection()->query(
            'SELECT `id_user`, `username`, `nama_lengkap`, `role`, `is_active`, `created_at`, `last_login_at` ' .
            'FROM `users` ORDER BY `nama_lengkap`, `username`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function updateAccess(int $userId, string $role, bool $isActive): bool
    {
        if ($userId <= 0 || !in_array($role, ['admin', 'operator', 'viewer'], true)) {
            return false;
        }

        $connection = self::connection();
        $lockAcquired = false;
        $transactionStarted = false;

        try {
            $lockResult = $connection->query("SELECT GET_LOCK('ruangkampus_user_access', 10) AS lock_acquired");
            $lock = $lockResult->fetch_assoc();
            if ((int) ($lock['lock_acquired'] ?? 0) !== 1) {
                return false;
            }
            $lockAcquired = true;
            $connection->begin_transaction();
            $transactionStarted = true;

            $currentStatement = $connection->prepare(
                'SELECT `role`, `is_active` FROM `users` WHERE `id_user` = ? FOR UPDATE'
            );
            $currentStatement->bind_param('i', $userId);
            $currentStatement->execute();
            $currentUser = $currentStatement->get_result()->fetch_assoc();
            $currentStatement->close();
            if ($currentUser === null) {
                $connection->rollback();
                $transactionStarted = false;
                return false;
            }

            $removesActiveAdmin = $currentUser['role'] === 'admin' && (int) $currentUser['is_active'] === 1 &&
                ($role !== 'admin' || !$isActive);
            if ($removesActiveAdmin) {
                $adminCountResult = $connection->query(
                    "SELECT COUNT(*) AS active_admins FROM `users` WHERE `role` = 'admin' AND `is_active` = 1"
                );
                $adminCount = (int) $adminCountResult->fetch_assoc()['active_admins'];
                if ($adminCount <= 1) {
                    $connection->rollback();
                    $transactionStarted = false;
                    return false;
                }
            }

            $statement = $connection->prepare(
                'UPDATE `users` SET `role` = ?, `is_active` = ? WHERE `id_user` = ?'
            );
            $activeValue = $isActive ? 1 : 0;
            $statement->bind_param('sii', $role, $activeValue, $userId);
            $statement->execute();
            $statement->close();
            $connection->commit();
            $transactionStarted = false;

            return true;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $connection->rollback();
            }
            return false;
        } finally {
            if ($lockAcquired) {
                $connection->query("SELECT RELEASE_LOCK('ruangkampus_user_access')");
            }
        }
    }

    public static function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = self::connection()->prepare(
            'UPDATE `users` SET `password_hash` = ? WHERE `id_user` = ?'
        );
        $statement->bind_param('si', $passwordHash, $userId);
        $statement->execute();
        $statement->close();
    }

    public static function recordFailedLogin(int $userId): void
    {
        $connection = self::connection();
        $reset = $connection->prepare(
            'UPDATE `users` SET `failed_login_attempts` = 0, `locked_until` = NULL ' .
            'WHERE `id_user` = ? AND `locked_until` IS NOT NULL AND `locked_until` <= CURRENT_TIMESTAMP'
        );
        $reset->bind_param('i', $userId);
        $reset->execute();
        $reset->close();

        $statement = $connection->prepare(
            'UPDATE `users` SET `locked_until` = CASE WHEN `failed_login_attempts` + 1 >= 5 ' .
            'THEN DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE) ELSE NULL END, ' .
            '`failed_login_attempts` = `failed_login_attempts` + 1 ' .
            'WHERE `id_user` = ? AND (`locked_until` IS NULL OR `locked_until` <= CURRENT_TIMESTAMP)'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $statement->close();
    }

    public static function recordSuccessfulLogin(int $userId): void
    {
        $statement = self::connection()->prepare(
            'UPDATE `users` SET `last_login_at` = CURRENT_TIMESTAMP, `failed_login_attempts` = 0, ' .
            '`locked_until` = NULL WHERE `id_user` = ?'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $statement->close();
    }
}