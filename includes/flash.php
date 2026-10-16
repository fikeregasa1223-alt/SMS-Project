<?php
declare(strict_types=1);

function flash_set(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash_' . $type] = $message;
}

function flash_get(string $type): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}

function flash_render(): string
{
    $types = [
        'success' => ['class' => 'alert-success', 'icon' => 'bi-check-circle-fill'],
        'error'   => ['class' => 'alert-danger',  'icon' => 'bi-x-octagon-fill'],
        'warning' => ['class' => 'alert-warning', 'icon' => 'bi-exclamation-triangle-fill'],
        'info'    => ['class' => 'alert-info',    'icon' => 'bi-info-circle-fill'],
    ];
    $html = '';
    foreach ($types as $type => $meta) {
        $msg = flash_get($type);
        if ($msg) {
            $html .= '<div class="alert ' . $meta['class'] . ' d-flex align-items-center gap-2 shadow-sm rounded-3" role="alert">'
                   . '<i class="bi ' . $meta['icon'] . '"></i>'
                   . '<span>' . e($msg) . '</span>'
                   . '</div>';
        }
    }
    return $html;
}