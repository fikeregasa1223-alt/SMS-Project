<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('delete_students');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM students WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Student not found.'); redirect(url('admin/students/index.php')); }

try {
    Database::begin();

    // Delete photo
    if (!empty($row['photo'])) {
        delete_upload($row['photo'], 'profiles');
    }

    Database::conn()->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);

    audit((int)current_user()['id'], 'student_deleted', 'students', $id, 'Student deleted: ' . $row['student_code']);
    Database::commit();

    flash_set('success', 'Student deleted successfully.');
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Cannot delete student. ' . (SMS_ENV === 'development' ? $e->getMessage() : 'Referenced by other records.'));
}

redirect(url('admin/students/index.php'));