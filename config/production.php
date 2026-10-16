<?php
declare(strict_types=1);

/**
 * SMS — Production overrides
 * Loaded automatically by config.php when SMS_ENV === 'production'.
 */

// Errors: log everything, display nothing
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/error.log');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);

// Force HTTPS (comment out if SSL is not provisioned)
if (!is_https()) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

// Harden session cookie
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

// Disable PHP version disclosure
header_remove('X-Powered-By');