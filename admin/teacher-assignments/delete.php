<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('edit_teachers');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM teacher_assignments WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Assignment not found.'); redirect(url('admin/teacher-assignments/index.php')); }

Database::conn()->prepare("DELETE FROM teacher_assignments WHERE id = ?")->execute([$id]);
audit((int)current_user()['id'], 'teacher_assignment_deleted', 'teacher_assignments', $id, 'Assignment deleted');

flash_set('success', 'Assignment deleted.');
redirect(url('admin/teacher-assignments/index.php'));