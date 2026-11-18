<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

echo BBAF\Cron\Kernel::run('sitemap') . PHP_EOL;
