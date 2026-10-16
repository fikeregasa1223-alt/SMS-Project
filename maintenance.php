<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

http_response_code(503);
header('Retry-After: 3600');

$schoolName = setting('school_name', 'SMS Secondary School');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scheduled Maintenance — <?= e($schoolName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0; min-height: 100vh;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: grid; place-items: center;
            font-family: 'Inter', system-ui, sans-serif;
            color: #fff; padding: 24px;
        }
        .m-card {
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 520px;
            text-align: center;
        }
        .m-icon {
            width: 96px; height: 96px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            display: grid; place-items: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
        }
        h1 { font-size: 1.75rem; margin: 0 0 .5rem; font-weight: 800; }
        p  { color: rgba(255,255,255,.85); margin: 0; }
        .footnote { margin-top: 2rem; color: rgba(255,255,255,.6); font-size: .8rem; }
    </style>
</head>
<body>
    <div class="m-card">
        <div class="m-icon"><i class="bi bi-tools"></i></div>
        <h1>We'll be right back</h1>
        <p><?= e($schoolName) ?> is currently undergoing scheduled maintenance.</p>
        <p style="margin-top:1rem;">Thank you for your patience — please try again in a little while.</p>
        <div class="footnote"><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?> · <?= date('d M Y H:i') ?></div>
    </div>
</body>
</html>