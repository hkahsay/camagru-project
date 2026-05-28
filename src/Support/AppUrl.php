<?php

declare(strict_types=1);

final class AppUrl
{
    public static function to(string $path): string
    {
        $baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');

        return $baseUrl . '/' . ltrim($path, '/');
    }
}
