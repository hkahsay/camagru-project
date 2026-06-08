<?php

declare(strict_types=1);

final class PasswordResetToken
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function expiresAt(): string
    {
        return gmdate('Y-m-d H:i:s', time() + 60 * 60);
    }
}
