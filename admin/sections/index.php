<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$filterGrade = (int) ($_GET['grade_id'] ?? 0);
$where  = "WHERE 1=1";
$params = [];
if ($filterGrade > 0) { $where .= " AND s.grade_id = :g"; $params['g'] = $filterGrade; }

$rows = Database::fetchAll(
    "SELECT s.*, g.name AS grade_name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.id AND e.status='active') AS students_count
     FROM sections s
     JOIN grades g ON g.id = s.grade_id
     $where
     ORDER BY g.level ASC, s.name ASC",
    $params
);

$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

$pageTitle       = 'Sections';
$pageDescription = 'Manage class sections for each grade.';
$breadcrumbs     = [['label' => 'Academic'], ['label' => 'Sections']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/sections/create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Section</a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-3">
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $filterGrade === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100"><i class="bi bi-x-circle me-1"></i> Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No sections found', 'Create a section under a grade.', 'bi-diagram-3',
                     url('admin/sections/create.php'), 'New Section') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Grade</th><th>Section</th>
                        <th>Room</th><th>Capacity</th><th>Students</th>
                        <th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-muted"><?= (int)$r['id'] ?></td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><?= e($r['room'] ?: '—') ?></td>
                        <td><?= (int)$r['capacity'] ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= (int)$r['students_count'] ?> / <?= (int)$r['capacity'] ?>
                            </span>
                        </td>
                        <td><?= ui_badge(ucfirst($r['status']), $r['status'] === 'active' ? 'success' : 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/sections/edit.php?id='   . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/sections/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete section "' . $r['grade_name'] . ' — ' . $r['name'] . '"?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>