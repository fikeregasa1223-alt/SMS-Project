<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$yearId    = (int) ($_GET['academic_year_id'] ?? 0);
$termId    = (int) ($_GET['term_id'] ?? 0);
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);

$years    = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$terms    = Database::fetchAll("SELECT id, academic_year_id, name FROM terms ORDER BY sequence ASC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name ASC", [$gradeId])
    : Database::fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

// Default year/term
if ($yearId === 0 && $years) {
    foreach ($years as $y) if ((int)$y['is_current']) { $yearId = (int)$y['id']; break; }
    if ($yearId === 0) $yearId = (int)$years[0]['id'];
}
if ($termId === 0 && $terms) {
    foreach ($terms as $t) if ((int)$t['academic_year_id'] === $yearId) { $termId = (int)$t['id']; break; }
}

// Build summary
$rows = [];
if ($yearId > 0 && $sectionId > 0) {
    $sql = "SELECT s.id, s.student_code, s.first_name, s.middle_name, s.last_name,
                   COUNT(a.id) AS total,
                   SUM(a.status='present') AS present,
                   SUM(a.status='absent')  AS absent,
                   SUM(a.status='late')    AS late,
                   SUM(a.status='excused') AS excused
            FROM students s
            JOIN enrollments e ON e.student_id = s.id AND e.status='active'
            LEFT JOIN attendance a
                ON a.student_id = s.id
               AND a.academic_year_id = e.academic_year_id
               AND a.section_id = e.section_id
               " . ($termId > 0 ? " AND a.term_id = " . (int)$termId : "") . "
            WHERE e.academic_year_id = ? AND e.section_id = ? AND e.status='active'
            GROUP BY s.id, s.student_code, s.first_name, s.middle_name, s.last_name
            ORDER BY s.first_name ASC, s.last_name ASC";
    $rows = Database::fetchAll($sql, [$yearId, $sectionId]);
}

$pageTitle       = 'Attendance Reports';
$pageDescription = 'Per-student attendance summary.';
$breadcrumbs     = [['label' => 'Attendance', 'url' => 'admin/attendance/index.php'], ['label' => 'Reports']];
$extraCss        = ['assets/css/attendance.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-3">
        <label class="form-label small">Academic Year</label>
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Term</label>
        <select name="term_id" class="form-select" data-auto-submit>
            <option value="0">Whole year</option>
            <?php foreach ($terms as $t): ?>
                <?php if ((int)$t['academic_year_id'] !== $yearId) continue; ?>
                <option value="<?= (int)$t['id'] ?>" <?= $termId === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
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
        <label class="form-label small">Section</label>
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">Select section</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<?php if (!$sectionId): ?>
    <div class="table-wrap">
        <?= ui_empty('Choose section', 'Select academic year and section to view the attendance report.', 'bi-bar-chart') ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Student</th><th>Code</th>
                        <th class="text-center text-success">Present</th>
                        <th class="text-center text-danger">Absent</th>
                        <th class="text-center text-warning">Late</th>
                        <th class="text-center text-info">Excused</th>
                        <th class="text-center">Total</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($rows as $r):
                    $name = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                    $tot  = (int)$r['total'];
                    $ok   = (int)$r['present'] + (int)$r['late'] + (int)$r['excused'];
                    $pct  = $tot > 0 ? round(($ok / $tot) * 100, 1) : 0;
                    $cls  = $pct < 60 ? 'danger' : ($pct < 75 ? 'warn' : '');
                ?>
                    <tr>
                        <td class="text-muted"><?= $i++ ?></td>
                        <td class="fw-semibold"><?= e($name) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['student_code']) ?></span></td>
                        <td class="text-center"><span class="text-success fw-semibold"><?= (int)$r['present'] ?></span></td>
                        <td class="text-center"><span class="text-danger fw-semibold"><?= (int)$r['absent'] ?></span></td>
                        <td class="text-center"><span class="text-warning fw-semibold"><?= (int)$r['late'] ?></span></td>
                        <td class="text-center"><span class="text-info fw-semibold"><?= (int)$r['excused'] ?></span></td>
                        <td class="text-center"><?= $tot ?></td>
                        <td style="min-width:120px;">
                            <strong><?= $pct ?>%</strong>
                            <div class="pct-bar <?= $cls ?>"><span style="width: <?= $pct ?>%"></span></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No students enrolled in this section.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>