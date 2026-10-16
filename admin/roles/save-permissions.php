<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_roles');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin/roles/index.php'));
csrf_check_or_die();

$roleId  = (int) post('role_id', 0);
$permIds = array_map('intval', (array) post('permission_ids', []));

$role = Database::fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
if (!$role) { flash_set('error', 'Role not found.'); redirect(url('admin/roles/index.php')); }

Database::begin();
try {
    Database::conn()->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);
    foreach ($permIds as $pid) {
        if ($pid > 0) {
            Database::insert('role_permissions', [
                'role_id'       => $roleId,
                'permission_id' => $pid,
            ]);
        }
    }
    audit((int)current_user()['id'], 'role_permissions_updated', 'roles', $roleId, 'Permissions updated for role: ' . $role['slug']);
    Database::commit();
    flash_set('success', 'Permissions saved.');
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Failed to save permissions. ' . (SMS_ENV === 'development' ? $e->getMessage() : ''));
}

redirect(url('admin/roles/edit.php?id=' . $roleId));