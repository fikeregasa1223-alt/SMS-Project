<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_attendance');

$id   = (int) ($_GET['id'] ?? 0);
$csrf = (string) ($_GET['csrf'] ?? '');
if (!hash_equals(csrf_token(), $csrf)) exit('Invalid CSRF.');

$row = Database::fetch("SELECT * FROM timetable_entries WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Entry not found.'); redirect(url('admin/timetable/index.php')); }

Database::conn()->prepare("DELETE FROM timetable_entries WHERE id = ?")->execute([$id]);
audit((int)current_user()['id'], 'timetable_deleted', 'timetable_entries', $id, 'Timetable entry deleted');
flash_set('success', 'Entry deleted.');
redirect(url('admin/timetable/index.php'));