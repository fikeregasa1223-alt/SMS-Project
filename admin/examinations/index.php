<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_marks');

$yearId   = (int) ($_GET['academic_year_id'] ?? 0);
$termId   = (int) ($_GET['term_id'] ?? 0);
$gradeId  = (int) ($_GET['grade_id'] ?? 0);
$type     = (string) ($_GET['type'] ?? '');
$status   = (string) ($_GET['status'] ?? '');
$search   = table_filter('q');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 15;

$where  = "WHERE 1=1";
$params = [];
if ($yearId > 0)  { $where .= " AND e.academic_year_id = :ay"; $params['ay'] = $yearId; }
if ($termId > 0)  { $where .= " AND e.term_id = :t";           $params['t']  = $termId; }
if ($gradeId > 0) { $where .= " AND e.grade_id = :g";          $params['g']  = $gradeId; }
if ($type !== '')   { $where .= " AND e.type = :ty";           $params['ty'] = $type; }
if ($status !== '') { $where .= " AND e.status = :st";         $params['st'] = $status; }
if ($search !== '') { $where .= " AND e.name LIKE :q";         $params['q']  = '%' . $search . '%'; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM examinations e $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$sql = "SELECT e.*, ay.name AS year_name, t.name AS term_name, g.name AS grade_name,
               (SELECT COUNT(*) FROM examination_subjects es WHERE es.examination_id = e.id) AS subjects_count,
               (SELECT COUNT(*) FROM marks m WHERE m.examination_id = e.id) AS marks_count
        FROM examinations e
        JOIN academic_years ay ON ay.id = e.academic_year_id
        JOIN terms t ON t.id = e.term_id
        JOIN grades g ON g.id = e.grade_id
        $where
        ORDER BY e.id DESC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$years  = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$terms  = Database::fetchAll("SELECT id, academic_year_id, name FROM terms ORDER BY sequence ASC");
$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

$pageTitle       = 'Examinations';
$pageDescription = 'Create and manage examinations for each term.';
$breadcrumbs     = [['label' => 'Examinations']];
$extraCss        = ['assets/css/examinations.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/grading/index.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-list-check me-1"></i> Grading Rules
        </a>
        <a href="<?= e(url('admin/examinations/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Examination
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
    <div class="col-md-2">
        <select name="term_id" class="form-select" data-auto-submit>
            <option value="0">All terms</option>
            <?php foreach ($terms as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= $termId === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
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
    <div class="col-md-2">
        <select name="type" class="form-select" data-auto-submit>
            <option value="">All types</option>
            <?php foreach (exam_type_list() as $ty): ?>
                <option value="<?= e($ty) ?>" <?= $type === $ty ? 'selected' : '' ?>><?= e(exam_type_label($ty)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select" data-auto-submit>
            <option value="">All statuses</option>
            <?php foreach (exam_status_list() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search examinations…">
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No examinations found', 'Create your first exam to start entering marks.', 'bi-clipboard-check',
                     url('admin/examinations/create.php'), 'New Examination') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Exam</th><th>Year</th><th>Term</th><th>Grade</th>
                        <th>Type</th><th>Dates</th><th>Max</th><th>Subjects</th>
                        <th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($r['name']) ?></div>
                            <div class="text-muted small"><?= (int)$r['marks_count'] ?> marks entered</div>
                        </td>
                        <td><?= e($r['year_name']) ?></td>
                        <td><?= e($r['term_name']) ?></td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e(exam_type_label($r['type'])) ?></span></td>
                        <td class="text-muted small">
                            <?= e(fmt_date($r['start_date'])) ?>
                            <?php if ($r['end_date']): ?> → <?= e(fmt_date($r['end_date'])) ?><?php endif; ?>
                        </td>
                        <td><?= e((string)$r['max_mark']) ?></td>
                        <td><?= (int)$r['subjects_count'] ?></td>
                        <td><?= exam_status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'View',   'href' => url('admin/examinations/show.php?id='    . (int)$r['id']), 'icon' => 'bi-eye',    'tone' => 'outline-primary'],
                                ['label' => 'Marks',  'href' => url('admin/examinations/marks.php?exam_id=' . (int)$r['id']), 'icon' => 'bi-pencil-square', 'tone' => 'outline-primary'],
                                ['label' => 'Edit',   'href' => url('admin/examinations/edit.php?id='    . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/examinations/delete.php?id='  . (int)$r['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete examination "' . $r['name'] . '"? All marks and exam subjects will be removed.'],
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
                'academic_year_id' => $yearId, 'term_id' => $termId, 'grade_id' => $gradeId,
                'type' => $type, 'status' => $status, 'q' => $search
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>