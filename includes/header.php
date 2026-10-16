<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../config/config.php';
}

$pageTitle       = $pageTitle       ?? 'Dashboard';
$pageDescription = $pageDescription ?? '';
$breadcrumbs     = $breadcrumbs     ?? [];
$bodyClass       = $bodyClass       ?? '';
$extraCss        = $extraCss        ?? [];      // ← KUN MUHIIMAA
$currentUser     = current_user();

$schoolName = setting('school_name', 'SMS Secondary School');
$schoolLogo = setting('school_logo', '');

$initials = '';
if ($currentUser) {
    $parts = preg_split('/\s+/', trim($currentUser['full_name'] ?? ''));
    foreach ($parts as $p) { $initials .= mb_strtoupper(mb_substr($p, 0, 1)); }
    $initials = mb_substr($initials, 0, 2);
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">

    <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
        <link href="<?= url($css) ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="sidebar-backdrop"></div>

    <div class="main">
        <?php include __DIR__ . '/topbar.php'; ?>

        <main class="page-content">
            <?= flash_render() ?>