<?php
declare(strict_types=1);

if (defined('SMS_HEADERS_LOADED')) { return; }
define('SMS_HEADERS_LOADED', true);

// Only send headers if we have a real web request (avoid CLI)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    if (function_exists('send_security_headers')) {
        send_security_headers();
    }
    if (function_exists('bind_session_to_fingerprint') && session_status() === PHP_SESSION_ACTIVE) {
        bind_session_to_fingerprint();
    }
}