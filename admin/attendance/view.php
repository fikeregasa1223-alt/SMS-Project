<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$id = (int) ($_GET['id'] ?? 0);
$r  = Database::fetch(
    "SELECT a.*, s.student_code, s.admission_no, s.first_name, s.middle_name, s.last_name, s.photo,
            g.name AS grade_name, sec.name AS section_name,
            sub.name AS subject_name, ay.name AS year_name, t.name AS term_name,
            u.full_name AS recorded_by_name
     FROM attendance a
     JOIN students s   ON s.id = a.student_id
     JOIN sections sec ON sec.id = a.section_id
     JOIN grades   g   ON g.id = sec.grade_id
     JOIN academic_years ay ON ay.id = a.academic_year_id
     JOIN terms    t   ON t.id = a.term_id
     LEFT JOIN subjects sub ON sub.id = a.subject_id
     LEFT JOIN users u ON u.id = a.recorded_by
     WHERE a.id = ?",
    [$id]
);
if (!$r) { flash_set('error', 'Attendance record not found.'); redirect(url('admin/attendance/history.php')); }

$name     = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
$photoUrl = !empty($r['photo']) ? url('uploads/profiles/' . $r['photo']) : null;

$pageTitle       = 'Attendance Record';
$pageDescription = 'Details of a single attendance entry.';
$breadcrumbs     = [
    ['label' => 'Attendance History', 'url' => 'admin/attendance/history.php'],
    ['label' => $name . ' — ' . fmt_date($r['date'])],
];
$extraCss = ['assets/css/attendance.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/students/show.php?id=' . (int)$r['student_id'])) ?>" class="btn btn-outline-primary">
            <i class="bi bi-person me-1"></i> View Student
        </a>
        <a href="<?= e(url('admin/attendance/history.php')) ?>" class="btn btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar avatar-md">
                        <?php if ($photoUrl): ?>
                            <img src="<?= e($photoUrl) ?>" alt="">
                        <?php else: ?>
                            <?= e(mb_strtoupper(mb_substr($r['first_name'], 0, 1) . mb_substr($r['last_name'], 0, 1))) ?>
                        <?php endif; ?>
                    </span>
                    <div>
                        <h5 class="mb-0"><?= e($name) ?></h5>
                        <div class="text-muted small"><?= e($r['student_code']) ?> · <?= e($r['admission_no']) ?></div>
                    </div>
                </div>

                <div class="info-row"><span class="label">Date</span><span class="value"><?= e(fmt_date($r['date'], 'D, d M Y')) ?></span></div>
                <div class="info-row"><span class="label">Status</span><span class="value"><?= attendance_status_badge($r['status']) ?></span></div>
                <div class="info-row"><span class="label">Academic Year</span><span class="value"><?= e($r['year_name']) ?></span></div>
                <div class="info-row"><span class="label">Term</span><span class="value"><?= e($r['term_name']) ?></span></div>
                <div class="info-row"><span class="label">Grade</span><span class="value"><?= e($r['grade_name']) ?></span></div>
                <div class="info-row"><span class="label">Section</span><span class="value"><?= e($r['section_name']) ?></span></div>
                <div class="info-row"><span class="label">Subject</span><span class="value"><?= e($r['subject_name'] ?: 'Homeroom') ?></span></div>
                <div class="info-row"><span class="label">Recorded By</span><span class="value"><?= e($r['recorded_by_name'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Recorded At</span><span class="value"><?= e(fmt_date($r['created_at'], 'd M Y H:i')) ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>