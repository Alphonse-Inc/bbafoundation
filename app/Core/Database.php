<?php

declare(strict_types=1);

namespace BBAF\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }
        $db = Config::get('db');
        if (!is_array($db)) {
            throw new PDOException('Database configuration is missing.');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'] ?? 'localhost',
            (int) ($db['port'] ?? 3306),
            $db['name'] ?? '',
            $db['charset'] ?? 'utf8mb4'
        );
        self::$pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$pdo->exec("SET time_zone = '+00:00'");
    }

    public static function ready(): bool
    {
        return self::$pdo instanceof PDO;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            self::boot();
        }

        return self::$pdo;
    }

    public static function all(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(self::params($params));

        return $statement->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(self::params($params));
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(self::params($params));

        return $statement->fetchColumn();
    }

    public static function exec(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(self::params($params));

        return $statement->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(self::params($params));

        return (int) self::pdo()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private static function params(array $params): array
    {
        $clean = [];
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }
}
