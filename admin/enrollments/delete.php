<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('edit_students');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM enrollments WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Enrollment not found.'); redirect(url('admin/enrollments/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM enrollments WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'enrollment_deleted', 'enrollments', $id, 'Enrollment deleted');
    flash_set('success', 'Enrollment deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: this enrollment is referenced by attendance or marks.');
}

redirect(url('admin/enrollments/index.php'));