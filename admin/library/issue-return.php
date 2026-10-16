<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$id   = (int) ($_GET['id'] ?? 0);
$csrf = (string) ($_GET['csrf'] ?? '');
if (!hash_equals(csrf_token(), $csrf)) exit('Invalid CSRF.');

$issue = Database::fetch("SELECT * FROM library_issues WHERE id = ?", [$id]);
if (!$issue) { flash_set('error', 'Issue not found.'); redirect(url('admin/library/issues.php')); }
if ($issue['status'] === 'returned') { flash_set('warning', 'Already returned.'); redirect(url('admin/library/issues.php')); }

$fine = library_fine($issue);

Database::begin();
try {
    Database::update('library_issues', [
        'return_date' => date('Y-m-d'),
        'fine_amount' => $fine,
        'status'      => 'returned',
    ], 'id = :id', ['id' => $id]);
    library_mark_returned((int)$issue['book_id']);
    audit((int)current_user()['id'], 'book_returned', 'library_issues', $id, 'Book returned; fine: ' . number_format($fine, 2));
    Database::commit();
    flash_set('success', 'Book returned. Fine: ' . number_format($fine, 2));
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Failed to return book.');
}
redirect(url('admin/library/issues.php'));