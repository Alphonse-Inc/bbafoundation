<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Middleware
{
    public static function run(string $name, Request $request): void
    {
        if ($name === 'csrf') {
            if (!Csrf::check(Csrf::fromRequest())) {
                if ($request->wantsJson()) {
                    View::json(['ok' => false, 'error' => 'The form expired. Refresh and try again.'], 419);
                    exit;
                }
                flash('error', 'The form expired. Refresh the page and try again.');
                $back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
                View::redirect($back !== '' ? $back : '/');
            }
            return;
        }

        if ($name === 'auth') {
            if (!Auth::check()) {
                $_SESSION['intended'] = $request->fullUrl();
                flash('warning', 'Sign in to continue.');
                View::redirect('/login');
            }
            return;
        }

        if ($name === 'guest') {
            if (Auth::check()) {
                View::redirect(self::homeFor(Auth::role()));
            }
            return;
        }

        if (str_starts_with($name, 'role:')) {
            if (!Auth::check()) {
                $_SESSION['intended'] = $request->fullUrl();
                flash('warning', 'Sign in to continue.');
                View::redirect('/login');
            }
            $allowed = array_values(array_filter(explode(',', substr($name, 5))));
            if (!Auth::can($allowed)) {
                http_response_code(403);
                View::render('public/forbidden', [
                    'title' => 'Not available',
                    'metaDescription' => 'You do not have access to this page.',
                ]);
                exit;
            }
        }
    }

    public static function homeFor(string $role): string
    {
        return match ($role) {
            'super_admin', 'foundation_admin', 'reviewer', 'content_editor' => '/admin',
            'awardee' => '/awardee',
            default => '/dashboard',
        };
    }
}
