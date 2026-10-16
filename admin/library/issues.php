<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_attendance');

$status = (string)($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = "WHERE 1=1";
$params = [];
if ($status !== '') { $where .= " AND li.status = :st"; $params['st'] = $status; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM library_issues li $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare(
    "SELECT li.*, b.title AS book_title, b.isbn,
            s.first_name, s.middle_name, s.last_name, s.student_code,
            t.first_name AS t_first, t.last_name AS t_last
     FROM library_issues li
     JOIN library_books b ON b.id = li.book_id
     LEFT JOIN students s ON s.id = li.student_id
     LEFT JOIN teachers t ON t.id = li.teacher_id
     $where
     ORDER BY li.id DESC
     LIMIT {$pg['limit']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Library Issues';
$breadcrumbs = [['label' => 'Library', 'url' => 'admin/library/index.php'], ['label' => 'Issues']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/library/issue-create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Issue Book</a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-3">
        <select name="status" class="form-select" data-auto-submit>
            <option value="">All statuses</option>
            <?php foreach (['issued','returned','overdue','lost'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No issues', 'Issue a book to begin.', 'bi-book', url('admin/library/issue-create.php'), 'Issue Book') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Book</th><th>Issued To</th><th>Issued</th><th>Due</th><th>Returned</th><th>Fine</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $borrower = $r['student_code']
                        ? trim($r['first_name'] . ' ' . $r['last_name']) . ' (' . $r['student_code'] . ')'
                        : ($r['t_first'] ? trim($r['t_first'] . ' ' . $r['t_last']) . ' (teacher)' : '—');
                    $fine = library_fine($r);
                ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e($r['book_title']) ?></div><div class="text-muted small"><?= e((string)$r['isbn']) ?></div></td>
                        <td><?= e($borrower) ?></td>
                        <td><?= e(fmt_date($r['issue_date'])) ?></td>
                        <td><?= e(fmt_date($r['due_date'])) ?></td>
                        <td><?= $r['return_date'] ? e(fmt_date($r['return_date'])) : '—' ?></td>
                        <td><?= $fine > 0 ? '<strong class="text-danger">' . e(number_format($fine, 2)) . '</strong>' : '—' ?></td>
                        <td><?= library_issue_status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?php if ($r['status'] !== 'returned'): ?>
                                <a class="btn btn-sm btn-outline-success" data-confirm="Confirm return?"
                                   href="<?= e(url('admin/library/issue-return.php?id=' . (int)$r['id'] . '&csrf=' . csrf_token())) ?>">
                                    <i class="bi bi-arrow-return-left"></i> Return
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['status' => $status]) ?>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>