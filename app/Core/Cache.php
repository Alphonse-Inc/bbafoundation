<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Cache
{
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $file = self::file($key);
        if (is_file($file) && (time() - (int) filemtime($file)) < $ttl) {
            $raw = file_get_contents($file);
            if (is_string($raw) && $raw !== '') {
                $data = unserialize($raw, ['allowed_classes' => false]);
                if ($data !== false || $raw === serialize(false)) {
                    return $data;
                }
            }
        }
        $value = $callback();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($file, serialize($value), LOCK_EX);

        return $value;
    }

    public static function forget(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function flush(): void
    {
        $dir = BBAF_ROOT . '/storage/cache';
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function file(string $key): string
    {
        return BBAF_ROOT . '/storage/cache/' . sha1($key) . '.cache';
    }
}
