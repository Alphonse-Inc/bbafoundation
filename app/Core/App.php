<?php

declare(strict_types=1);

namespace BBAF\Core;

final class App
{
    public function run(): void
    {
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        $GLOBALS['bbaf_nonce'] = $nonce;
        Security::headers($nonce);
        Session::start();
        Auth::restoreRemember();
        $request = Request::capture();
        if (!Auth::touchIdle() && !in_array($request->path(), ['/login', '/forgot-password'], true)) {
            View::redirect('/login');
        }
        if (Settings::get('maintenance_mode', '0') === '1' && !$this->allowedDuringMaintenance($request)) {
            http_response_code(503);
            header('Retry-After: 3600');
            View::render('public/maintenance', [
                'title' => 'Maintenance',
                'metaDescription' => 'BBAFoundation is briefly unavailable.',
            ], 'bare');
            return;
        }
        $router = new Router();
        $router->load(BBAF_ROOT . '/app/routes.php');
        $router->dispatch($request);
    }

    private function allowedDuringMaintenance(Request $request): bool
    {
        $path = $request->path();
        $prefixes = ['/login', '/two-factor', '/forgot-password', '/reset-password', '/admin', '/logout', '/cron'];
        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/') || $path === $prefix) {
                return true;
            }
        }
        $role = Auth::role();

        return $role !== '' && Auth::isStaff($role);
    }
}
