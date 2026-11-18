<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Security
{
    public static function headers(string $nonce): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        if (Session::https()) {
            header('Strict-Transport-Security: max-age=15552000; includeSubDomains');
        }
        $csp = "default-src 'self'; "
            . "img-src 'self' data: blob: https://www.google-analytics.com; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com; "
            . "font-src 'self'; "
            . "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com; "
            . "frame-src 'self' https://www.openstreetmap.org; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self'";
        header('Content-Security-Policy: ' . $csp);
    }

    public static function hashPassword(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            if (is_string($hash)) {
                return $hash;
            }
        }

        return (string) password_hash($password, PASSWORD_BCRYPT);
    }

    public static function passwordProblem(string $password): ?string
    {
        if (strlen($password) < 10) {
            return 'Use at least 10 characters.';
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            return 'Include both uppercase and lowercase letters.';
        }
        if (!preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Include a number and a symbol.';
        }

        return null;
    }

    public static function rateAllow(string $bucket, int $max, int $windowSeconds): bool
    {
        $ip = client_ip();
        $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);
        $row = Database::one(
            'SELECT id, hits FROM rate_limits WHERE bucket = ? AND ip = ? AND window_start >= ? ORDER BY id DESC LIMIT 1',
            [$bucket, $ip, $since]
        );
        if ($row === null) {
            Database::insert(
                'INSERT INTO rate_limits (bucket, ip, hits, window_start) VALUES (?, ?, 1, UTC_TIMESTAMP())',
                [$bucket, $ip]
            );

            return true;
        }
        if ((int) $row['hits'] >= $max) {
            return false;
        }
        Database::exec('UPDATE rate_limits SET hits = hits + 1 WHERE id = ?', [(int) $row['id']]);

        return true;
    }

    public static function sign(array $payload, int $ttlSeconds): string
    {
        $payload['exp'] = time() + $ttlSeconds;
        $json = json_encode($payload);
        $body = rtrim(strtr(base64_encode((string) $json), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $body, Config::key());

        return $body . '.' . $sig;
    }

    public static function unsign(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }
        [$body, $sig] = $parts;
        $expected = hash_hmac('sha256', $body, Config::key());
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $json = base64_decode(strtr($body, '-_', '+/'), true);
        if (!is_string($json)) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || (int) ($data['exp'] ?? 0) < time()) {
            return null;
        }

        return $data;
    }
}
