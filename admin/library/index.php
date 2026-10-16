<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_attendance');

$stats = Database::fetch(
    "SELECT
        (SELECT COUNT(*) FROM library_books WHERE status='active') AS books,
        (SELECT COUNT(*) FROM library_books WHERE status='active') AS titles,
        (SELECT COALESCE(SUM(total_copies),0) FROM library_books) AS total_copies,
        (SELECT COALESCE(SUM(available_copies),0) FROM library_books) AS available,
        (SELECT COUNT(*) FROM library_issues WHERE status='issued') AS issued,
        (SELECT COUNT(*) FROM library_issues WHERE status='overdue' OR (status='issued' AND due_date < CURDATE())) AS overdue
    "
);
$pageTitle = 'Library';
$breadcrumbs = [['label' => 'Library']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/library/issue-create.php')) ?>" class="btn btn-primary"><i class="bi bi-book me-1"></i> Issue Book</a>
        <a href="<?= e(url('admin/library/book-create.php')) ?>" class="btn btn-outline-primary"><i class="bi bi-plus me-1"></i> Add Book</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Titles', (int)$stats['titles'], 'bi-book', 'primary') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Total Copies', (int)$stats['total_copies'], 'bi-bookshelf', 'info') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Available', (int)$stats['available'], 'bi-check-circle', 'success') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Currently Issued', (int)$stats['issued'], 'bi-arrow-right-circle', 'warning') ?></div>
</div>

<div class="row g-3">
    <div class="col-md-6"><div class="card"><div class="card-body">
        <h5>Quick Links</h5>
        <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="<?= e(url('admin/library/books.php')) ?>"><i class="bi bi-book me-1"></i> All Books</a>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/library/issues.php')) ?>"><i class="bi bi-arrow-right-circle me-1"></i> All Issues</a>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/library/issues.php?status=overdue')) ?>"><i class="bi bi-exclamation-triangle me-1"></i> Overdue Only</a>
        </div>
    </div></div></div>

    <div class="col-md-6"><div class="card"><div class="card-body">
        <h5>Overdue Alert</h5>
        <p class="mb-0"><strong class="text-danger"><?= (int)$stats['overdue'] ?></strong> items currently overdue.</p>
    </div></div></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>