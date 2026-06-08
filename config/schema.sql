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

CREATE TABLE IF NOT EXISTS images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY images_file_name_unique (file_name),
    INDEX images_created_at_index (created_at),
    CONSTRAINT images_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS image_likes (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS image_comments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
