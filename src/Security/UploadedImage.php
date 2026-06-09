<?php

declare(strict_types=1);

final class UploadedImage
{
    private const MAX_BYTES = 2_000_000;
    private const OVERLAYS = [
        'frame' => 'camera-frame.png',
        'sunglasses' => 'sunglasses.png',
        'stars' => 'stars.png',
    ];
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

    public static function storeDataUrl(string $dataUrl): array
    {
        if (!preg_match('/^data:(image\/(?:jpeg|png|webp));base64,(.+)$/', $dataUrl, $matches)) {
            return ['ok' => false, 'error' => 'Image data is invalid.'];
        }

        $mimeType = $matches[1];
        $imageData = base64_decode($matches[2], true);

        if ($imageData === false) {
            return ['ok' => false, 'error' => 'Image data is invalid.'];
        }

        if (strlen($imageData) > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'Image must be 2MB or smaller.'];
        }

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        $temporaryPath = tempnam(UPLOAD_PATH, 'camagru-');

        if ($temporaryPath === false || file_put_contents($temporaryPath, $imageData, LOCK_EX) === false) {
            return ['ok' => false, 'error' => 'Could not save the image.'];
        }

        if (getimagesize($temporaryPath) === false) {
            unlink($temporaryPath);
            return ['ok' => false, 'error' => 'The image data is not a valid image.'];
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONS[$mimeType];
        $targetPath = UPLOAD_PATH . '/' . $fileName;

        if (!rename($temporaryPath, $targetPath)) {
            unlink($temporaryPath);
            return ['ok' => false, 'error' => 'Could not save the image.'];
        }

        return [
            'ok' => true,
            'path' => $targetPath,
            'fileName' => $fileName,
        ];
    }

    public static function storeComposedDataUrl(string $dataUrl, string $overlayKey): array
    {
        if (!extension_loaded('gd')) {
            return ['ok' => false, 'error' => 'Image editing is not available on the server.'];
        }

        if (!isset(self::OVERLAYS[$overlayKey])) {
            return ['ok' => false, 'error' => 'Select a valid superposable image.'];
        }

        if (!preg_match('/^data:image\/(?:jpeg|png|webp);base64,(.+)$/', $dataUrl, $matches)) {
            return ['ok' => false, 'error' => 'Image data is invalid.'];
        }

        $imageData = base64_decode($matches[1], true);

        if ($imageData === false) {
            return ['ok' => false, 'error' => 'Image data is invalid.'];
        }

        if (strlen($imageData) > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'Image must be 2MB or smaller.'];
        }

        $baseImage = imagecreatefromstring($imageData);

        if ($baseImage === false) {
            return ['ok' => false, 'error' => 'The image data is not a valid image.'];
        }

        $overlayPath = PUBLIC_PATH . '/overlays/' . self::OVERLAYS[$overlayKey];
        $overlayImage = is_file($overlayPath) ? imagecreatefrompng($overlayPath) : false;

        if ($overlayImage === false) {
            imagedestroy($baseImage);
            return ['ok' => false, 'error' => 'The selected superposable image is unavailable.'];
        }

        $width = imagesx($baseImage);
        $height = imagesy($baseImage);
        $finalImage = imagecreatetruecolor($width, $height);

        if ($finalImage === false) {
            imagedestroy($baseImage);
            imagedestroy($overlayImage);
            return ['ok' => false, 'error' => 'Could not create the final image.'];
        }

        imagecopyresampled($finalImage, $baseImage, 0, 0, 0, 0, $width, $height, $width, $height);
        imagealphablending($finalImage, true);
        imagecopyresampled(
            $finalImage,
            $overlayImage,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($overlayImage),
            imagesy($overlayImage)
        );

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        $fileName = bin2hex(random_bytes(16)) . '.jpg';
        $targetPath = UPLOAD_PATH . '/' . $fileName;
        $saved = imagejpeg($finalImage, $targetPath, 92);

        imagedestroy($baseImage);
        imagedestroy($overlayImage);
        imagedestroy($finalImage);

        if (!$saved) {
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
