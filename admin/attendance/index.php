<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$today = date('Y-m-d');

// Optional date filter for the summary cards
$filterDate = (string) ($_GET['date'] ?? $today);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $filterDate = $today;
}

// ------------------------------------------------------------
// Summary for the selected day
// ------------------------------------------------------------
$summary = Database::fetch(
    "SELECT
        COUNT(*) AS total,
        SUM(status='present') AS present,
        SUM(status='absent')  AS absent,
        SUM(status='late')    AS late,
        SUM(status='excused') AS excused
     FROM attendance WHERE date = ?",
    [$filterDate]
) ?: ['total'=>0,'present'=>0,'absent'=>0,'late'=>0,'excused'=>0];

$pct = ($summary['total'] > 0)
    ? round((($summary['present'] + $summary['late'] + $summary['excused']) / $summary['total']) * 100, 1)
    : 0;

// ------------------------------------------------------------
// Last 7 days trend (for line chart + table)
// ------------------------------------------------------------
$trend = Database::fetchAll(
    "SELECT date,
            COUNT(*) AS total,
            SUM(status='present') AS present,
            SUM(status='absent')  AS absent,
            SUM(status='late')    AS late,
            SUM(status='excused') AS excused
     FROM attendance
     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY date
     ORDER BY date ASC"
);

$trendLabels = [];
$trendPct    = [];
foreach ($trend as $d) {
    $t = (int)$d['total'];
    $ok = (int)$d['present'] + (int)$d['late'] + (int)$d['excused'];
    $trendLabels[] = date('D', strtotime($d['date']));
    $trendPct[]    = $t > 0 ? round(($ok / $t) * 100, 1) : 0;
}

// ------------------------------------------------------------
// Grade-wise summary for the selected day
// ------------------------------------------------------------
$byGrade = Database::fetchAll(
    "SELECT g.name AS grade_name,
            COUNT(a.id) AS total,
            SUM(a.status='present') AS present,
            SUM(a.status='absent')  AS absent,
            SUM(a.status='late')    AS late,
            SUM(a.status='excused') AS excused
     FROM attendance a
     JOIN sections sec ON sec.id = a.section_id
     JOIN grades   g   ON g.id   = sec.grade_id
     WHERE a.date = ?
     GROUP BY g.id, g.name
     ORDER BY g.level ASC",
    [$filterDate]
);

// ------------------------------------------------------------
// Top absentees (last 7 days, requires at least 3 records)
// ------------------------------------------------------------
$topAbsent = Database::fetchAll(
    "SELECT s.id, s.student_code, s.first_name, s.middle_name, s.last_name,
            COUNT(a.id) AS total,
            SUM(a.status='absent') AS absent
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY s.id
     HAVING absent >= 2
     ORDER BY absent DESC, s.first_name ASC
     LIMIT 5"
);

// ------------------------------------------------------------
// Top attenders (last 7 days)
// ------------------------------------------------------------
$topPresent = Database::fetchAll(
    "SELECT s.id, s.student_code, s.first_name, s.middle_name, s.last_name,
            COUNT(a.id) AS total,
            SUM(a.status='present') AS present
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY s.id
     HAVING present >= 3
     ORDER BY present DESC, s.first_name ASC
     LIMIT 5"
);

// ------------------------------------------------------------
// Recent activity
// ------------------------------------------------------------
$recentActivity = Database::fetchAll(
    "SELECT a.action, a.description, a.created_at, u.full_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.action IN ('attendance_saved','attendance_updated')
     ORDER BY a.id DESC
     LIMIT 8"
);

$pageTitle       = 'Attendance';
$pageDescription = 'Attendance overview and analytics.';
$breadcrumbs     = [['label' => 'Attendance']];
$extraCss        = ['assets/css/attendance.css', 'assets/css/dashboard.css'];
$extraJs         = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
    'assets/js/dashboard.js',
];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/attendance/take.php')) ?>" class="btn btn-primary">
            <i class="bi bi-check2-square me-1"></i> Take Attendance
        </a>
        <a href="<?= e(url('admin/attendance/reports.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-bar-chart me-1"></i> Reports
        </a>
    </div>
</div>

<!-- Date filter -->
<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-3">
        <label class="form-label small mb-1">View date</label>
        <input type="date" name="date" value="<?= e($filterDate) ?>" class="form-control" data-auto-submit>
    </div>
    <div class="col-md-2">
        <a href="?" class="btn btn-ghost w-100"><i class="bi bi-arrow-clockwise me-1"></i> Today</a>
    </div>
</form>

<!-- Summary stat cards -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Present', (int)$summary['present'], 'bi-check-circle-fill', 'success') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Absent',  (int)$summary['absent'],  'bi-x-circle-fill',     'danger') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Late',    (int)$summary['late'],    'bi-clock-fill',        'warning') ?></div>
    <div class="col-sm-6 col-xl-3"><?= attendance_stat_card('Attendance %', (float)$pct,           'bi-percent',           'primary') ?></div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-graph-up-arrow me-2"></i>Attendance Rate — Last 7 Days</h6>
                <span class="text-muted small">% present + late + excused</span>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartAttRate"
                            data-labels='<?= json_encode($trendLabels, JSON_HEX_APOS) ?>'
                            data-values='<?= json_encode($trendPct, JSON_HEX_APOS) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header">
                <h6><i class="bi bi-pie-chart me-2"></i><?= e(fmt_date($filterDate, 'd M Y')) ?></h6>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="chartAttToday"
                            data-present="<?= (int)$summary['present'] ?>"
                            data-absent="<?= (int)$summary['absent'] ?>"
                            data-late="<?= (int)$summary['late'] ?>"
                            data-excused="<?= (int)$summary['excused'] ?>"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trend table + Grade summary -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-table me-2"></i>Daily Breakdown</div>
            <div class="card-body p-0">
                <?php if (!$trend): ?>
                    <?= ui_empty('No attendance yet', 'Attendance trend will appear here.', 'bi-graph-up') ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Date</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>%</th></tr></thead>
                            <tbody>
                            <?php foreach (array_reverse($trend) as $d):
                                $t = (int)$d['total'];
                                $ok = (int)$d['present'] + (int)$d['late'] + (int)$d['excused'];
                                $dp = $t > 0 ? round(($ok / $t) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?= e(fmt_date($d['date'], 'D, d M')) ?></td>
                                    <td><span class="text-success fw-semibold"><?= (int)$d['present'] ?></span></td>
                                    <td><span class="text-danger fw-semibold"><?= (int)$d['absent'] ?></span></td>
                                    <td><span class="text-warning fw-semibold"><?= (int)$d['late'] ?></span></td>
                                    <td><span class="text-info fw-semibold"><?= (int)$d['excused'] ?></span></td>
                                    <td>
                                        <strong><?= $dp ?>%</strong>
                                        <div class="pct-bar <?= $dp < 75 ? 'warn' : '' ?> <?= $dp < 60 ? 'danger' : '' ?>">
                                            <span style="width: <?= $dp ?>%"></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>By Grade — <?= e(fmt_date($filterDate, 'd M')) ?></div>
            <div class="card-body p-0">
                <?php if (!$byGrade): ?>
                    <?= ui_empty('No data', 'Grade-wise attendance will appear once recorded.', 'bi-bar-chart') ?>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($byGrade as $g):
                            $tot = (int)$g['total'];
                            $ok  = (int)$g['present'] + (int)$g['late'] + (int)$g['excused'];
                            $gp  = $tot > 0 ? round(($ok / $tot) * 100, 1) : 0;
                        ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold"><?= e($g['grade_name']) ?></span>
                                    <span class="text-muted small"><?= (int)$g['present'] ?> / <?= $tot ?></span>
                                </div>
                                <div class="pct-bar <?= $gp < 75 ? 'warn' : '' ?> <?= $gp < 60 ? 'danger' : '' ?>">
                                    <span style="width: <?= $gp ?>%"></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top absentees + top attenders -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle me-2"></i>Top Absentees
                <span class="text-muted small ms-2">Last 7 days</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$topAbsent): ?>
                    <?= ui_empty('No absentee data', 'Students with repeated absences will appear here.', 'bi-person-x') ?>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topAbsent as $s):
                            $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                            $rate = (int)$s['total'] > 0 ? round(((int)$s['absent'] / (int)$s['total']) * 100, 1) : 0;
                        ?>
                            <li class="list-group-item d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm" style="background: rgba(239,68,68,.15); color: #b91c1c;">
                                    <?= e(mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1))) ?>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate"><?= e($name) ?></div>
                                    <div class="text-muted small"><?= e($s['student_code']) ?> · <?= $rate ?>% absent</div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                    <?= (int)$s['absent'] ?> absences
                                </span>
                                <a href="<?= e(url('admin/students/show.php?id=' . (int)$s['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-trophy me-2"></i>Top Attenders
                <span class="text-muted small ms-2">Last 7 days</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$topPresent): ?>
                    <?= ui_empty('No attendance data', 'Students with best attendance will appear here.', 'bi-trophy') ?>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topPresent as $s):
                            $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                            $rate = (int)$s['total'] > 0 ? round(((int)$s['present'] / (int)$s['total']) * 100, 1) : 0;
                        ?>
                            <li class="list-group-item d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm" style="background: rgba(16,185,129,.15); color: #047857;">
                                    <?= e(mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1))) ?>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate"><?= e($name) ?></div>
                                    <div class="text-muted small"><?= e($s['student_code']) ?> · <?= $rate ?>% present</div>
                                </div>
                                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                    <?= (int)$s['present'] ?> presents
                                </span>
                                <a href="<?= e(url('admin/students/show.php?id=' . (int)$s['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent activity -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-activity me-2"></i>Recent Attendance Activity
    </div>
    <div class="card-body p-0">
        <?php if (!$recentActivity): ?>
            <?= ui_empty('No recent activity', 'Attendance saves will appear here.', 'bi-activity') ?>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>