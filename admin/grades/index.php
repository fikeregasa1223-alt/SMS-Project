<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$rows = Database::fetchAll(
    "SELECT g.*, (SELECT COUNT(*) FROM sections s WHERE s.grade_id = g.id) AS sections_count,
                   (SELECT COUNT(*) FROM subjects s WHERE s.grade_id = g.id) AS subjects_count
     FROM grades g
     ORDER BY g.level ASC"
);

$pageTitle       = 'Grades';
$pageDescription = 'Manage grade levels (Grade 9 – 12).';
$breadcrumbs     = [['label' => 'Academic'], ['label' => 'Grades']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/grades/create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Grade</a>
    </div>
</div>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No grades found', 'Add grade levels to organize your school.', 'bi-mortarboard',
                     url('admin/grades/create.php'), 'New Grade') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Level</th>
                        <th>Sections</th><th>Subjects</th>
                        <th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-muted"><?= (int)$r['id'] ?></td>
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><span class="badge bg-light text-dark border">Level <?= (int)$r['level'] ?></span></td>
                        <td><?= (int)$r['sections_count'] ?></td>
                        <td><?= (int)$r['subjects_count'] ?></td>
                        <td><?= ui_badge(ucfirst($r['status']), $r['status'] === 'active' ? 'success' : 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/grades/edit.php?id='   . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/grades/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete grade "' . $r['name'] . '"? Sections and subjects may be affected.'],
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