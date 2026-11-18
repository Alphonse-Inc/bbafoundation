<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Config;
use BBAF\Core\Request;
use BBAF\Cron\Kernel;

final class CronController
{
    public function run(Request $request): void
    {
        $expected = (string) Config::get('cron_key', '');
        $given = (string) $request->query('key', '');
        if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Forbidden';
            return;
        }
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        $message = Kernel::run($request->param('job'));
        if ($message === 'Unknown job') {
            http_response_code(404);
        }
        echo $message;
    }
}
