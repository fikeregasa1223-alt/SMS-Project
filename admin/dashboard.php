<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('admin');

// ============================================================
// Statistics
// ============================================================
$totalStudents  = (int) Database::conn()->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalTeachers  = (int) Database::conn()->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalClasses   = (int) Database::conn()->query("SELECT COUNT(*) FROM sections WHERE status='active'")->fetchColumn();
$totalSubjects  = (int) Database::conn()->query("SELECT COUNT(*) FROM subjects WHERE status='active'")->fetchColumn();
$totalGrades    = (int) Database::conn()->query("SELECT COUNT(*) FROM grades WHERE status='active'")->fetchColumn();

// Today's attendance
$today = date('Y-m-d');
$attRow = Database::fetch(
    "SELECT
        SUM(status='present') AS present,
        SUM(status='absent')  AS absent,
        SUM(status='late')    AS late,
        SUM(status='excused') AS excused,
        COUNT(*)              AS total
     FROM attendance
     WHERE date = ?",
    [$today]
);
$presentToday = (int)($attRow['present'] ?? 0);
$absentToday  = (int)($attRow['absent']  ?? 0);
$lateToday    = (int)($attRow['late']    ?? 0);
$excusedToday = (int)($attRow['excused'] ?? 0);
$totalToday   = (int)($attRow['total']   ?? 0);
$attendancePct = $totalToday > 0
    ? round((($presentToday + $lateToday + $excusedToday) / $totalToday) * 100, 1)
    : 0;

// Academic
$pendingMarks    = (int) Database::conn()->query("SELECT COUNT(*) FROM examinations WHERE status IN ('open','completed')")->fetchColumn();
$publishedExams  = (int) Database::conn()->query("SELECT COUNT(*) FROM examinations WHERE status='published'")->fetchColumn();
$avgPerformance  = (float) (Database::conn()->query("SELECT AVG(percentage) FROM marks WHERE percentage IS NOT NULL")->fetchColumn() ?: 0);

// ============================================================
// Enrollment trend (last 5 academic years)
// ============================================================
$enrollTrend = Database::fetchAll(
    "SELECT ay.name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.academic_year_id = ay.id) AS cnt
     FROM academic_years ay
     ORDER BY ay.start_date ASC
     LIMIT 5"
);

// ============================================================
// Performance by grade
// ============================================================
$perfByGrade = Database::fetchAll(
    "SELECT g.name, AVG(m.percentage) AS avg_pct
     FROM grades g
     LEFT JOIN students s ON s.id IN (
        SELECT student_id FROM enrollments WHERE grade_id = g.id
     )
     LEFT JOIN marks m ON m.student_id = s.id AND m.percentage IS NOT NULL
     GROUP BY g.id, g.name
     ORDER BY g.level ASC"
);

// ============================================================
// Student distribution by grade
// ============================================================
$distByGrade = Database::fetchAll(
    "SELECT g.name, COUNT(e.id) AS cnt
     FROM grades g
     LEFT JOIN enrollments e ON e.grade_id = g.id AND e.status='active'
     GROUP BY g.id, g.name
     ORDER BY g.level ASC"
);

// ============================================================
// Recent activity (audit logs)
// ============================================================
$recentActivity = Database::fetchAll(
    "SELECT a.action, a.entity, a.description, a.created_at, u.full_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.id DESC
     LIMIT 10"
);

$pageTitle       = 'Admin Dashboard';
$pageDescription = 'Overview of your school at a glance.';
$breadcrumbs     = [['label' => 'Dashboard']];

$extraCss = ['assets/css/dashboard.css'];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="page-desc"><?= e($pageDescription) ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/students/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Student
        </a>
        <a href="<?= e(url('admin/teachers/create.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-person-badge me-1"></i> Add Teacher
        </a>
    </div>
</div>

<!-- ============================================================
     Stat cards
     ============================================================ -->
<div class="row g-3 dashboard-stats mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-primary"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalStudents) ?></div>
                <div class="stat-label">Total Students</div>
                <div class="stat-trend trend-up"><i class="bi bi-arrow-up-right"></i> Active</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-info"><i class="bi bi-person-badge-fill"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalTeachers) ?></div>
                <div class="stat-label">Total Teachers</div>
                <div class="stat-trend text-muted"><i class="bi bi-dot"></i> All campuses</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-success"><i class="bi bi-diagram-3-fill"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalClasses) ?></div>
                <div class="stat-label">Active Classes</div>
                <div class="stat-trend text-muted"><?= $totalGrades ?> grades · <?= $totalSubjects ?> subjects</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-warning"><i class="bi bi-check2-square"></i></div>
            <div>
                <div class="stat-value"><?= $attendancePct ?>%</div>
                <div class="stat-label">Today's Attendance</div>
                <div class="stat-trend <?= $attendancePct >= 90 ? 'trend-up' : 'trend-down' ?>">
                    <?= $presentToday ?>P · <?= $absentToday ?>A · <?= $lateToday ?>L
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     Charts row 1
     ============================================================ -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-graph-up-arrow me-2"></i>Enrollment Trend</h6>
                <span class="text-muted small">Last 5 academic years</span>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartEnrollment"
                            data-labels='<?= json_encode(array_column($enrollTrend, 'name'), JSON_HEX_APOS) ?>'
                            data-values='<?= json_encode(array_map('intval', array_column($enrollTrend, 'cnt')), JSON_HEX_APOS) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-pie-chart me-2"></i>Attendance Today</h6>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartAttendance"
                            data-present="<?= $presentToday ?>"
                            data-absent="<?= $absentToday ?>"
                            data-late="<?= $lateToday ?>"
                            data-excused="<?= $excusedToday ?>"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     Charts row 2 + Activity
     ============================================================ -->
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-bar-chart-line me-2"></i>Average Performance by Grade</h6>
            </div>
            <div class="card-body">
                <div class="chart-wrap-sm chart-wrap">
                    <canvas id="chartPerformance"
                            data-labels='<?= json_encode(array_column($perfByGrade, 'name'), JSON_HEX_APOS) ?>'
                            data-values='<?= json_encode(array_map(fn($r) => round((float)$r['avg_pct'], 1), $perfByGrade), JSON_HEX_APOS) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-diagram-2 me-2"></i>Student Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-wrap-sm chart-wrap">
                    <canvas id="chartDistribution"
                            data-labels='<?= json_encode(array_column($distByGrade, 'name'), JSON_HEX_APOS) ?>'
                            data-values='<?= json_encode(array_map('intval', array_column($distByGrade, 'cnt')), JSON_HEX_APOS) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a class="qa-btn" href="<?= e(url('admin/students/create.php')) ?>">
                        <span class="qa-icon"><i class="bi bi-person-plus"></i></span> Add Student
                    </a>
                    <a class="qa-btn" href="<?= e(url('admin/teachers/create.php')) ?>">
                        <span class="qa-icon"><i class="bi bi-person-badge"></i></span> Add Teacher
                    </a>
                    <a class="qa-btn" href="<?= e(url('admin/attendance/take.php')) ?>">
                        <span class="qa-icon"><i class="bi bi-check2-square"></i></span> Take Attendance
                    </a>
                    <a class="qa-btn" href="<?= e(url('admin/examinations/marks.php')) ?>">
                        <span class="qa-icon"><i class="bi bi-pencil-square"></i></span> Enter Marks
                    </a>
                    <a class="qa-btn" href="<?= e(url('admin/announcements/create.php')) ?>">
                        <span class="qa-icon"><i class="bi bi-megaphone"></i></span> New Announcement
                    </a>
                    <a class="qa-btn" href="<?= e(url('admin/reports/academic.php')) ?>">
                        <span class="qa-icon"><i class="bi bi-file-earmark-text"></i></span> Generate Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     Academic snapshot + Recent activity
     ============================================================ -->
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-journal-check me-2"></i>Academic Snapshot</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-soft">Pending marks</span>
                    <strong><?= number_format($pendingMarks) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-soft">Published results</span>
                    <strong><?= number_format($publishedExams) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-soft">Average performance</span>
                    <strong><?= number_format($avgPerformance, 1) ?>%</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-soft">Total subjects</span>
                    <strong><?= number_format($totalSubjects) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-2"></i>Recent Activity</span>
                <a href="<?= e(url('admin/audit/index.php')) ?>" class="small">View all</a>
            </div>
            <div class="card-body p-0">
                <?php if (!$recentActivity): ?>
                    <?= ui_empty('No activity yet', 'Actions performed in the system will appear here.', 'bi-activity') ?>
                <?php else: ?>
                    <ul class="activity-feed">
                        <?php foreach ($recentActivity as $a): ?>
                            <li>
                                <span class="af-icon"><i class="bi bi-check2-circle"></i></span>
                                <div class="af-body">
                                    <p class="af-title"><?= e($a['description'] ?: ucfirst(str_replace('_', ' ', $a['action']))) ?></p>
                                    <div class="af-meta">
                                        <i class="bi bi-person"></i> <?= e($a['full_name'] ?: 'System') ?>
                                        · <?= e(fmt_date($a['created_at'], 'd M Y H:i')) ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Extra JS for dashboard charts (only on this page)
$extraJs = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', 'assets/js/dashboard.js'];
include __DIR__ . '/../includes/footer.php';
?>