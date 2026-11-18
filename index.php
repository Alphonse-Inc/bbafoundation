<?php

declare(strict_types=1);

/**
 * Front controller when the host cannot change the document root
 * and the repository root itself is the web root.
 */
define('BBAF_ROOT', __DIR__);
define('BBAF_PUBLIC', __DIR__ . '/public');
define('BBAF_FRONT', 'root');

require BBAF_ROOT . '/app/bootstrap.php';
