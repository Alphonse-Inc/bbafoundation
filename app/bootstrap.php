<?php

declare(strict_types=1);

/**
 * Boots the BBAFoundation application for both front controllers.
 * The installer is allowed to run before config/config.php exists.
 */

require_once __DIR__ . '/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'BBAF\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BBAF_ROOT . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use BBAF\Controllers\InstallController;
use BBAF\Core\App;
use BBAF\Core\Config;
use BBAF\Core\Database;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $error): void {
    $dir = BBAF_ROOT . '/storage/logs';
    if (is_dir($dir) && is_writable($dir)) {
        $line = gmdate('c') . ' ' . $error->getMessage()
            . ' in ' . $error->getFile() . ':' . $error->getLine() . PHP_EOL;
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $debug = Config::loaded() && (bool) Config::get('debug', false);
    if ($debug) {
        echo '<pre>' . e($error->getMessage() . "\n" . $error->getFile() . ':' . $error->getLine()) . '</pre>';
        return;
    }
    echo '<!DOCTYPE html><html lang="en"><meta charset="utf-8"><title>Something went wrong</title>'
        . '<body style="font-family:system-ui,sans-serif;padding:2rem">'
        . '<h1>Something went wrong</h1><p>Please try again in a moment. If this continues, contact info@bbafoundation.com.</p>'
        . '</body></html>';
});

$configFile = BBAF_ROOT . '/config/config.php';
$lockFile = BBAF_ROOT . '/storage/install.lock';
$installed = is_file($configFile) && is_file($lockFile);

if (!$installed) {
    (new InstallController())->handle();
    return;
}

Config::load($configFile);
if (!(bool) Config::get('debug', false)) {
    ini_set('display_errors', '0');
}

if (!ini_get('zlib.output_compression') && extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

try {
    Database::boot();
} catch (Throwable $error) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><meta charset="utf-8"><title>Database unavailable</title>'
        . '<body style="font-family:system-ui,sans-serif;padding:2rem">'
        . '<h1>The site cannot reach its database</h1>'
        . '<p>Check the credentials in the hosting control panel and config/config.php.</p>'
        . '</body></html>';
    $dir = BBAF_ROOT . '/storage/logs';
    if (is_dir($dir)) {
        @file_put_contents($dir . '/app.log', gmdate('c') . ' DB ' . $error->getMessage() . PHP_EOL, FILE_APPEND);
    }
    return;
}

(new App())->run();
