<?php

declare(strict_types=1);

namespace BBAF\Core;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'public'): void
    {
        $data['csrf'] = Csrf::token();
        $data['authUser'] = Auth::user();
        $data['currentPath'] = Request::current()?->path() ?? '/';
        $html = self::capture($template, $data);
        if ($layout !== null) {
            $data['content'] = $html;
            $html = self::capture('layouts/' . $layout, $data);
        }
        self::send($html, $layout === null);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $path, int $status = 302): void
    {
        header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)), true, $status);
        exit;
    }

    private static function capture(string $template, array $data): string
    {
        $file = BBAF_ROOT . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Missing view: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    private static function send(string $html, bool $raw): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!$raw && in_array($method, ['GET', 'HEAD'], true)) {
            $etag = '"' . substr(sha1($html), 0, 20) . '"';
            header('ETag: ' . $etag);
            header('Cache-Control: private, max-age=0, must-revalidate');
            if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                http_response_code(304);
                return;
            }
        } else {
            header('Cache-Control: no-store');
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
    }
}
