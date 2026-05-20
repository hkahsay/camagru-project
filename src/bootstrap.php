<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');
define('VIEW_PATH', SRC_PATH . '/View');

spl_autoload_register(static function (string $className): void {
    $paths = [
        SRC_PATH . '/Controller/' . $className . '.php',
        SRC_PATH . '/Model/' . $className . '.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

require_once VIEW_PATH . '/helpers.php';
require_once VIEW_PATH . '/View.php';
