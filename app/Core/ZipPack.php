<?php

declare(strict_types=1);

namespace BBAF\Core;

/** Builds a stored (uncompressed) ZIP. Used when the zip extension is missing. */
final class ZipPack
{
    public static function create(array $files, string $destination): void
    {
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create the ZIP file.');
            }
            foreach ($files as $name => $path) {
                $zip->addFile($path, $name);
            }
            $zip->close();
            return;
        }
        $handle = fopen($destination, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Could not create the ZIP file.');
        }
        $central = '';
        $offset = 0;
        $count = 0;
        foreach ($files as $name => $path) {
            $data = file_get_contents($path);
            if (!is_string($data)) {
                continue;
            }
            $name = str_replace('\\', '/', (string) $name);
            $crc = crc32($data);
            $size = strlen($data);
            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), 0)
                . $name;
            fwrite($handle, $local . $data);
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset)
                . $name;
            $offset += strlen($local) + $size;
            $count++;
        }
        fwrite($handle, $central);
        fwrite($handle, pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), $offset, 0));
        fclose($handle);
    }
}
