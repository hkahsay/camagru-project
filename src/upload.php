<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['error' => 'Method not allowed.'], 405);
}

if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
    Response::json(['error' => 'Invalid security token.'], 419);
}

$result = UploadedImage::store($_FILES['image'] ?? []);

if (!$result['ok']) {
    Response::json(['error' => $result['error']], 422);
}

Response::json([
    'message' => 'Image uploaded successfully.',
    'file' => $result['fileName'],
], 201);
