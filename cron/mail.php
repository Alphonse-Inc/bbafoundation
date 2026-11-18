<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$message = BBAF\Cron\Kernel::run('mail');
echo $message . PHP_EOL;
exit($message === 'Unknown job' ? 1 : 0);
