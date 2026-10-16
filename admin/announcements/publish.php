<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_announcements');

$id  = (int) ($_GET['id'] ?? 0);
$csrf = (string) ($_GET['csrf'] ?? '');
if (!hash_equals(csrf_token(), $csrf)) exit('Invalid CSRF.');

$row = Database::fetch("SELECT * FROM announcements WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Not found.'); redirect(url('admin/announcements/index.php')); }

Database::update('announcements', [
    'status'       => 'published',
    'publish_date' => date('Y-m-d H:i:s'),
], 'id = :id', ['id' => $id]);

audit((int)current_user()['id'], 'announcement_published', 'announcements', $id, 'Announcement published: ' . $row['title']);
flash_set('success', 'Announcement published.');
redirect(url('admin/announcements/index.php'));