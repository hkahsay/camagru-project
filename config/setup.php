<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$schema = file_get_contents(__DIR__ . '/schema.sql');

if ($schema === false) {
    throw new RuntimeException('Could not read database schema.');
}

Database::connection()->exec($schema);
ensureVerificationColumns();

echo "Database setup completed.\n";

function ensureVerificationColumns(): void
{
    $columns = existingUserColumns();
    $connection = Database::connection();

    if (!in_array('email_verified_at', $columns, true)) {
        $connection->exec('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER password_hash');
    }

    if (!in_array('verification_token_hash', $columns, true)) {
        $connection->exec('ALTER TABLE users ADD COLUMN verification_token_hash CHAR(64) NULL AFTER email_verified_at');
    }

    if (!in_array('verification_expires_at', $columns, true)) {
        $connection->exec('ALTER TABLE users ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_token_hash');
    }

    if (!in_array('users_verification_token_hash_index', existingUserIndexes(), true)) {
        $connection->exec('CREATE INDEX users_verification_token_hash_index ON users (verification_token_hash)');
    }
}

function existingUserColumns(): array
{
    $statement = Database::connection()->query('SHOW COLUMNS FROM users');

    return array_map(
        static fn (array $column): string => (string) $column['Field'],
        $statement->fetchAll()
    );
}

function existingUserIndexes(): array
{
    $statement = Database::connection()->query('SHOW INDEX FROM users');

    return array_values(array_unique(array_map(
        static fn (array $index): string => (string) $index['Key_name'],
        $statement->fetchAll()
    )));
}
