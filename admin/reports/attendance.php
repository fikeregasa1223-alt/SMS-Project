<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_reports');

$yearId    = (int) ($_GET['academic_year_id'] ?? 0);
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);
$dateFrom  = (string) ($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo    = (string) ($_GET['date_to'] ?? date('Y-m-d'));

$years    = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name ASC", [$gradeId])
    : Database::fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

if ($yearId === 0 && $years) {
    foreach ($years as $y) if ((int)$y['is_current']) { $yearId = (int)$y['id']; break; }
}

// Grade summary
$byGrade = Database::fetchAll(
    "SELECT g.name AS grade_name,
            COUNT(a.id) AS total,
            SUM(a.status='present') AS present,
            SUM(a.status='absent')  AS absent,
            SUM(a.status='late')    AS late,
            SUM(a.status='excused') AS excused
     FROM attendance a
     JOIN sections sec ON sec.id = a.section_id
     JOIN grades   g   ON g.id = sec.grade_id
     WHERE a.date BETWEEN ? AND ?
       " . ($yearId ? " AND a.academic_year_id = " . (int)$yearId : "") . "
       " . ($gradeId > 0 ? " AND sec.grade_id = " . (int)$gradeId : "") . "
     GROUP BY g.id, g.name
     ORDER BY g.level ASC",
    [$dateFrom, $dateTo]
);

$pageTitle       = 'Attendance Reports';
$pageDescription = 'Attendance percentages by grade.';
$breadcrumbs     = [['label' => 'Reports', 'url' => 'admin/reports/index.php'], ['label' => 'Attendance']];
$extraCss        = ['assets/css/dashboard.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-2">
        <label class="form-label small">Year</label>
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">From</label>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small">To</label>
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control">
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Apply</button></div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="card">
    <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Attendance by Grade — <?= e(fmt_date($dateFrom)) ?> → <?= e(fmt_date($dateTo)) ?></div>
    <div class="card-body p-0">
        <?php if (!$byGrade): ?>
            <?= ui_empty('No attendance records', 'Adjust your filters.', 'bi-bar-chart') ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th class="text-center">Total</th>
                            <th class="text-center text-success">Present</th>
                            <th class="text-center text-danger">Absent</th>
                            <th class="text-center text-warning">Late</th>
                            <th class="text-center text-info">Excused</th>
                            <th style="min-width:160px;">Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($byGrade as $g):
                        $tot = (int)$g['total'];
                        $ok  = (int)$g['present'] + (int)$g['late'] + (int)$g['excused'];
                        $pct = $tot > 0 ? round(($ok / $tot) * 100, 1) : 0;
                        $cls = $pct < 60 ? 'danger' : ($pct < 75 ? 'warn' : '');
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= e($g['grade_name']) ?></td>
                            <td class="text-center"><?= $tot ?></td>
                            <td class="text-center"><span class="text-success fw-semibold"><?= (int)$g['present'] ?></span></td>
                            <td class="text-center"><span class="text-danger fw-semibold"><?= (int)$g['absent'] ?></span></td>
                            <td class="text-center"><span class="text-warning fw-semibold"><?= (int)$g['late'] ?></span></td>
                            <td class="text-center"><span class="text-info fw-semibold"><?= (int)$g['excused'] ?></span></td>
                            <td>
                                <strong><?= $pct ?>%</strong>
                                <div class="pct-bar <?= $cls ?>"><span style="width: <?= $pct ?>%"></span></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>