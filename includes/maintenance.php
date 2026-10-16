<?php
declare(strict_types=1);

if (defined('SMS_MAINTENANCE_LOADED')) { return; }
define('SMS_MAINTENANCE_LOADED', true);

/**
 * Is maintenance mode currently enabled?
 */
function maintenance_mode_active(): bool
{
    return is_file(APP_ROOT . '/.maintenance');
}

/**
 * If maintenance is active and current user is not admin, redirect to maintenance page.
 */
function enforce_maintenance_mode(): void
{
    if (!maintenance_mode_active()) return;

    // Admins bypass
    $user = $_SESSION['user'] ?? null;
    if ($user && in_array('admin', $user['roles'] ?? [], true)) return;

    // Already on the maintenance or health page — do nothing
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, 'maintenance.php') || str_contains($uri, 'health.php')) return;

    // Whitelist static assets
    if (preg_match('#\.(css|js|png|jpe?g|webp|svg|gif|ico|woff2?|ttf)$#', $uri)) return;

    header('HTTP/1.1 503 Service Unavailable');
    header('Retry-After: 3600');
    include APP_ROOT . '/maintenance.php';
    exit;
}