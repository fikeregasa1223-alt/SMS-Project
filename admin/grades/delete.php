<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM grades WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Grade not found.'); redirect(url('admin/grades/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM grades WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'grade_deleted', 'grades', $id, 'Grade deleted: ' . $row['name']);
    flash_set('success', 'Grade deleted successfully.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: grade is referenced by other records.');
}

redirect(url('admin/grades/index.php'));