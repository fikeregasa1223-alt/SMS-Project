<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

sms_session_start();

if (is_logged_in()) {
    $role = $_SESSION['user']['primary_role'] ?? 'student';
    redirect(url(role_home($role)));
}
redirect(url('auth/login.php'));