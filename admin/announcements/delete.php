<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_announcements');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM announcements WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Announcement not found.'); redirect(url('admin/announcements/index.php')); }

Database::conn()->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
audit((int)current_user()['id'], 'announcement_deleted', 'announcements', $id, 'Announcement deleted: ' . $row['title']);

flash_set('success', 'Announcement deleted.');
redirect(url('admin/announcements/index.php'));