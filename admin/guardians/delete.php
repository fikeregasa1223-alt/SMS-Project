<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('edit_students');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM guardians WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Guardian not found.'); redirect(url('admin/guardians/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM guardians WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'guardian_deleted', 'guardians', $id, 'Guardian deleted: ' . $row['full_name']);
    flash_set('success', 'Guardian deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: guardian is attached to a student.');
}

redirect(url('admin/guardians/index.php'));