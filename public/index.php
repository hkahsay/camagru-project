<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$controller = new HomeController();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/register') {
    $controller->register();
    return;
}

if ($path === '/login') {
    $controller->login();
    return;
}

if ($path === '/logout') {
    $controller->logout();
    return;
}

if ($path === '/verify-email') {
    $controller->verifyEmail();
    return;
}

$controller->index();
