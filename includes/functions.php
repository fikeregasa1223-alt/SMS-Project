<?php
declare(strict_types=1);

/** HTML escape */
function e(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a URL relative to BASE_URL */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/** Absolute path on disk */
function path(string $rel = ''): string
{
    return rtrim(APP_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($rel, '/\\');
}

/** Redirect and exit */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** JSON output helper */
function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Read GET/POST cleanly */
function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}
function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/** CSRF-safe trim */
function clean(?string $v): string
{
    return trim((string)$v);
}

/** Slug generator */
function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('~[^a-z0-9]+~', '-', $s) ?? '';
    return trim($s, '-');
}

/** Generate a random code like ST-2025-0001 */
function make_code(string $prefix, int $n, int $pad = 4): string
{
    return sprintf('%s-%s', $prefix, str_pad((string)$n, $pad, '0', STR_PAD_LEFT));
}

/** Safe filenames for uploads */
function safe_filename(string $original, string $ext): string
{
    $base = pathinfo($original, PATHINFO_FILENAME);
    $base = preg_replace('~[^A-Za-z0-9_-]+~', '_', $base) ?? 'file';
    return $base . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
}

/** Simple audit logger */
function audit(?int $userId, string $action, ?string $entity = null, ?int $entityId = null, ?string $desc = null): void
{
    try {
        Database::insert('audit_logs', [
            'user_id'     => $userId,
            'action'      => $action,
            'entity'      => $entity,
            'entity_id'   => $entityId,
            'description' => $desc,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
        ]);
    } catch (Throwable $e) {
        error_log('[SMS][audit] ' . $e->getMessage());
    }
}

/** Retrieve a school setting */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $row = Database::fetch('SELECT setting_value FROM school_settings WHERE setting_key = ?', [$key]);
    $cache[$key] = $row['setting_value'] ?? $default;
    return $cache[$key];
}

/** Active menu helper */
function is_active(string $needle): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $needle);
}

/** Format date */
function fmt_date(?string $d, string $f = 'd M Y'): string
{
    if (!$d) return '—';
    $ts = strtotime($d);
    return $ts ? date($f, $ts) : '—';
}