<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_students');

$yearId    = (int) ($_GET['academic_year_id'] ?? 0);
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 20;

$where  = "WHERE 1=1";
$params = [];
if ($yearId > 0)    { $where .= " AND e.academic_year_id = :ay"; $params['ay'] = $yearId; }
if ($gradeId > 0)   { $where .= " AND e.grade_id = :g";         $params['g']  = $gradeId; }
if ($sectionId > 0) { $where .= " AND e.section_id = :sec";     $params['sec']= $sectionId; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM enrollments e $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$sql = "SELECT e.*, s.first_name, s.middle_name, s.last_name, s.student_code, s.photo,
               g.name AS grade_name, sec.name AS section_name, ay.name AS year_name
        FROM enrollments e
        JOIN students s     ON s.id     = e.student_id
        JOIN grades   g     ON g.id     = e.grade_id
        JOIN sections sec   ON sec.id   = e.section_id
        JOIN academic_years ay ON ay.id = e.academic_year_id
        $where
        ORDER BY e.id DESC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$years    = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

$pageTitle       = 'Enrollments';
$pageDescription = 'Manage student enrollments per academic year.';
$breadcrumbs     = [['label' => 'Students'], ['label' => 'Enrollments']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/enrollments/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Enrollment
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-3">
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <option value="0">All years</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">All sections</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No enrollments found', 'Create the first enrollment.', 'bi-journal',
                     url('admin/enrollments/create.php'), 'New Enrollment') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th><th>Year</th><th>Grade</th><th>Section</th>
                        <th>Roll</th><th>Status</th><th>Date</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e(trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?></div>
                            <div class="text-muted small"><?= e($r['student_code']) ?></div>
                        </td>
                        <td><?= e($r['year_name']) ?></td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><?= e($r['section_name']) ?></td>
                        <td><?= e((string)($r['roll_no'] ?? '—')) ?></td>
                        <td><?= ui_badge(ucfirst($r['status']), $r['status'] === 'active' ? 'success' : 'secondary') ?></td>
                        <td><?= e(fmt_date($r['enrollment_date'])) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/enrollments/edit.php?id=' . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/enrollments/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete this enrollment?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], [
                'academic_year_id' => $yearId, 'grade_id' => $gradeId, 'section_id' => $sectionId
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>