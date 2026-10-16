<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$years    = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$terms    = Database::fetchAll("SELECT id, academic_year_id, name FROM terms ORDER BY sequence ASC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

$yearId  = (int) ($_GET['year_id']  ?? 0);
$termId  = (int) ($_GET['term_id']  ?? 0);
$gradeId = (int) ($_GET['grade_id'] ?? 0);

if ($yearId === 0) { foreach ($years as $y) if ((int)$y['is_current']) { $yearId = (int)$y['id']; break; } }
if ($termId === 0) { foreach ($terms as $t) if ((int)$t['academic_year_id'] === $yearId) { $termId = (int)$t['id']; break; } }

$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name ASC", [$gradeId])
    : Database::fetchAll("SELECT sec.id, sec.name, g.name AS grade_name FROM sections sec JOIN grades g ON g.id = sec.grade_id ORDER BY g.level, sec.name");

$pageTitle       = 'Timetable';
$pageDescription = 'Class schedules per section and teacher.';
$breadcrumbs     = [['label' => 'Timetable']];
$extraCss        = ['assets/css/timetable.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/timetable/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Entry
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-3">
        <label class="form-label small">Year</label>
        <select name="year_id" class="form-select" data-auto-submit>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Term</label>
        <select name="term_id" class="form-select" data-auto-submit>
            <?php foreach ($terms as $t): ?>
                <?php if ((int)$t['academic_year_id'] !== $yearId) continue; ?>
                <option value="<?= (int)$t['id'] ?>" <?= $termId === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">— All —</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <?php if (!$sections): ?>
            <?= ui_empty('No sections', 'Create sections first.', 'bi-diagram-3') ?>
        <?php else: ?>
            <?php foreach ($sections as $sec):
                $grid = timetable_grid($yearId, $termId, (int)$sec['id']);
                $periods = timetable_periods($yearId);
            ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><?= e($sec['name']) ?><?= isset($sec['grade_name']) ? ' — ' . e($sec['grade_name']) : '' ?></strong>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/timetable/create.php?year_id=' . $yearId . '&term_id=' . $termId . '&section_id=' . (int)$sec['id'])) ?>">
                            <i class="bi bi-plus"></i> Add Entry
                        </a>
                    </div>
                    <?php if (!$periods): ?>
                        <div class="text-muted small">No periods defined for this year.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="tt-grid">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <?php foreach (timetable_days() as $d => $name): ?>
                                            <th><?= e(substr($name, 0, 3)) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($periods as $p): ?>
                                    <tr>
                                        <td class="tt-period">
                                            <div class="fw-semibold"><?= e($p['name']) ?></div>
                                            <div class="text-muted small"><?= e(substr($p['start_time'],0,5)) ?>–<?= e(substr($p['end_time'],0,5)) ?></div>
                                        </td>
                                        <?php foreach (timetable_days() as $d => $name): ?>
                                            <td>
                                                <?php $cell = $grid[$d][(int)$p['id']] ?? null; ?>
                                                <?php if ($cell): ?>
                                                    <div class="tt-entry">
                                                        <div class="fw-semibold"><?= e($cell['subject_code']) ?></div>
                                                        <div class="small text-muted"><?= e($cell['subject_name']) ?></div>
                                                        <?php if (!empty($cell['teacher_first'])): ?>
                                                            <div class="small"><i class="bi bi-person"></i> <?= e($cell['teacher_first'] . ' ' . $cell['teacher_last']) ?></div>
                                                        <?php endif; ?>
                                                        <a href="<?= e(url('admin/timetable/edit.php?id=' . (int)$cell['id'])) ?>" class="tt-edit"><i class="bi bi-pencil"></i></a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>