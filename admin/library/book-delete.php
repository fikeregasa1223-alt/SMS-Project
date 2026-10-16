<?php
// book-delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$id = (int)($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM library_books WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Book not found.'); redirect(url('admin/library/books.php')); }
Database::conn()->prepare("DELETE FROM library_books WHERE id = ?")->execute([$id]);
audit((int)current_user()['id'], 'book_deleted', 'library_books', $id, 'Book deleted: ' . $row['title']);
flash_set('success', 'Book deleted.');
redirect(url('admin/library/books.php'));