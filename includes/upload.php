<?php
declare(strict_types=1);

/**
 * SMS — Secure file upload helper
 * Path: includes/upload.php
 */

if (defined('SMS_UPLOAD_LOADED')) { return; }
define('SMS_UPLOAD_LOADED', true);

/**
 * Validate and store an uploaded image.
 *
 * @param array  $file     $_FILES['photo'] entry
 * @param string $subdir   Sub-directory under /uploads (e.g. 'profiles')
 * @param string $prefix   Filename prefix (e.g. 'student')
 * @return array{ok:bool, message:string, filename:?string}
 */
function upload_image(array $file, string $subdir = 'profiles', string $prefix = 'img'): array
{
    // 1) Basic upload errors
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'message' => 'Invalid upload payload.', 'filename' => null];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['ok' => false, 'message' => 'No file was uploaded.', 'filename' => null];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['ok' => false, 'message' => 'File is too large.', 'filename' => null];
        default:
            return ['ok' => false, 'message' => 'Upload failed. Please try again.', 'filename' => null];
    }

    // 2) File size
    if (($file['size'] ?? 0) <= 0 || $file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'message' => 'File size must be under ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB.', 'filename' => null];
    }

    // 3) Real MIME check via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';

    if (!array_key_exists($mime, ALLOWED_IMAGE_MIME)) {
        return ['ok' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.', 'filename' => null];
    }

    // 4) Verify it is a real image (prevents MIME spoofing)
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'message' => 'The uploaded file is not a valid image.', 'filename' => null];
    }

    // 5) Dimension sanity (min 40x40, max 4000x4000)
    [$w, $h] = $info;
    if ($w < 40 || $h < 40) {
        return ['ok' => false, 'message' => 'Image must be at least 40x40 pixels.', 'filename' => null];
    }
    if ($w > 4000 || $h > 4000) {
        return ['ok' => false, 'message' => 'Image dimensions are too large (max 4000x4000).', 'filename' => null];
    }

    // 6) Prepare destination
    $ext      = ALLOWED_IMAGE_MIME[$mime];             // jpg | png | webp
    $safeName = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    $baseDir = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($subdir, '/\\');
    if (!is_dir($baseDir) && !@mkdir($baseDir, 0755, true)) {
        return ['ok' => false, 'message' => 'Upload directory is not writable.', 'filename' => null];
    }

    $dest = $baseDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'message' => 'Could not save the uploaded file.', 'filename' => null];
    }

    @chmod($dest, 0644);

    return ['ok' => true, 'message' => 'Uploaded.', 'filename' => $safeName];
}

/**
 * Delete a previously uploaded file safely.
 */
function delete_upload(string $filename, string $subdir = 'profiles'): void
{
    if ($filename === '') return;

    $filename = basename($filename); // prevent path traversal
    $path     = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
              . trim($subdir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (is_file($path)) {
        @unlink($path);
    }
}