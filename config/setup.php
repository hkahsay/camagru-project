<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$schema = file_get_contents(__DIR__ . '/schema.sql');

if ($schema === false) {
    throw new RuntimeException('Could not read database schema.');
}

Database::connection()->exec($schema);
ensureVerificationColumns();
ensurePasswordResetColumns();
ensurePreferenceColumns();
ensureGalleryTables();

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

function ensurePasswordResetColumns(): void
{
    $columns = existingUserColumns();
    $connection = Database::connection();

    if (!in_array('password_reset_token_hash', $columns, true)) {
        $connection->exec('ALTER TABLE users ADD COLUMN password_reset_token_hash CHAR(64) NULL AFTER verification_expires_at');
    }

    if (!in_array('password_reset_expires_at', $columns, true)) {
        $connection->exec('ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL AFTER password_reset_token_hash');
    }

    if (!in_array('users_password_reset_token_hash_index', existingUserIndexes(), true)) {
        $connection->exec('CREATE INDEX users_password_reset_token_hash_index ON users (password_reset_token_hash)');
    }
}

function ensurePreferenceColumns(): void
{
    $columns = existingUserColumns();

    if (!in_array('comment_notifications_enabled', $columns, true)) {
        Database::connection()->exec(
            'ALTER TABLE users
            ADD COLUMN comment_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1
            AFTER password_reset_expires_at'
        );
    }
}

function ensureGalleryTables(): void
{
    Database::connection()->exec('CREATE TABLE IF NOT EXISTS images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        file_name VARCHAR(80) NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY images_file_name_unique (file_name),
        INDEX images_created_at_index (created_at),
        CONSTRAINT images_user_id_foreign
            FOREIGN KEY (user_id) REFERENCES users (id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    Database::connection()->exec('CREATE TABLE IF NOT EXISTS image_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY image_likes_image_user_unique (image_id, user_id),
        CONSTRAINT image_likes_image_id_foreign
            FOREIGN KEY (image_id) REFERENCES images (id)
            ON DELETE CASCADE,
        CONSTRAINT image_likes_user_id_foreign
            FOREIGN KEY (user_id) REFERENCES users (id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    Database::connection()->exec('CREATE TABLE IF NOT EXISTS image_comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX image_comments_image_id_index (image_id),
        CONSTRAINT image_comments_image_id_foreign
            FOREIGN KEY (image_id) REFERENCES images (id)
            ON DELETE CASCADE,
        CONSTRAINT image_comments_user_id_foreign
            FOREIGN KEY (user_id) REFERENCES users (id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
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
