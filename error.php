<?php
declare(strict_types=1);

// Do not start session here — keep it lightweight and side-effect-free.

require_once __DIR__ . '/config/config.php';

$code = (int) ($_GET['code'] ?? 404);
if (!in_array($code, [400,403,404,419,429,500,503], true)) $code = 404;

$title = match ($code) {
    400 => 'Bad Request',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    419 => 'Session Expired',
    429 => 'Too Many Requests',
    500 => 'Server Error',
    503 => 'Service Unavailable',
    default => 'Something Went Wrong',
};

$message = match ($code) {
    400 => 'The request could not be understood by the server.',
    403 => 'You do not have permission to access this resource.',
    404 => 'We could not find the page you are looking for.',
    419 => 'Your session has expired. Please sign in again.',
    429 => 'Too many requests. Please wait a moment and try again.',
    500 => 'Something went wrong on our end. Please try again or contact the administrator.',
    503 => 'The system is temporarily unavailable. Please try again soon.',
    default => 'An unexpected error occurred.',
};

$icon = match ($code) {
    403 => 'bi-shield-lock-fill',
    404 => 'bi-signpost-2-fill',
    419 => 'bi-clock-history',
    429 => 'bi-hourglass-split',
    500 => 'bi-exclamation-octagon-fill',
    503 => 'bi-tools',
    default => 'bi-exclamation-triangle-fill',
};

http_response_code($code);

$schoolName = function_exists('setting') ? (setting('school_name', 'SMS Secondary School') ?: 'SMS') : 'SMS';
$backUrl    = function_exists('is_logged_in') && is_logged_in() ? url(role_home($_SESSION['user']['primary_role'] ?? 'student')) : url('auth/login.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($code . ' — ' . $title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            min-height: 100vh;
            display: grid;
            place-items: center;
            color: #fff;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .err-card {
            background: #fff;
            color: #0f172a;
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 520px;
            width: 90%;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0,0,0,.25);
        }
        .err-icon {
            width: 84px; height: 84px;
            border-radius: 50%;
            margin: 0 auto 1.25rem;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            color: #4f46e5;
            font-size: 2.25rem;
        }
        .err-code {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .err-title { font-size: 1.25rem; font-weight: 700; margin-top: .5rem; }
        .err-msg   { color: #64748b; margin-top: .35rem; font-size: .9375rem; }
        .brand     { color: #94a3b8; font-size: .75rem; margin-top: 1.5rem; text-transform: uppercase; letter-spacing: .1em; }
    </style>
</head>
<body>
    <div class="err-card">
        <div class="err-icon"><i class="bi <?= e($icon) ?>"></i></div>
        <div class="err-code"><?= (int)$code ?></div>
        <div class="err-title"><?= e($title) ?></div>
        <div class="err-msg"><?= e($message) ?></div>

        <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
            <a href="<?= e($backUrl) ?>" class="btn btn-primary">
                <i class="bi bi-house me-1"></i> Go to Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="brand"><?= e($schoolName) ?> · <?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></div>
    </div>
</body>
</html>