<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_reports');

$pageTitle       = 'Reports';
$pageDescription = 'Generate and print academic, attendance, and student reports.';
$breadcrumbs     = [['label' => 'Reports']];
$extraCss        = ['assets/css/announcements.css', 'assets/css/dashboard.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<div class="row g-3">
    <div class="col-md-6 col-xl-4">
        <a href="<?= e(url('admin/reports/academic.php')) ?>" class="card text-decoration-none h-100">
            <div class="card-body">
                <div class="stat-icon icon-primary mb-3"><i class="bi bi-graph-up"></i></div>
                <h5 class="mb-1">Academic Reports</h5>
                <p class="text-muted small mb-0">
                    Performance by grade, section, subject. Top students. Pass/fail distribution.
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= e(url('admin/reports/attendance.php')) ?>" class="card text-decoration-none h-100">
            <div class="card-body">
                <div class="stat-icon icon-success mb-3"><i class="bi bi-check2-square"></i></div>
                <h5 class="mb-1">Attendance Reports</h5>
                <p class="text-muted small mb-0">
                    Attendance percentages per student, section, and grade with color-coded thresholds.
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= e(url('admin/reports/students.php')) ?>" class="card text-decoration-none h-100">
            <div class="card-body">
                <div class="stat-icon icon-info mb-3"><i class="bi bi-people"></i></div>
                <h5 class="mb-1">Student Reports</h5>
                <p class="text-muted small mb-0">
                    Full directory listing by grade, section, status. Enrollment statistics.
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= e(url('admin/reports/class-summary.php')) ?>" class="card text-decoration-none h-100">
            <div class="card-body">
                <div class="stat-icon icon-warning mb-3"><i class="bi bi-diagram-3"></i></div>
                <h5 class="mb-1">Class Summary</h5>
                <p class="text-muted small mb-0">
                    Per-section overview: student count, average attendance, average score.
                </p>
            </div>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>