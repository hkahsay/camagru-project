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

if ($path === '/gallery') {
    $controller->gallery();
    return;
}

if ($path === '/gallery/like') {
    $controller->likeImage();
    return;
}

if ($path === '/gallery/comment') {
    $controller->commentImage();
    return;
}

if ($path === '/gallery/delete') {
    $controller->deleteImage();
    return;
}

if ($path === '/upload') {
    $controller->uploadImage();
    return;
}

if ($path === '/save-image') {
    $controller->saveImage();
    return;
}

if (str_starts_with($path, '/uploads/')) {
    $controller->serveUpload(basename($path));
    return;
}

if ($path === '/forgot') {
    $controller->forgotPassword();
    return;
}

if ($path === '/reset-password') {
    $controller->resetPassword();
    return;
}

if ($path === '/account') {
    $controller->account();
    return;
}

if ($path === '/account/profile') {
    $controller->updateAccount();
    return;
}

if ($path === '/account/password') {
    $controller->updatePassword();
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
