<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('student');

$user = current_user();
$student = student_for_user((int)$user['id']);

if (!$student) {
    // User has student role but no linked student record
    $pageTitle = 'Student Dashboard';
    $breadcrumbs = [['label' => 'Dashboard']];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-exclamation-circle" style="font-size:3rem;color:var(--sms-warning);"></i>
        <h5 class="mt-3">Student record not linked</h5>
        <p class="text-muted">Your account is not yet linked to a student record. Contact the administrator.</p>
    </div></div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$enrollment  = student_current_enrollment((int)$student['id']);
$attendance  = student_attendance_summary((int)$student['id']);
$performance = student_performance_overview((int)$student['id']);
$results     = student_latest_results((int)$student['id'], 1);
$announcements = announcement_feed('student', (int)$user['id'],
    $enrollment ? (int)$enrollment['grade_id'] : null,
    $enrollment ? (int)$enrollment['section_id'] : null,
    5
);

$photoUrl = student_photo_url($student);
$fullName = student_full_name($student);
$initials = mb_strtoupper(mb_substr($student['first_name'] ?? 'S', 0, 1) . mb_substr($student['last_name'] ?? '', 0, 1));

$pageTitle       = 'Student Dashboard';
$pageDescription = 'Your academic overview.';
$breadcrumbs     = [['label' => 'Dashboard']];
$extraCss        = ['assets/css/dashboards.css', 'assets/css/announcements.css'];
include __DIR__ . '/../includes/header.php';
?>

<!-- Hero -->
<div class="hero-card">
    <div class="hero-avatar">
        <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="">
        <?php else: ?><?= e($initials) ?><?php endif; ?>
    </div>
    <div>
        <h1>Hi, <?= e($student['first_name']) ?> 👋</h1>
        <p class="sub">
            <?= e($student['student_code']) ?>
            <?php if ($enrollment): ?>
                · <?= e($enrollment['grade_name']) ?> — <?= e($enrollment['section_name']) ?>
                · <?= e($enrollment['year_name']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="<?= e(url('student/results.php')) ?>" class="btn btn-sm"><i class="bi bi-trophy me-1"></i> My Results</a>
        <a href="<?= e(url('student/attendance.php')) ?>" class="btn btn-sm"><i class="bi bi-check2-square me-1"></i> My Attendance</a>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-primary"><i class="bi bi-graph-up"></i></div>
            <div>
                <div class="stat-value"><?= $performance['avg_pct'] ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-success"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="stat-value"><?= $attendance['percentage'] ?>%</div>
                <div class="stat-label">Attendance Rate</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-info"><i class="bi bi-journal-check"></i></div>
            <div>
                <div class="stat-value"><?= $performance['marks_count'] ?></div>
                <div class="stat-label">Marks Recorded</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-warning"><i class="bi bi-award"></i></div>
            <div>
                <div class="stat-value"><?= $performance['best'] ?>%</div>
                <div class="stat-label">Best Score</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Attendance ring -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Attendance Snapshot</div>
            <div class="card-body d-flex align-items-center gap-3">
                <?php
                    $pctCls = $attendance['percentage'] < 60 ? 'danger' : ($attendance['percentage'] < 75 ? 'warn' : 'success');
                ?>
                <div class="ring <?= $pctCls ?>" style="--pct: <?= (int)$attendance['percentage'] ?>;">
                    <span class="val"><?= (int)$attendance['percentage'] ?>%</span>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Present</span><strong><?= (int)$attendance['present'] ?></strong></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Absent</span><strong><?= (int)$attendance['absent'] ?></strong></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Late</span><strong><?= (int)$attendance['late'] ?></strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted small">Excused</span><strong><?= (int)$attendance['excused'] ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest results -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-trophy me-2"></i>Latest Results</span>
                <a href="<?= e(url('student/results.php')) ?>" class="small">View all</a>
            </div>
            <div class="card-body p-0">
                <?php if (!$results): ?>
                    <?= ui_empty('No results yet', 'Your exam results will appear here.', 'bi-trophy') ?>
                <?php else: ?>
                    <?php foreach ($results as $examId => $block): ?>
                        <div class="px-3 pt-3 pb-2 border-bottom">
                            <strong><?= e($block['exam_name']) ?></strong>
                            <span class="text-muted small ms-2"><?= e(exam_type_label($block['exam_type'])) ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead><tr><th>Subject</th><th>Score</th><th>%</th><th>Grade</th></tr></thead>
                                <tbody>
                                <?php foreach ($block['marks'] as $m): ?>
                                    <tr>
                                        <td><?= e($m['subject_name']) ?></td>
                                        <td><?= e((string)$m['score']) ?> / <?= e((string)$m['max_mark']) ?></td>
                                        <td><?= $m['percentage'] !== null ? e((string)$m['percentage']) . '%' : '—' ?></td>
                                        <td><?= grade_badge($m['grade_letter']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Announcements -->
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-megaphone me-2"></i>Announcements</span>
        <a href="<?= e(url('student/announcements.php')) ?>" class="small">View all</a>
    </div>
    <div class="card-body">
        <?php if (!$announcements): ?>
            <?= ui_empty('No announcements', 'You have no new announcements.', 'bi-megaphone') ?>
        <?php else: ?>
            <?php foreach ($announcements as $a): ?>
                <?= render_announcement_banner($a) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>