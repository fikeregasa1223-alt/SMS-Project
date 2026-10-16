<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_users');

$id   = (int) ($_GET['id'] ?? 0);
$csrf = (string) ($_GET['csrf'] ?? '');
if (!hash_equals(csrf_token(), $csrf)) exit('Invalid CSRF.');

$u = Database::fetch("SELECT id, status FROM users WHERE id = ?", [$id]);
if (!$u) { flash_set('error', 'Not found.'); redirect(url('admin/users/index.php')); }

if ((int)$u['id'] === (int)current_user()['id']) {
    flash_set('error', 'You cannot change your own status.');
    redirect(url('admin/users/index.php'));
}

$new = $u['status'] === 'active' ? 'suspended' : 'active';
Database::update('users', ['status' => $new], 'id = :id', ['id' => $id]);
audit((int)current_user()['id'], 'user_status_changed', 'users', $id, "User status changed to $new");
flash_set('success', 'User status updated.');
redirect(url('admin/users/index.php'));