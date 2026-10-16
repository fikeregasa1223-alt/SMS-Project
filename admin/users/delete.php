<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_users');

$id = (int) ($_GET['id'] ?? 0);
$u  = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
if (!$u) { flash_set('error', 'User not found.'); redirect(url('admin/users/index.php')); }

// Prevent deleting yourself
if ((int)$u['id'] === (int)current_user()['id']) {
    flash_set('error', 'You cannot delete your own account.');
    redirect(url('admin/users/index.php'));
}

try {
    Database::conn()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'user_deleted', 'users', $id, 'User deleted: ' . $u['username']);
    flash_set('success', 'User deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: user is referenced by other records.');
}

redirect(url('admin/users/index.php'));