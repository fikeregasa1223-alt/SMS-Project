<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_reports');

$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);
$status    = (string) ($_GET['status'] ?? 'active');
$gender    = (string) ($_GET['gender'] ?? '');

$where  = "WHERE 1=1";
$params = [];

$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name ASC", [$gradeId])
    : Database::fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

if ($status !== '') { $where .= " AND s.status = :st"; $params['st'] = $status; }
if ($gender !== '') { $where .= " AND s.gender = :g";  $params['g']  = $gender; }
if ($sectionId > 0) { $where .= " AND e.section_id = :sec"; $params['sec'] = $sectionId; }
if ($gradeId > 0)   { $where .= " AND e.grade_id = :gid";   $params['gid'] = $gradeId; }

$rows = Database::fetchAll(
    "SELECT s.*, g.name AS grade_name, sec.name AS section_name
     FROM students s
     LEFT JOIN enrollments e ON e.student_id = s.id AND e.status='active'
     LEFT JOIN grades   g   ON g.id = e.grade_id
     LEFT JOIN sections sec ON sec.id = e.section_id
     $where
     GROUP BY s.id
     ORDER BY g.level ASC, sec.name ASC, s.first_name ASC, s.last_name ASC",
    $params
);

$pageTitle       = 'Student Reports';
$pageDescription = 'Directory and enrollment report.';
$breadcrumbs     = [['label' => 'Reports', 'url' => 'admin/reports/index.php'], ['label' => 'Students']];
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
        <label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Section</label>
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">All sections</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select" data-auto-submit>
            <option value="">All</option>
            <?php foreach (student_status_list() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Gender</label>
        <select name="gender" class="form-select" data-auto-submit>
            <option value="">All</option>
            <?php foreach (gender_list() as $g): ?>
                <option value="<?= e($g) ?>" <?= $gender === $g ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-people me-2"></i>Students</span>
        <span class="text-muted small"><?= count($rows) ?> record(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (!$rows): ?>
            <?= ui_empty('No students', 'Adjust filters to see results.', 'bi-people') ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Code</th><th>Name</th>
                            <th>Grade</th><th>Section</th>
                            <th>Gender</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($rows as $s):
                        $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                    ?>
                        <tr>
                            <td class="text-muted"><?= $i++ ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($s['student_code']) ?></span></td>
                            <td class="fw-semibold"><?= e($name) ?></td>
                            <td><?= e($s['grade_name'] ?: '—') ?></td>
                            <td><?= e($s['section_name'] ?: '—') ?></td>
                            <td><?= e(ucfirst($s['gender'])) ?></td>
                            <td><?= student_status_badge($s['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>