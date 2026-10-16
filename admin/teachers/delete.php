<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('delete_teachers');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM teachers WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Teacher not found.'); redirect(url('admin/teachers/index.php')); }

try {
    Database::begin();

    if (!empty($row['photo'])) {
        delete_upload($row['photo'], 'profiles');
    }

    Database::conn()->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);

    audit((int)current_user()['id'], 'teacher_deleted', 'teachers', $id, 'Teacher deleted: ' . $row['teacher_code']);
    Database::commit();
    flash_set('success', 'Teacher deleted successfully.');
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Cannot delete teacher. ' . (SMS_ENV === 'development' ? $e->getMessage() : 'Referenced by assignments.'));
}

redirect(url('admin/teachers/index.php'));