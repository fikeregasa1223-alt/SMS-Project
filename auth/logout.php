<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();

$user = current_user();
if ($user) {
    audit((int)$user['id'], 'logout', 'users', (int)$user['id'], 'User signed out');
}

logout_user();

// Start a fresh session for the flash message
session_start();
flash_set('success', 'You have been signed out successfully.');

redirect(url('auth/login.php'));