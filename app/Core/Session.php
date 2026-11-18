<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $path = BBAF_ROOT . '/storage/sessions';
        if (is_dir($path) && is_writable($path)) {
            session_save_path($path);
        }
        $cookiePath = '/';
        $secure = self::https();
        $name = 'BBAFSESSID';
        if (Config::loaded()) {
            $name = (string) Config::get('session_name', $name);
            $basePath = (string) (parse_url((string) Config::get('base_url', ''), PHP_URL_PATH) ?: '/');
            $cookiePath = $basePath === '' ? '/' : $basePath;
            if (!str_ends_with($cookiePath, '/')) {
                $cookiePath .= '/';
            }
            if (str_starts_with((string) Config::get('base_url', ''), 'https://')) {
                $secure = true;
            }
        }
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        if (!isset($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = time();
        }
        $_SESSION['_old_current'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    }
}
