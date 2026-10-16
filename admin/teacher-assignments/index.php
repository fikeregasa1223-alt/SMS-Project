<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_teachers');

$yearId    = (int) ($_GET['academic_year_id'] ?? 0);
$teacherId = (int) ($_GET['teacher_id'] ?? 0);
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 20;

$where  = "WHERE 1=1";
$params = [];
if ($yearId > 0)    { $where .= " AND ta.academic_year_id = :ay"; $params['ay'] = $yearId; }
if ($teacherId > 0) { $where .= " AND ta.teacher_id = :t";        $params['t']  = $teacherId; }
if ($gradeId > 0)   { $where .= " AND ta.grade_id = :g";          $params['g']  = $gradeId; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM teacher_assignments ta $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$sql = "SELECT ta.*, t.teacher_code, t.first_name, t.middle_name, t.last_name,
               g.name AS grade_name, sec.name AS section_name,
               sub.name AS subject_name, sub.code AS subject_code,
               ay.name AS year_name
        FROM teacher_assignments ta
        JOIN teachers t ON t.id = ta.teacher_id
        JOIN grades   g ON g.id = ta.grade_id
        JOIN sections sec ON sec.id = ta.section_id
        JOIN subjects sub ON sub.id = ta.subject_id
        JOIN academic_years ay ON ay.id = ta.academic_year_id
        $where
        ORDER BY ta.id DESC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$years    = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");
$teachers = Database::fetchAll("SELECT id, teacher_code, first_name, middle_name, last_name FROM teachers ORDER BY first_name");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

$pageTitle       = 'Teacher Assignments';
$pageDescription = 'Assign teachers to sections and subjects.';
$breadcrumbs     = [['label' => 'Teachers'], ['label' => 'Assignments']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/teacher-assignments/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Assignment
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
        <select name="teacher_id" class="form-select" data-auto-submit>
            <option value="0">All teachers</option>
            <?php foreach ($teachers as $t):
                $nm = trim(($t['first_name'] ?? '') . ' ' . ($t['middle_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
            ?>
                <option value="<?= (int)$t['id'] ?>" <?= $teacherId === (int)$t['id'] ? 'selected' : '' ?>>
                    <?= e($t['teacher_code']) ?> — <?= e($nm) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No assignments', 'Create the first teacher assignment.', 'bi-person-workspace',
                     url('admin/teacher-assignments/create.php'), 'New Assignment') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Teacher</th><th>Year</th><th>Grade</th><th>Section</th>
                        <th>Subject</th><th>Class Teacher</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $tn = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($tn) ?></div>
                            <div class="text-muted small"><?= e($r['teacher_code']) ?></div>
                        </td>
                        <td><?= e($r['year_name']) ?></td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><?= e($r['section_name']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['subject_code']) ?></span> <?= e($r['subject_name']) ?></td>
                        <td><?= (int)$r['is_class_teacher'] ? ui_badge('Yes', 'primary') : '—' ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/teacher-assignments/edit.php?id=' . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/teacher-assignments/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete this assignment?'],
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
                'academic_year_id' => $yearId, 'teacher_id' => $teacherId, 'grade_id' => $gradeId
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>