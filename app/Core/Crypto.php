<?php

declare(strict_types=1);

namespace BBAF\Core;

/**
 * Field-level encryption for the National Identification Number.
 * The key lives in config/config.php, never in the database.
 */
final class Crypto
{
    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', Config::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Could not encrypt a sensitive field.');
        }

        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $stored): string
    {
        if ($stored === '') {
            return '';
        }
        $raw = base64_decode($stored, true);
        if (!is_string($raw) || strlen($raw) < 29) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', Config::key(), OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? '' : $plain;
    }

    public static function hashNin(string $nin): string
    {
        $digits = preg_replace('/\D+/', '', $nin) ?? '';

        return hash_hmac('sha256', $digits, Config::key());
    }

    public static function maskNin(string $nin): string
    {
        $digits = preg_replace('/\D+/', '', $nin) ?? '';
        if (strlen($digits) < 4) {
            return '';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }
}
