<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Database;
use BBAF\Core\View;

final class RedirectMap
{
    public static function attempt(string $path): bool
    {
        $row = Database::one('SELECT target_path, status_code FROM redirects WHERE source_path = ? AND is_active = 1', [$path]);
        if ($row === null) {
            return false;
        }
        $code = (int) $row['status_code'];
        if ($code === 410) {
            http_response_code(410);
            View::render('public/gone', [
                'title' => 'Gone',
                'metaDescription' => 'This address belonged to the previous website and is no longer available.',
            ]);
            return true;
        }
        $target = (string) $row['target_path'];
        if ($target === '') {
            return false;
        }
        header('Location: ' . (preg_match('#^https?://#', $target) ? $target : url($target)), true, $code === 302 ? 302 : 301);

        return true;
    }
}
