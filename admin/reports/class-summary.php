<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_reports');

$yearId  = (int) ($_GET['academic_year_id'] ?? 0);
$gradeId = (int) ($_GET['grade_id'] ?? 0);

$years  = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

if ($yearId === 0 && $years) {
    foreach ($years as $y) if ((int)$y['is_current']) { $yearId = (int)$y['id']; break; }
}

$rows = Database::fetchAll(
    "SELECT sec.id, sec.name AS section_name, g.name AS grade_name,
            COUNT(DISTINCT e.student_id) AS student_count,
            (SELECT COUNT(*) FROM attendance a WHERE a.section_id = sec.id " .
                ($yearId ? " AND a.academic_year_id = " . (int)$yearId : "") . "
             ) AS attendance_records,
            (SELECT AVG(m.percentage) FROM marks m WHERE m.section_id = sec.id
             ) AS avg_score
     FROM sections sec
     JOIN grades g ON g.id = sec.grade_id
     LEFT JOIN enrollments e
       ON e.section_id = sec.id AND e.status='active'
       " . ($yearId ? " AND e.academic_year_id = " . (int)$yearId : "") . "
     " . ($gradeId > 0 ? " WHERE sec.grade_id = " . (int)$gradeId : "") . "
     GROUP BY sec.id, sec.name, g.name
     ORDER BY g.level ASC, sec.name ASC"
);

$pageTitle       = 'Class Summary Report';
$pageDescription = 'Enrollment, attendance, and average performance per class.';
$breadcrumbs     = [['label' => 'Reports', 'url' => 'admin/reports/index.php'], ['label' => 'Class Summary']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-3">
        <label class="form-label small">Year</label>
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="card">
    <div class="card-body p-0">
        <?php if (!$rows): ?>
            <?= ui_empty('No data', 'Create sections and enrollments first.', 'bi-diagram-3') ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Grade</th><th>Section</th><th class="text-center">Students</th><th class="text-center">Attendance</th><th>Average %</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r):
                        $avg = round((float)($r['avg_score'] ?? 0), 1);
                        $cls = $avg < 60 ? 'danger' : ($avg < 75 ? 'warn' : '');
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= e($r['grade_name']) ?></td>
                            <td><?= e($r['section_name']) ?></td>
                            <td class="text-center"><?= (int)$r['student_count'] ?></td>
                            <td class="text-center text-muted small"><?= (int)$r['attendance_records'] ?> records</td>
                            <td>
                                <?php if ($avg > 0): ?>
                                    <strong><?= $avg ?>%</strong>
                                    <div class="pct-bar <?= $cls ?>"><span style="width: <?= $avg ?>%"></span></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
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