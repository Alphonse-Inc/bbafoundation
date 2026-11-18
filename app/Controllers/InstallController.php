<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Csrf;
use BBAF\Core\Request;
use BBAF\Core\Session;
use BBAF\Core\View;
use BBAF\Install\Installer;

final class InstallController
{
    public function handle(): void
    {
        Session::start();
        $request = Request::capture();
        $path = $request->path();
        if ($path !== '/install') {
            View::redirect('/install');
        }
        $checks = Installer::requirements();
        $blocking = Installer::blocking($checks);
        $result = null;
        if ($request->method() === 'POST') {
            if (!Csrf::check(Csrf::fromRequest())) {
                $result = ['ok' => false, 'error' => 'The form expired. Refresh the page and try again.'];
            } else {
                $result = Installer::install([
                    'db_host' => $request->input('db_host', 'localhost'),
                    'db_port' => $request->input('db_port', '3306'),
                    'db_name' => $request->input('db_name'),
                    'db_user' => $request->input('db_user'),
                    'db_pass' => $request->inputRaw('db_pass'),
                    'base_url' => $request->input('base_url', request_base_url()),
                    'admin_surname' => $request->input('admin_surname'),
                    'admin_first' => $request->input('admin_first'),
                    'admin_email' => $request->input('admin_email'),
                    'admin_password' => $request->inputRaw('admin_password'),
                    'demo' => $request->input('demo') === '1',
                ]);
            }
        }
        View::render('install/index', [
            'checks' => $checks,
            'blocking' => $blocking,
            'result' => $result,
            'suggestedBase' => request_base_url(),
            'title' => 'Install BBAFoundation',
        ], null);
    }
}
