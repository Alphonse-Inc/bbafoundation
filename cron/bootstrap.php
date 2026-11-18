<?php

declare(strict_types=1);

if (!defined('BBAF_ROOT')) {
    define('BBAF_ROOT', dirname(__DIR__));
}
define('BBAF_PUBLIC', BBAF_ROOT . '/public');
define('BBAF_FRONT', 'cli');

require BBAF_ROOT . '/app/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'BBAF\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BBAF_ROOT . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = BBAF_ROOT . '/config/config.php';
if (!is_file($config)) {
    fwrite(STDERR, "Install the application before running cron.\n");
    exit(1);
}

BBAF\Core\Config::load($config);
BBAF\Core\Database::boot();
