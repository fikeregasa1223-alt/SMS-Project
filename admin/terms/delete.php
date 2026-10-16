<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM terms WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Term not found.'); redirect(url('admin/terms/index.php')); }

try {
    Database::conn()->prepare("DELETE FROM terms WHERE id = ?")->execute([$id]);
    audit((int)current_user()['id'], 'term_deleted', 'terms', $id, 'Term deleted: ' . $row['name']);
    flash_set('success', 'Term deleted successfully.');
} catch (Throwable $e) {
    flash_set('error', 'Cannot delete: term is referenced by other records.');
}

redirect(url('admin/terms/index.php'));