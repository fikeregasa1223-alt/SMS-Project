<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('student');

$user = current_user();
$student = student_for_user((int)$user['id']);
if (!$student) { flash_set('error', 'No student record linked.'); redirect(url('student/dashboard.php')); }

$summary = student_attendance_summary((int)$student['id']);

$rows = Database::fetchAll(
    "SELECT a.*, sub.name AS subject_name
     FROM attendance a
     LEFT JOIN subjects sub ON sub.id = a.subject_id
     WHERE a.student_id = ?
     ORDER BY a.date DESC
     LIMIT 100",
    [(int)$student['id']]
);

$pageTitle       = 'My Attendance';
$pageDescription = 'Your attendance history.';
$breadcrumbs     = [['label' => 'Attendance']];
$extraCss        = ['assets/css/dashboards.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Present',  (int)$summary['present'], 'bi-check-circle-fill', 'success') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Absent',   (int)$summary['absent'],  'bi-x-circle-fill',     'danger') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Late',     (int)$summary['late'],    'bi-clock-fill',        'warning') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Attendance %', (float)$summary['percentage'], 'bi-percent', 'primary') ?></div>
</div>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No attendance records', 'Your attendance will appear here.', 'bi-check2-square') ?>
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