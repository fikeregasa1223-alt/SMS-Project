<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM subjects WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Subject not found.'); redirect(url('admin/subjects/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'subject_deleted', 'subjects', $id, 'Subject deleted: ' . $row['name']);
    flash_set('success', 'Subject deleted successfully.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: subject is referenced by other records.');
}

redirect(url('admin/subjects/index.php'));