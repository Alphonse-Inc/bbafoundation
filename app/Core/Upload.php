<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Upload
{
    private const IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const DOC_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public static function storePrivate(array $file, array $allowedMime, int $maxBytes): array
    {
        return self::store($file, $allowedMime, $maxBytes, BBAF_ROOT . '/storage/uploads/private');
    }

    public static function storePublicImage(array $file, int $maxBytes = 8000000): array
    {
        $stored = self::store($file, self::IMAGE_MIME, $maxBytes, BBAF_ROOT . '/storage/uploads/public');
        $publicDir = BBAF_PUBLIC . '/assets/media';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0775, true);
        }
        $target = $publicDir . '/' . $stored['stored_name'];
        if (!copy($stored['absolute'], $target)) {
            throw new \RuntimeException('Could not publish the image.');
        }
        $stored['public_path'] = 'assets/media/' . $stored['stored_name'];
        self::writeWebp($target);

        return $stored;
    }

    public static function documentMimes(): array
    {
        return self::DOC_MIME;
    }

    public static function imageMimes(): array
    {
        return self::IMAGE_MIME;
    }

    public static function scan(string $path, string $mime): ?string
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return 'The file could not be read.';
        }
        $sample = (string) fread($handle, 8192);
        fclose($handle);
        if (str_contains($sample, '<?php') || stripos($sample, '<script') !== false) {
            return 'The file was rejected because it contains executable content.';
        }
        if ($mime === 'application/pdf') {
            if (!str_starts_with($sample, '%PDF')) {
                return 'The PDF does not look valid.';
            }
            $lower = strtolower($sample . (string) @file_get_contents($path, false, null, 0, 200000));
            foreach (['/javascript', '/js', '/launch', '/embeddedfile'] as $needle) {
                if (str_contains($lower, $needle)) {
                    return 'The PDF was rejected because it contains active content.';
                }
            }
        }

        return null;
    }

    public static function stripImage(string $path, string $mime): void
    {
        if (!isset(self::IMAGE_MIME[$mime]) || !function_exists('imagecreatetruecolor')) {
            return;
        }
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        if (!$image) {
            return;
        }
        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $path, 6);
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($image, $path, 82);
        } else {
            imagejpeg($image, $path, 85);
        }
        imagedestroy($image);
    }

    public static function inspectZip(string $path, int $maxEntries = 40, int $maxUncompressed = 104857600): ?string
    {
        if (!class_exists(\ZipArchive::class)) {
            return 'ZIP support is not available on this server.';
        }
        $zip = new \ZipArchive();
        $opened = $zip->open($path, \ZipArchive::CHECKCONS);
        if ($opened !== true) {
            return 'The ZIP file is damaged or unreadable.';
        }
        if ($zip->numFiles > $maxEntries) {
            $zip->close();
            return 'The ZIP file contains too many files.';
        }
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat)) {
                continue;
            }
            $name = (string) ($stat['name'] ?? '');
            if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                $zip->close();
                return 'The ZIP file contains an unsafe path.';
            }
            if (($stat['encryption_method'] ?? 0) !== 0) {
                $zip->close();
                return 'Encrypted ZIP files are not accepted.';
            }
            $total += (int) ($stat['size'] ?? 0);
            if ($total > $maxUncompressed) {
                $zip->close();
                return 'The ZIP file expands beyond the allowed size.';
            }
            $chunk = (string) $zip->getFromIndex($i, 4096);
            if (str_contains($chunk, '<?php') || stripos($chunk, '<script') !== false) {
                $zip->close();
                return 'The ZIP file contains a rejected file type.';
            }
        }
        $zip->close();

        return null;
    }

    private static function store(array $file, array $allowedMime, int $maxBytes, string $directory): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadError($error));
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('The upload did not arrive correctly.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > $maxBytes) {
            throw new \RuntimeException('The file is larger than the allowed size.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        if (!isset($allowedMime[$mime])) {
            throw new \RuntimeException('This file type is not accepted.');
        }
        $problem = self::scan($tmp, $mime);
        if ($problem !== null) {
            throw new \RuntimeException($problem);
        }
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('The upload folder is not writable.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
        $absolute = rtrim($directory, '/') . '/' . $name;
        if (!move_uploaded_file($tmp, $absolute)) {
            throw new \RuntimeException('The file could not be stored.');
        }
        chmod($absolute, 0640);
        if (isset(self::IMAGE_MIME[$mime])) {
            self::stripImage($absolute, $mime);
            clearstatcache(true, $absolute);
            $size = (int) filesize($absolute);
        }

        return [
            'stored_name' => $name,
            'absolute' => $absolute,
            'mime' => $mime,
            'byte_size' => $size,
            'original_name' => mb_substr(basename((string) ($file['name'] ?? 'file')), 0, 200),
            'public_path' => '',
        ];
    }

    private static function writeWebp(string $path): void
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
            return;
        }
        $raw = file_get_contents($path);
        if (!is_string($raw)) {
            return;
        }
        $image = @imagecreatefromstring($raw);
        if (!$image) {
            return;
        }
        $webp = preg_replace('/\.[a-z0-9]+$/i', '.webp', $path);
        if (is_string($webp)) {
            imagewebp($image, $webp, 82);
        }
        imagedestroy($image);
    }

    private static function uploadError(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is larger than the server allows.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            default => 'The upload failed.',
        };
    }
}
