<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Database;

final class Importer
{
    public static function rows(): array
    {
        return Database::all('SELECT * FROM legacy_imports ORDER BY id');
    }

    public static function canDownload(): bool
    {
        return function_exists('curl_init') || (bool) ini_get('allow_url_fopen');
    }

    public static function pendingCount(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM legacy_imports WHERE status <> 'imported'");
    }

    /** @return list<array{url:string,status:string,error:string}> */
    public static function run(): array
    {
        if (!self::canDownload()) {
            return [];
        }
        $dir = BBAF_PUBLIC . '/assets/img/legacy';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('The legacy image folder is not writable.');
        }
        $results = [];
        foreach (self::rows() as $row) {
            $local = (string) $row['local_path'];
            if ($row['status'] === 'imported' && $local !== '' && is_file(BBAF_PUBLIC . '/' . ltrim($local, '/'))) {
                $results[] = ['url' => $row['source_url'], 'status' => 'imported', 'error' => ''];
                continue;
            }
            try {
                $binary = self::fetch((string) $row['source_url']);
                $name = basename(parse_url((string) $row['source_url'], PHP_URL_PATH) ?: 'file');
                if ($name === '' || str_contains($name, '..')) {
                    throw new \RuntimeException('The file name is not safe.');
                }
                $target = $dir . '/' . $name;
                if (file_put_contents($target, $binary) === false) {
                    throw new \RuntimeException('The file could not be saved.');
                }
                $checksum = hash('sha256', $binary);
                $info = @getimagesize($target);
                $width = is_array($info) ? (int) $info[0] : null;
                $height = is_array($info) ? (int) $info[1] : null;
                self::webp($target, is_array($info) ? (string) ($info['mime'] ?? '') : '');
                $public = 'assets/img/legacy/' . $name;
                Database::exec(
                    "UPDATE legacy_imports SET local_path = ?, checksum = ?, width = ?, height = ?, status = 'imported', error_text = '', updated_at = UTC_TIMESTAMP() WHERE id = ?",
                    [$public, $checksum, $width, $height, (int) $row['id']]
                );
                $sourcePath = parse_url((string) $row['source_url'], PHP_URL_PATH) ?: '';
                Database::exec(
                    'UPDATE redirects SET target_path = ?, status_code = 301, note = ? WHERE source_path = ?',
                    ['/' . $public, 'Imported from the previous website', $sourcePath]
                );
                if (str_ends_with(strtolower($name), '.pdf')) {
                    self::library($target, $name, (string) $row['alt_text'], $checksum);
                }
                $results[] = ['url' => $row['source_url'], 'status' => 'imported', 'error' => ''];
            } catch (\Throwable $error) {
                Database::exec(
                    "UPDATE legacy_imports SET status = 'failed', error_text = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?",
                    [mb_substr($error->getMessage(), 0, 500), (int) $row['id']]
                );
                $results[] = ['url' => $row['source_url'], 'status' => 'failed', 'error' => $error->getMessage()];
            }
        }

        return $results;
    }

    private static function fetch(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== 'bbafoundation.com' && !str_ends_with($host, '.bbafoundation.com')) {
            throw new \RuntimeException('The import only downloads files from bbafoundation.com.');
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'BBAFoundation-importer',
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);
            $finalHost = strtolower((string) parse_url($final, PHP_URL_HOST));
            if (!is_string($body) || $code !== 200 || ($finalHost !== 'bbafoundation.com' && !str_ends_with($finalHost, '.bbafoundation.com'))) {
                throw new \RuntimeException('Download failed with HTTP ' . $code . '.');
            }

            return $body;
        }
        $body = @file_get_contents($url);
        if (!is_string($body) || $body === '') {
            throw new \RuntimeException('Download failed.');
        }

        return $body;
    }

    private static function webp(string $path, string $mime): void
    {
        if (!function_exists('imagewebp') || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return;
        }
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };
        if (!$image) {
            return;
        }
        imagewebp($image, preg_replace('/\.[^.]+$/', '.webp', $path) ?: ($path . '.webp'), 82);
        imagedestroy($image);
    }

    private static function library(string $path, string $name, string $alt, string $checksum): void
    {
        $publicDir = BBAF_PUBLIC . '/assets/media';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0775, true);
        }
        $copy = $publicDir . '/' . $name;
        if (!is_file($copy)) {
            copy($path, $copy);
        }
        $exists = Database::one('SELECT id FROM media WHERE checksum = ?', [$checksum]);
        if ($exists) {
            return;
        }
        Database::insert(
            'INSERT INTO media (disk_path, public_path, mime, original_name, alt_text, byte_size, checksum, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [$copy, 'assets/media/' . $name, 'application/pdf', $name, $alt, filesize($copy) ?: 0, $checksum]
        );
    }
}
