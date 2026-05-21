<?php

declare(strict_types=1);

final class UploadedImage
{
    private const MAX_BYTES = 2_000_000;
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function store(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }

        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'Image must be 2MB or smaller.'];
        }

        $tmpPath = $file['tmp_name'] ?? '';

        if (!is_uploaded_file($tmpPath)) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        $mimeType = self::mimeType($tmpPath);

        if (!isset(self::EXTENSIONS[$mimeType])) {
            return ['ok' => false, 'error' => 'Only JPG, PNG, and WebP images are allowed.'];
        }

        if (getimagesize($tmpPath) === false) {
            return ['ok' => false, 'error' => 'The uploaded file is not a valid image.'];
        }

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONS[$mimeType];
        $targetPath = UPLOAD_PATH . '/' . $fileName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            return ['ok' => false, 'error' => 'Could not save the image.'];
        }

        return [
            'ok' => true,
            'path' => $targetPath,
            'fileName' => $fileName,
        ];
    }

    private static function mimeType(string $path): string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->file($path) ?: '';
    }
}
