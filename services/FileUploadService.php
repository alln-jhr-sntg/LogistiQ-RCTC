<?php

/**
 * FileUploadService
 *
 * Handles profile_photo and license_photo uploads.
 * Files are stored under public/uploads/{destDir}/ with a random
 * hex filename. Store only the filename in the DB, not the full path.
 *
 * Display: <img src="/lvms/public/uploads/profile_photos/<?= $filename ?>">
 */
class FileUploadService
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png'];
    private const MAX_BYTES    = 2 * 1024 * 1024; // 2 MB
    private const UPLOAD_BASE  = __DIR__ . '/../public/uploads/';

    /**
     * Validate an uploaded file.
     * Checks upload error, file size, and MIME type via finfo.
     */
    public function validate(
        array $file,
        array $allowedMime = self::ALLOWED_MIME,
        int   $maxBytes    = self::MAX_BYTES
    ): bool {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        if ($file['size'] > $maxBytes) {
            return false;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        return $mime !== false && in_array($mime, $allowedMime, true);
    }

    /**
     * Move a validated file to public/uploads/{destDir}/ and return
     * the stored filename (not the full path).
     *
     * @throws RuntimeException if directory cannot be created or move fails.
     */
    public function store(array $file, string $destDir): string
    {
        $dir = self::UPLOAD_BASE . ltrim($destDir, '/') . '/';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('FileUploadService: cannot create directory: ' . $dir);
        }

        $rawExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $ext     = in_array($rawExt, ['jpg', 'jpeg', 'png'], true) ? $rawExt : 'bin';
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            throw new RuntimeException('FileUploadService: move_uploaded_file failed.');
        }

        return $filename;
    }

    /**
     * Delete a previously stored file. Silent no-op if file doesn't exist.
     */
    public function delete(string $filename, string $destDir): void
    {
        if ($filename === '' || $filename === null) {
            return;
        }
        $path = self::UPLOAD_BASE . ltrim($destDir, '/') . '/' . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
