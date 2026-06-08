CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    username_normalized VARCHAR(30) NOT NULL,
    email VARCHAR(190) NOT NULL,
    email_normalized VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    verification_token_hash CHAR(64) NULL,
    verification_expires_at DATETIME NULL,
    password_reset_token_hash CHAR(64) NULL,
    password_reset_expires_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY users_username_normalized_unique (username_normalized),
    UNIQUE KEY users_email_normalized_unique (email_normalized),
    INDEX users_verification_token_hash_index (verification_token_hash),
    INDEX users_password_reset_token_hash_index (password_reset_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
