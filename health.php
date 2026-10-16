<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'app'        => APP_NAME,
    'version'    => APP_VERSION,
    'env'        => SMS_ENV,
    'timestamp'  => date('c'),
    'checks'     => [],
];

$overall = true;

// --- Database ---
try {
    $db = Database::conn();
    $db->query('SELECT 1');
    $response['checks']['database'] = ['status' => 'ok'];
} catch (Throwable $e) {
    $response['checks']['database'] = ['status' => 'fail', 'error' => 'connection_error'];
    $overall = false;
}

// --- Disk space ---
$free = @disk_free_space(APP_ROOT);
$total = @disk_total_space(APP_ROOT);
if ($free !== false && $total !== false && $total > 0) {
    $pct = round(($free / $total) * 100, 1);
    $response['checks']['disk'] = [
        'status'   => $pct > 5 ? 'ok' : 'warn',
        'free_pct' => $pct,
    ];
    if ($pct <= 5) $overall = false;
}

// --- Writable uploads ---
$uploadsOk = is_writable(UPLOAD_DIR);
$response['checks']['uploads_writable'] = ['status' => $uploadsOk ? 'ok' : 'fail'];
if (!$uploadsOk) $overall = false;

// --- Logs writable ---
$logsDir = APP_ROOT . '/logs';
if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
$response['checks']['logs_writable'] = ['status' => is_writable($logsDir) ? 'ok' : 'fail'];

// --- PHP version ---
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$response['checks']['php'] = ['status' => $phpOk ? 'ok' : 'fail', 'version' => PHP_VERSION];
if (!$phpOk) $overall = false;

$response['status'] = $overall ? 'healthy' : 'unhealthy';
http_response_code($overall ? 200 : 503);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);