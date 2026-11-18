<?php

declare(strict_types=1);

/**
 * Front controller when the host document root is /public.
 */
define('BBAF_ROOT', dirname(__DIR__));
define('BBAF_PUBLIC', __DIR__);
define('BBAF_FRONT', 'public');

require BBAF_ROOT . '/app/bootstrap.php';
