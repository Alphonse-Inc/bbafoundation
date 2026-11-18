<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function check(?string $token): bool
    {
        $known = $_SESSION['_csrf'] ?? '';
        if (!is_string($known) || $known === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($known, $token);
    }

    public static function fromRequest(): string
    {
        if (isset($_POST['_csrf']) && is_string($_POST['_csrf'])) {
            return $_POST['_csrf'];
        }
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        return is_string($header) ? $header : '';
    }
}
