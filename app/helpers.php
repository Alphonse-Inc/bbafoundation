<?php

declare(strict_types=1);

use BBAF\Core\Config;
use BBAF\Core\Csrf;
use BBAF\Core\Dates;
use BBAF\Core\Purify;
use BBAF\Core\Settings;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rich(mixed $html): string
{
    return Purify::clean((string) $html);
}

function request_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if (str_ends_with($dir, '/public')) {
        $dir = substr($dir, 0, -7);
    }
    if ($dir === '/' || $dir === '.' || $dir === '') {
        $dir = '';
    }

    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function url(string $path = ''): string
{
    if (class_exists(Config::class) && Config::loaded()) {
        $configured = (string) Config::get('base_url', '');
        $base = $configured !== '' ? rtrim($configured, '/') : rtrim(request_base_url(), '/');
    } else {
        $base = rtrim(request_base_url(), '/');
    }
    if ($path === '' || $path === '/') {
        return $base . '/';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $full = BBAF_PUBLIC . '/assets/' . $relative;
    $version = is_file($full) ? (string) filemtime($full) : '1';

    return url('assets/' . $relative) . '?v=' . $version;
}

function setting(string $key, string $default = ''): string
{
    if (!class_exists(Settings::class) || !\BBAF\Core\Database::ready()) {
        return $default;
    }

    return Settings::get($key, $default);
}

function label(string $key, string $default): string
{
    if (!class_exists(Settings::class) || !\BBAF\Core\Database::ready()) {
        return $default;
    }
    $override = Settings::label($key);
    if ($override !== null && $override !== '') {
        return $override;
    }

    return $default;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function flash(string $type, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$type] = $message;
        return null;
    }
    if (!isset($_SESSION['_flash'][$type])) {
        return null;
    }
    $value = (string) $_SESSION['_flash'][$type];
    unset($_SESSION['_flash'][$type]);

    return $value;
}

function flashes(): array
{
    $bag = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($bag) ? $bag : [];
}

function remember_input(array $input): void
{
    $clean = [];
    foreach ($input as $key => $value) {
        if (is_string($key) && (is_string($value) || is_numeric($value))) {
            $clean[$key] = (string) $value;
        }
    }
    $_SESSION['_old'] = $clean;
}

function old(string $key, string $default = ''): string
{
    $bag = $_SESSION['_old_current'] ?? [];
    $value = $bag[$key] ?? $default;

    return is_string($value) ? $value : $default;
}

function display_date(?string $utc, bool $withTime = false): string
{
    return Dates::display($utc, $withTime);
}

function word_count(string $text): int
{
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));
    if ($text === '') {
        return 0;
    }

    return count(explode(' ', $text));
}

function money_input(?string $amount): string
{
    if ($amount === null || $amount === '') {
        return '';
    }

    return number_format((float) $amount, 2, '.', ',');
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return substr($ip, 0, 45);
}

function client_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

function selected($left, $right): string
{
    return (string) $left === (string) $right ? ' selected' : '';
}

function checked($value): string
{
    return $value ? ' checked' : '';
}

function post_value(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function logo_src(bool $onDark = true): string
{
    $key = $onDark ? 'logo_path' : 'logo_dark_path';
    $configured = setting($key);
    if ($configured !== '' && is_file(BBAF_PUBLIC . '/' . ltrim($configured, '/'))) {
        return url(ltrim($configured, '/'));
    }
    $legacy = BBAF_PUBLIC . '/assets/img/legacy/BBAF-logo.png';
    if (is_file($legacy)) {
        return asset('img/legacy/BBAF-logo.png');
    }

    return asset($onDark ? 'img/placeholders/logo.svg' : 'img/placeholders/logo-dark.svg');
}

function favicon_src(): string
{
    $configured = setting('favicon_path');
    if ($configured !== '' && is_file(BBAF_PUBLIC . '/' . ltrim($configured, '/'))) {
        return url(ltrim($configured, '/'));
    }
    $legacy = BBAF_PUBLIC . '/assets/img/legacy/cropped-BBAF_logo-siteicon-270x270.png';
    if (is_file($legacy)) {
        return asset('img/legacy/cropped-BBAF_logo-siteicon-270x270.png');
    }

    return asset('img/placeholders/favicon.svg');
}

function legacy_src(string $filename): string
{
    $safe = basename($filename);
    $path = BBAF_PUBLIC . '/assets/img/legacy/' . $safe;
    if ($safe !== '' && is_file($path)) {
        return asset('img/legacy/' . $safe);
    }

    return asset('img/placeholders/pending.svg');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'B';
}

function send_stored_file(string $stored, string $downloadName, string $mime, bool $inline = false): void
{
    $path = \BBAF\Services\Applications::privatePath($stored);
    if (!is_file($path)) {
        http_response_code(404);
        echo 'File not found';
        return;
    }
    $safe = str_replace(['"', "\r", "\n"], '', $downloadName);
    header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safe . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
}

function content_cache_clear(): void
{
    foreach (['settings', 'labels', 'slides', 'blocks', 'trustees', 'faqs', 'affiliates', 'pages', 'public-cycle'] as $key) {
        \BBAF\Core\Cache::forget($key);
    }
}
