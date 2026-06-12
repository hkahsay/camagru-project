<?php

declare(strict_types=1);

final class ImageRepository
{
    public function create(int $userId, string $fileName): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO images (user_id, file_name, created_at)
            VALUES (:user_id, :file_name, :created_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'file_name' => $fileName,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, user_id, file_name, created_at
            FROM images
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $image = $statement->fetch();

        return $image === false ? null : $image;
    }

    public function findWithAuthor(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                images.id,
                images.user_id,
                images.file_name,
                images.created_at,
                users.username,
                users.email,
                users.comment_notifications_enabled
            FROM images
            INNER JOIN users ON users.id = images.user_id
            WHERE images.id = :id
            LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
        ]);

        $image = $statement->fetch();

        return $image === false ? null : $image;
    }

    public function all(?int $viewerId = null, ?int $limit = null, int $offset = 0): array
    {
        $sql = 'SELECT
                images.id,
                images.user_id,
                images.file_name,
                images.created_at,
                users.username,
                COUNT(DISTINCT image_likes.id) AS likes_count,
                COUNT(DISTINCT image_comments.id) AS comments_count,
                MAX(CASE WHEN viewer_likes.user_id IS NULL THEN 0 ELSE 1 END) AS liked_by_viewer
            FROM images
            INNER JOIN users ON users.id = images.user_id
            LEFT JOIN image_likes ON image_likes.image_id = images.id
            LEFT JOIN image_comments ON image_comments.image_id = images.id
            LEFT JOIN image_likes AS viewer_likes
                ON viewer_likes.image_id = images.id
                AND viewer_likes.user_id = :viewer_id
            GROUP BY images.id, images.user_id, images.file_name, images.created_at, users.username
            ORDER BY images.created_at DESC, images.id DESC';
        $parameters = [
            'viewer_id' => $viewerId,
        ];

        if ($limit !== null) {
            $sql .= ' LIMIT :limit OFFSET :offset';
            $parameters['limit'] = max(1, $limit);
            $parameters['offset'] = max(0, $offset);
        }

        $statement = Database::connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue(
                ':' . $name,
                $value,
                $type
            );
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    public function countAll(): int
    {
        $statement = Database::connection()->query('SELECT COUNT(*) FROM images');

        return (int) $statement->fetchColumn();
    }

    public function forUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, file_name, created_at
            FROM images
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC'
        );
        $statement->execute([
            'user_id' => $userId,
        ]);

        return $statement->fetchAll();
    }

    public function commentsFor(int $imageId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT image_comments.body, image_comments.created_at, users.username
            FROM image_comments
            INNER JOIN users ON users.id = image_comments.user_id
            WHERE image_comments.image_id = :image_id
            ORDER BY image_comments.created_at ASC, image_comments.id ASC'
        );
        $statement->execute([
            'image_id' => $imageId,
        ]);

        return $statement->fetchAll();
    }

    public function hasLiked(int $imageId, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM image_likes
            WHERE image_id = :image_id
            AND user_id = :user_id
            LIMIT 1'
        );
        $statement->execute([
            'image_id' => $imageId,
            'user_id' => $userId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function toggleLike(int $imageId, int $userId): void
    {
        if ($this->hasLiked($imageId, $userId)) {
            $statement = Database::connection()->prepare(
                'DELETE FROM image_likes
                WHERE image_id = :image_id
                AND user_id = :user_id'
            );
            $statement->execute([
                'image_id' => $imageId,
                'user_id' => $userId,
            ]);

            return;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO image_likes (image_id, user_id, created_at)
            VALUES (:image_id, :user_id, :created_at)'
        );
        $statement->execute([
            'image_id' => $imageId,
            'user_id' => $userId,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function addComment(int $imageId, int $userId, string $body): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO image_comments (image_id, user_id, body, created_at)
            VALUES (:image_id, :user_id, :body, :created_at)'
        );
        $statement->execute([
            'image_id' => $imageId,
            'user_id' => $userId,
            'body' => $body,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function deleteOwnedBy(int $imageId, int $userId): ?string
    {
        $image = $this->find($imageId);

        if ($image === null || (int) $image['user_id'] !== $userId) {
            return null;
        }

        $statement = Database::connection()->prepare(
            'DELETE FROM images
            WHERE id = :id
            AND user_id = :user_id'
        );
        $statement->execute([
            'id' => $imageId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() === 1 ? (string) $image['file_name'] : null;
    }
}
