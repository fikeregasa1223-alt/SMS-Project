<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_reports');

// Attendance trend (last 30 days)
$trend = Database::fetchAll(
    "SELECT date,
            COUNT(*) AS total,
            SUM(status='present') AS present,
            SUM(status='absent')  AS absent
     FROM attendance
     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY date ORDER BY date ASC"
);

$trendLabels = array_map(fn($r) => date('d M', strtotime($r['date'])), $trend);
$trendPct    = array_map(function ($r) {
    $t = (int)$r['total']; if ($t === 0) return 0;
    return round((((int)$r['present']) / $t) * 100, 1);
}, $trend);

// Performance by grade
$perf = Database::fetchAll(
    "SELECT g.name AS grade_name, AVG(m.percentage) AS avg_pct, COUNT(m.id) AS marks_count
     FROM marks m JOIN subjects sub ON sub.id = m.subject_id
     JOIN grades g ON g.id = sub.grade_id
     GROUP BY g.id ORDER BY g.level ASC"
);

// Gender distribution
$gender = Database::fetchAll(
    "SELECT gender, COUNT(*) AS cnt FROM students WHERE status='active' GROUP BY gender"
);

// Grade distribution
$gradeDist = Database::fetchAll(
    "SELECT g.name, COUNT(e.id) AS cnt FROM grades g
     LEFT JOIN enrollments e ON e.grade_id = g.id AND e.status='active'
     GROUP BY g.id ORDER BY g.level ASC"
);

$pageTitle       = 'Analytics';
$pageDescription = 'Performance and attendance insights.';
$breadcrumbs     = [['label' => 'Analytics']];
$extraCss        = ['assets/css/dashboard.css'];
$extraJs         = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', 'assets/js/dashboard.js'];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card chart-card">
            <div class="card-header"><h6><i class="bi bi-graph-up me-2"></i>Attendance Trend — Last 30 Days</h6></div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartAttRate"
                            data-labels='<?= json_encode($trendLabels) ?>'
                            data-values='<?= json_encode($trendPct) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header"><h6><i class="bi bi-pie-chart me-2"></i>Gender Distribution</h6></div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartDistribution"
                            data-labels='<?= json_encode(array_column($gender, 'gender')) ?>'
                            data-values='<?= json_encode(array_map('intval', array_column($gender, 'cnt'))) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h6><i class="bi bi-bar-chart me-2"></i>Average Performance by Grade</h6></div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartPerformance"
                            data-labels='<?= json_encode(array_column($perf, 'grade_name')) ?>'
                            data-values='<?= json_encode(array_map(fn($r) => round((float)$r['avg_pct'], 1), $perf)) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h6><i class="bi bi-people me-2"></i>Students by Grade</h6></div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartEnrollment"
                            data-labels='<?= json_encode(array_column($gradeDist, 'name')) ?>'
                            data-values='<?= json_encode(array_map('intval', array_column($gradeDist, 'cnt'))) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>