<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM academic_years WHERE id = ?", [$id]);
if (!$row) {
    flash_set('error', 'Academic year not found.');
    redirect(url('admin/academic-years/index.php'));
}

try {
    Database::conn()->prepare("DELETE FROM academic_years WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'academic_year_deleted', 'academic_years', $id, 'Academic year deleted: ' . $row['name']);
    flash_set('success', 'Academic year deleted successfully.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: this academic year is referenced by other records.');
}

redirect(url('admin/academic-years/index.php'));