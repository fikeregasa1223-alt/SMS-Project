<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_marks');

$rows = Database::fetchAll(
    "SELECT gr.*, g.name AS grade_name
     FROM grading_rules gr
     LEFT JOIN grades g ON g.id = gr.grade_id
     ORDER BY gr.min_score DESC"
);

$pageTitle       = 'Grading Rules';
$pageDescription = 'Configure grade boundaries and points.';
$breadcrumbs     = [['label' => 'Examinations', 'url' => 'admin/examinations/index.php'], ['label' => 'Grading Rules']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/grading/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Rule
        </a>
    </div>
</div>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No grading rules', 'Create your first grading rule.', 'bi-list-check',
                     url('admin/grading/create.php'), 'New Rule') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Grade</th><th>Min %</th><th>Max %</th>
                        <th>Letter</th><th>Points</th><th>Remark</th>
                        <th>Active</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['grade_name'] ?: 'Global') ?></td>
                        <td><?= e((string)$r['min_score']) ?></td>
                        <td><?= e((string)$r['max_score']) ?></td>
                        <td><?= grade_badge($r['letter']) ?></td>
                        <td><?= e((string)$r['points']) ?></td>
                        <td class="text-muted small"><?= e($r['remark'] ?: '—') ?></td>
                        <td><?= (int)$r['is_active'] ? ui_badge('Yes', 'success') : ui_badge('No', 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/grading/edit.php?id=' . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/grading/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete grading rule?'],
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