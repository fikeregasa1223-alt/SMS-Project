<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_reports');

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

if ($yearId === 0 && $years) {
    foreach ($years as $y) if ((int)$y['is_current']) { $yearId = (int)$y['id']; break; }
    if ($yearId === 0) $yearId = (int)$years[0]['id'];
}

// Performance by grade
$byGrade = Database::fetchAll(
    "SELECT g.name AS grade_name,
            COUNT(m.id) AS marks_count,
            AVG(m.percentage) AS avg_pct
     FROM marks m
     JOIN subjects sub ON sub.id = m.subject_id
     JOIN grades   g   ON g.id = sub.grade_id
     WHERE 1=1
       " . ($yearId ? " AND m.examination_id IN (SELECT id FROM examinations WHERE academic_year_id = " . (int)$yearId . ")" : "") . "
       " . ($termId ? " AND m.examination_id IN (SELECT id FROM examinations WHERE term_id = " . (int)$termId . ")" : "") . "
     GROUP BY g.id, g.name
     ORDER BY g.level ASC"
);

// Top students (average across marks)
$topStudents = Database::fetchAll(
    "SELECT s.id, s.student_code, s.first_name, s.middle_name, s.last_name,
            AVG(m.percentage) AS avg_pct,
            COUNT(m.id) AS marks_count
     FROM marks m
     JOIN students s ON s.id = m.student_id
     WHERE 1=1
       " . ($yearId ? " AND m.examination_id IN (SELECT id FROM examinations WHERE academic_year_id = " . (int)$yearId . ")" : "") . "
       " . ($termId ? " AND m.examination_id IN (SELECT id FROM examinations WHERE term_id = " . (int)$termId . ")" : "") . "
       " . ($gradeId > 0 ? " AND s.id IN (SELECT student_id FROM enrollments WHERE grade_id = " . (int)$gradeId . ")" : "") . "
     GROUP BY s.id
     HAVING marks_count >= 3
     ORDER BY avg_pct DESC
     LIMIT 10"
);

$pageTitle       = 'Academic Reports';
$pageDescription = 'Performance analytics across grades and students.';
$breadcrumbs     = [['label' => 'Reports', 'url' => 'admin/reports/index.php'], ['label' => 'Academic']];
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
    <div class="col-md-3">
        <label class="form-label small">Year</label>
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
    <div class="col-md-3">
        <label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Average Performance by Grade</div>
            <div class="card-body p-0">
                <?php if (!$byGrade): ?>
                    <?= ui_empty('No data', 'Performance will appear once marks are entered.', 'bi-bar-chart') ?>
                <?php else: ?>
                    <table class="table mb-0">
                        <thead><tr><th>Grade</th><th>Marks</th><th>Average %</th></tr></thead>
                        <tbody>
                        <?php foreach ($byGrade as $g):
                            $avg = round((float)$g['avg_pct'], 1);
                            $cls = $avg < 60 ? 'danger' : ($avg < 75 ? 'warn' : '');
                        ?>
                            <tr>
                                <td class="fw-semibold"><?= e($g['grade_name']) ?></td>
                                <td><?= (int)$g['marks_count'] ?></td>
                                <td>
                                    <strong><?= $avg ?>%</strong>
                                    <div class="pct-bar <?= $cls ?>"><span style="width: <?= $avg ?>%"></span></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-trophy me-2"></i>Top Students</div>
            <div class="card-body p-0">
                <?php if (!$topStudents): ?>
                    <?= ui_empty('No data', 'Top students will appear once enough marks are recorded.', 'bi-trophy') ?>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topStudents as $i => $s):
                            $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                            $avg  = round((float)$s['avg_pct'], 1);
                        ?>
                            <li class="list-group-item d-flex align-items-center gap-3">
                                <span class="badge <?= $i < 3 ? 'bg-warning text-dark' : 'bg-light text-dark border' ?>" style="min-width:32px;">#<?= $i + 1 ?></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate"><?= e($name) ?></div>
                                    <div class="text-muted small"><?= e($s['student_code']) ?> · <?= (int)$s['marks_count'] ?> marks</div>
                                </div>
                                <strong><?= $avg ?>%</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>