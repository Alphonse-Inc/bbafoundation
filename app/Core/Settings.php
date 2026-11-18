<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Settings
{
    public static function all(): array
    {
        return Cache::remember('settings', 300, static function (): array {
            $rows = Database::all('SELECT skey, svalue FROM settings');
            $out = [];
            foreach ($rows as $row) {
                $out[$row['skey']] = (string) ($row['svalue'] ?? '');
            }

            return $out;
        });
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();

        return array_key_exists($key, $all) ? (string) $all[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::exec(
            'INSERT INTO settings (skey, svalue, updated_at) VALUES (?, ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = UTC_TIMESTAMP()',
            [$key, $value]
        );
        Cache::forget('settings');
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            Database::exec(
                'INSERT INTO settings (skey, svalue, updated_at) VALUES (?, ?, UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = UTC_TIMESTAMP()',
                [$key, $value === null ? null : (string) $value]
            );
        }
        Cache::forget('settings');
    }

    public static function label(string $key): ?string
    {
        $labels = Cache::remember('labels', 300, static function (): array {
            $rows = Database::all('SELECT lkey, lvalue FROM labels');
            $out = [];
            foreach ($rows as $row) {
                $out[$row['lkey']] = (string) $row['lvalue'];
            }

            return $out;
        });

        return $labels[$key] ?? null;
    }

    public static function setLabel(string $key, string $value): void
    {
        Database::exec(
            'INSERT INTO labels (lkey, lvalue, updated_at) VALUES (?, ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE lvalue = VALUES(lvalue), updated_at = UTC_TIMESTAMP()',
            [$key, $value]
        );
        Cache::forget('labels');
    }
}
