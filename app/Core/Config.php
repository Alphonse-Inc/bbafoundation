<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Config
{
    private static array $data = [];
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        $data = require $file;
        if (!is_array($data)) {
            throw new \RuntimeException('Configuration file did not return an array.');
        }
        self::$data = $data;
        self::$loaded = true;
    }

    public static function loaded(): bool
    {
        return self::$loaded;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!str_contains($key, '.')) {
            return self::$data[$key] ?? $default;
        }
        $cursor = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$part];
        }

        return $cursor;
    }

    public static function key(): string
    {
        $encoded = (string) self::get('key', '');
        $raw = base64_decode($encoded, true);
        if (!is_string($raw) || strlen($raw) < 32) {
            throw new \RuntimeException('Application key is missing.');
        }

        return substr($raw, 0, 32);
    }
}
