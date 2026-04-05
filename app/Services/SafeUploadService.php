<?php
namespace App\Services;

final class SafeUploadService
{
    public static function uploadImage(array $file, string $targetDir, int $maxBytes = 2097152): array
    {
        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'upload-error'];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['ok' => false, 'error' => 'invalid-upload'];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return ['ok' => false, 'error' => 'size-invalid'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'error' => 'mime-invalid'];
        }

        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            return ['ok' => false, 'error' => 'not-image'];
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['ok' => false, 'error' => 'mkdir-failed'];
        }

        try {
            $token = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $token = uniqid('img', true);
        }

        $ext = $allowed[$mime];
        $name = 'img_' . date('YmdHis') . '_' . $token . '.' . $ext;
        $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($tmpPath, $dest)) {
            return ['ok' => false, 'error' => 'move-failed'];
        }

        @chmod($dest, 0644);

        return [
            'ok' => true,
            'filename' => $name,
            'path' => $dest,
            'mime' => $mime,
        ];
    }
}
