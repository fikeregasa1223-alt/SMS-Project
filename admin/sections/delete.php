<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM sections WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Section not found.'); redirect(url('admin/sections/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM sections WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'section_deleted', 'sections', $id, 'Section deleted: ' . $row['name']);
    flash_set('success', 'Section deleted successfully.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: section is referenced by other records.');
}

redirect(url('admin/sections/index.php'));