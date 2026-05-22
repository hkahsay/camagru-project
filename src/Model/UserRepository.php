<?php

declare(strict_types=1);

final class UserRepository
{
    public function existsByUsername(string $username): bool
    {
        return $this->exists('username_normalized', self::normalize($username));
    }

    public function existsByEmail(string $email): bool
    {
        return $this->exists('email_normalized', self::normalize($email));
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (
                username,
                username_normalized,
                email,
                email_normalized,
                password_hash,
                created_at
            ) VALUES (
                :username,
                :username_normalized,
                :email,
                :email_normalized,
                :password_hash,
                :created_at
            )'
        );

        $statement->execute([
            'username' => $username,
            'username_normalized' => self::normalize($username),
            'email' => $email,
            'email_normalized' => self::normalize($email),
            'password_hash' => $passwordHash,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private function exists(string $column, string $value): bool
    {
        $allowedColumns = ['username_normalized', 'email_normalized'];

        if (!in_array($column, $allowedColumns, true)) {
            throw new InvalidArgumentException('Invalid user lookup column.');
        }

        $statement = Database::connection()->prepare(
            'SELECT 1 FROM users WHERE ' . $column . ' = :value LIMIT 1'
        );
        $statement->execute(['value' => $value]);

        return $statement->fetchColumn() !== false;
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
