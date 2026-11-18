<?php

declare(strict_types=1);

/**
 * Copy of the configuration written by /install.
 * Do not put real passwords in this example. The installer writes config/config.php,
 * which is gitignored and blocked by config/.htaccess.
 */
return [
    'installed' => false,
    'base_url' => 'https://bbafoundation.com',
    'timezone' => 'Africa/Lagos',
    'debug' => false,
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'bbafoundation',
        'user' => 'bbafoundation',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // 32-byte key, base64. Used for NIN encryption, signed download tokens and remember-me.
    'key' => '',
    'cron_key' => '',
    'session_name' => 'BBAFSESSID',
];
