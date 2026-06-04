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

    public function findByLogin(string $login): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, username, email, password_hash, email_verified_at
            FROM users
            WHERE username_normalized = :username_login
            OR email_normalized = :email_login
            LIMIT 1'
        );
        $normalizedLogin = self::normalize($login);
        $statement->execute([
            'username_login' => $normalizedLogin,
            'email_login' => $normalizedLogin,
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(
        string $username,
        string $email,
        string $passwordHash,
        string $verificationTokenHash,
        string $verificationExpiresAt
    ): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (
                username,
                username_normalized,
                email,
                email_normalized,
                password_hash,
                verification_token_hash,
                verification_expires_at,
                created_at
            ) VALUES (
                :username,
                :username_normalized,
                :email,
                :email_normalized,
                :password_hash,
                :verification_token_hash,
                :verification_expires_at,
                :created_at
            )'
        );

        $statement->execute([
            'username' => $username,
            'username_normalized' => self::normalize($username),
            'email' => $email,
            'email_normalized' => self::normalize($email),
            'password_hash' => $passwordHash,
            'verification_token_hash' => $verificationTokenHash,
            'verification_expires_at' => $verificationExpiresAt,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function verifyEmail(string $token): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT id, verification_expires_at
            FROM users
            WHERE verification_token_hash = :token_hash
            AND email_verified_at IS NULL
            LIMIT 1'
        );
        $statement->execute([
            'token_hash' => EmailVerificationToken::hash($token),
        ]);

        $user = $statement->fetch();

        if ($user === false || strtotime((string) $user['verification_expires_at']) < time()) {
            return false;
        }

        $update = Database::connection()->prepare(
            'UPDATE users
            SET email_verified_at = :verified_at,
                verification_token_hash = NULL,
                verification_expires_at = NULL
            WHERE id = :id'
        );

        $update->execute([
            'verified_at' => gmdate('Y-m-d H:i:s'),
            'id' => $user['id'],
        ]);

        return $update->rowCount() === 1;
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
