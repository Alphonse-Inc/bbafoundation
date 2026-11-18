<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Database;
use BBAF\Core\Mailer;

final class Notify
{
    public static function user(int $userId, string $template, array $vars, string $url = ''): void
    {
        $user = Database::one('SELECT id, email, surname, first_name, notify_email FROM users WHERE id = ?', [$userId]);
        if ($user === null) {
            return;
        }
        $name = trim($user['surname'] . ' ' . $user['first_name']);
        $vars['name'] = $vars['name'] ?? $name;
        $title = Mailer::fill(self::subject($template), $vars);
        Database::insert(
            'INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
            [$userId, mb_substr($title, 0, 180), mb_substr(strip_tags((string) ($vars['note'] ?? $title)), 0, 500), $url]
        );
        if ((int) $user['notify_email'] === 1) {
            Mailer::enqueue((string) $user['email'], $name, $template, $vars);
        }
    }

    public static function email(string $email, string $name, string $template, array $vars): void
    {
        $vars['name'] = $vars['name'] ?? $name;
        Mailer::enqueue($email, $name, $template, $vars);
    }

    public static function admins(string $template, array $vars, string $url = ''): void
    {
        $admins = Database::all(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug IN ('super_admin', 'foundation_admin') AND u.status = 'active'"
        );
        foreach ($admins as $admin) {
            self::user((int) $admin['id'], $template, $vars, $url);
        }
    }

    public static function unread(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public static function recent(int $userId, int $limit = 8): array
    {
        return Database::all(
            'SELECT id, title, body, url, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    private static function subject(string $template): string
    {
        $row = Database::one('SELECT subject FROM email_templates WHERE tkey = ?', [$template]);

        return (string) ($row['subject'] ?? 'BBAFoundation');
    }
}
