<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['error' => 'Method not allowed.'], 405);
}

if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
    Response::json(['error' => 'Invalid security token.'], 419);
}

Response::json(['message' => 'Save image endpoint is protected and ready for image data validation.']);
