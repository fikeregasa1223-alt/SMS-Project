<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_roles');

$id  = (int) ($_GET['id'] ?? 0);
$role = Database::fetch("SELECT * FROM roles WHERE id = ?", [$id]);
if (!$role) { flash_set('error', 'Role not found.'); redirect(url('admin/roles/index.php')); }
if ((int)$role['is_system']) { flash_set('error', 'Cannot delete a system role.'); redirect(url('admin/roles/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'role_deleted', 'roles', $id, 'Role deleted: ' . $role['slug']);
    flash_set('success', 'Role deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: role is assigned to users.');
}

redirect(url('admin/roles/index.php'));