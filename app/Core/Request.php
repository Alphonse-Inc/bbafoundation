<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Request
{
    private static ?self $current = null;
    private array $params = [];

    public function __construct(private string $path, private string $method)
    {
    }

    public static function capture(): self
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = rawurldecode(is_string($uri) ? $uri : '/');
        $base = '';
        if (Config::loaded()) {
            $base = (string) (parse_url((string) Config::get('base_url', ''), PHP_URL_PATH) ?: '');
        }
        if ($base === '' || $base === '/') {
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            if (str_ends_with($script, '/index.php') || $script === 'index.php') {
                $scriptDir = dirname($script);
                if (str_ends_with($scriptDir, '/public')) {
                    $scriptDir = substr($scriptDir, 0, -7);
                }
                if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '') {
                    $base = $scriptDir;
                }
            }
        }
        $base = rtrim($base, '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, 10) ?: '/';
        }
        if ($uri === '') {
            $uri = '/';
        }
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }
        if ($uri !== '/' && str_ends_with($uri, '/') && in_array($method, ['GET', 'HEAD'], true)) {
            $target = rtrim($uri, '/');
            $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
            header('Location: ' . url($target) . ($query !== '' ? '?' . $query : ''), true, 301);
            exit;
        }

        self::$current = new self($uri === '' ? '/' : $uri, $method);

        return self::$current;
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, string $default = ''): string
    {
        return isset($this->params[$key]) ? (string) $this->params[$key] : $default;
    }

    public function query(string $key, ?string $default = null): ?string
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $value = $_GET[$key];

        return is_string($value) ? $value : $default;
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function inputRaw(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]);
    }

    public function file(string $key): ?array
    {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
            return null;
        }
        if ((int) ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $_FILES[$key];
    }

    public function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return str_contains($accept, 'application/json') || strcasecmp($xhr, 'XMLHttpRequest') === 0;
    }

    public function ip(): string
    {
        return client_ip();
    }

    public function fullUrl(): string
    {
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

        return url(ltrim($this->path, '/')) . ($query !== '' ? '?' . $query : '');
    }
}
