<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('parent');

$user = current_user();
$parent = parent_for_user((int)$user['id']);
if (!$parent) { flash_set('error', 'No parent record linked.'); redirect(url('parent/dashboard.php')); }

$children = parent_children((int)$parent['id']);
$childIds = array_map(fn($c) => (int)$c['id'], $children);

$studentId = (int) ($_GET['student_id'] ?? ($childIds[0] ?? 0));
if (!in_array($studentId, $childIds, true)) {
    flash_set('error', 'That child is not linked to your account.');
    redirect(url('parent/dashboard.php'));
}

$child   = Database::fetch("SELECT * FROM students WHERE id = ?", [$studentId]);
$summary = student_attendance_summary($studentId);

$rows = Database::fetchAll(
    "SELECT a.*, sub.name AS subject_name FROM attendance a
     LEFT JOIN subjects sub ON sub.id = a.subject_id
     WHERE a.student_id = ? ORDER BY a.date DESC LIMIT 100",
    [$studentId]
);

$pageTitle       = 'Attendance — ' . student_full_name($child);
$pageDescription = 'Attendance records for your child.';
$breadcrumbs     = [['label' => 'Attendance']];
$extraCss        = ['assets/css/dashboards.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <?php foreach ($children as $c): ?>
            <a href="?student_id=<?= (int)$c['id'] ?>" class="btn btn-sm <?= (int)$c['id'] === $studentId ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= e($c['first_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Present',  (int)$summary['present'], 'bi-check-circle-fill', 'success') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Absent',   (int)$summary['absent'],  'bi-x-circle-fill',     'danger') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Late',     (int)$summary['late'],    'bi-clock-fill',        'warning') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Rate',     (float)$summary['percentage'], 'bi-percent',       'primary') ?></div>
</div>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No records', 'No attendance recorded yet.', 'bi-check2-square') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Subject</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e(fmt_date($r['date'], 'D, d M Y')) ?></td>
                        <td><?= e($r['subject_name'] ?: 'Homeroom') ?></td>
                        <td><?= attendance_status_badge($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>