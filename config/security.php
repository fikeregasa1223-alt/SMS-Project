<?php
declare(strict_types=1);

/**
 * SMS — Security utilities
 */

if (defined('SMS_SECURITY_LOADED')) { return; }
define('SMS_SECURITY_LOADED', true);

/**
 * Send hardening headers (call from every page load — idempotent).
 */
function send_security_headers(): void
{
    if (headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header_remove('X-Powered-By');

    // CSP — allow CDN for Bootstrap, Chart.js, Google Fonts, Bootstrap Icons
    $csp = "default-src 'self'; "
         . "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; "
         . "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; "
         . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; "
         . "img-src 'self' data: blob:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'self'; "
         . "base-uri 'self'; "
         . "form-action 'self'";

    // Only send CSP in production (dev often needs toggles)
    if (defined('SMS_ENV') && SMS_ENV === 'production') {
        header('Content-Security-Policy: ' . $csp);
    }
}

/**
 * Session fingerprint — ties session to a client fingerprint to detect hijacking.
 */
function bind_session_to_fingerprint(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) return;

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $fp = hash('sha256', $ua . '|' . $ip . '|' . (defined('APP_NAME') ? APP_NAME : 'SMS'));

    if (empty($_SESSION['_fp'])) {
        $_SESSION['_fp'] = $fp;
        return;
    }
    if (!hash_equals($_SESSION['_fp'], $fp)) {
        error_log('[SMS][security] Session fingerprint mismatch — destroying session');
        $_SESSION = [];
        session_destroy();
        if (!headers_sent()) {
            session_start();
            $_SESSION['flash_error'] = 'Session invalid. Please sign in again.';
        }
    }
}

/**
 * Get client IP (proxy-aware but safe).
 */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Is the request over HTTPS?
 */
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/**
 * Simple integrity check on uploaded file for potential HTML/PHP injection.
 */
function is_safe_image(string $tmpPath): bool
{
    if (!is_file($tmpPath)) return false;
    $head = @file_get_contents($tmpPath, false, null, 0, 512) ?: '';

    // Reject if file starts with `<?php` or `<script`
    if (preg_match('/<\?php|<script|<\?=/i', $head)) return false;

    // Confirm it's a real image
    return @getimagesize($tmpPath) !== false;
}