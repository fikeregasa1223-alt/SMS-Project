<?php
declare(strict_types=1);

/**
 * SMS - Global Configuration
 * Path: C:\xampp\htdocs\SMS\config\config.php
 */

// ------------------------------------------------------------
// Environment
// ------------------------------------------------------------
define('SMS_ENV', 'development'); // 'development' | 'production'

// ------------------------------------------------------------
// Application
// ------------------------------------------------------------
define('APP_NAME',     'SMS');
define('APP_FULLNAME', 'School Management System');
define('BASE_URL',     'http://localhost/SMS');
define('APP_VERSION',  '1.0.0');
define('APP_ROOT',     dirname(__DIR__));

// ------------------------------------------------------------
// Upload paths
// ------------------------------------------------------------
define('UPLOAD_DIR',      APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_PROFILES', UPLOAD_DIR . DIRECTORY_SEPARATOR . 'profiles');
define('UPLOAD_SCHOOL',   UPLOAD_DIR . DIRECTORY_SEPARATOR . 'school');
define('UPLOAD_URL',      BASE_URL . '/uploads');

define('ALLOWED_IMAGE_MIME', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB

// ------------------------------------------------------------
// Database (PDO / MySQL)
// ------------------------------------------------------------
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '3306');
define('DB_NAME',    'sms-db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ------------------------------------------------------------
// Session & Security
// ------------------------------------------------------------
define('SESSION_NAME',       'SMS_SESSION');
define('SESSION_LIFETIME',   1800);   // 30 minutes idle timeout
define('SESSION_REGENERATE', 900);    // regenerate every 15 minutes
define('CSRF_TOKEN_NAME',    '_csrf');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCK_MINUTES', 15);

// ------------------------------------------------------------
// Timezone & Errors
// ------------------------------------------------------------
date_default_timezone_set('Africa/Addis_Ababa');

if (SMS_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/error.log');
}

// ------------------------------------------------------------
// Load core includes (ORDER MATTERS)
// ------------------------------------------------------------
require_once APP_ROOT . '/config/database.php';           // Database class
require_once APP_ROOT . '/includes/functions.php';        // Helpers (e, url, redirect...)
require_once APP_ROOT . '/includes/csrf.php';             // CSRF helpers
require_once APP_ROOT . '/includes/auth.php';             // Session + login
require_once APP_ROOT . '/includes/authorization.php';    // Roles + permissions
require_once APP_ROOT . '/includes/flash.php';            // Flash messages
require_once APP_ROOT . '/includes/validation.php';       // Input validators
require_once APP_ROOT . '/includes/pagination.php';       // Pagination
require_once APP_ROOT . '/includes/table-helpers.php';    // Sort + filter helpers
require_once APP_ROOT . '/includes/ui.php';               // Reusable UI components
require_once APP_ROOT . '/includes/upload.php';
require_once APP_ROOT . '/includes/student-helpers.php';
require_once APP_ROOT . '/includes/teacher-helpers.php';
require_once APP_ROOT . '/includes/attendance-helpers.php';
require_once APP_ROOT . '/includes/examination-helpers.php';
require_once APP_ROOT . '/includes/grading-helpers.php';
require_once APP_ROOT . '/includes/announcement-helpers.php';
require_once APP_ROOT . '/includes/dashboard-helpers.php';

// Load production overrides (only when SMS_ENV === 'production')
if (SMS_ENV === 'production' && is_file(APP_ROOT . '/config/production.php')) {
    require_once APP_ROOT . '/config/production.php';
}

// Security utilities (must load before session start)
require_once APP_ROOT . '/config/security.php';
require_once APP_ROOT . '/includes/rate-limit.php';
require_once APP_ROOT . '/includes/maintenance.php';
// Send security headers after everything is loaded
require_once APP_ROOT . '/includes/headers.php';
require_once APP_ROOT . '/includes/i18n.php';
require_once APP_ROOT . '/includes/pdf.php';
require_once APP_ROOT . '/includes/mailer.php';
require_once APP_ROOT . '/includes/importer.php';
require_once APP_ROOT . '/includes/timetable-helpers.php';
require_once APP_ROOT . '/includes/library-helpers.php';
require_once APP_ROOT . '/includes/transport-helpers.php';
// ------------------------------------------------------------
// SMTP (Email)
// ------------------------------------------------------------
define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'your-email@gmail.com');
define('SMTP_PASS',   'your-app-password-16-chars');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM',   'your-email@gmail.com');
define('SMTP_FROM_NAME', 'SMS School Management');