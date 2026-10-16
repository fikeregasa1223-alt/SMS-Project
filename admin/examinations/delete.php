<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('delete_marks');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM examinations WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Examination not found.'); redirect(url('admin/examinations/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM examinations WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'examination_deleted', 'examinations', $id, 'Examination deleted: ' . $row['name']);
    flash_set('success', 'Examination deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: referenced by other records.');
}

redirect(url('admin/examinations/index.php'));