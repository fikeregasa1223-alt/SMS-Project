<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$search      = table_filter('q');
$filterGrade = (int) ($_GET['grade_id'] ?? 0);
$page        = max(1, (int) ($_GET['page'] ?? 1));
$perPage     = 12;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '')   { $where .= " AND (s.name LIKE :q OR s.code LIKE :q)"; $params['q'] = '%' . $search . '%'; }
if ($filterGrade > 0) { $where .= " AND s.grade_id = :g"; $params['g'] = $filterGrade; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM subjects s $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pg = paginate($total, $perPage, $page);

$sql = "SELECT s.*, g.name AS grade_name
        FROM subjects s
        JOIN grades g ON g.id = s.grade_id
        $where
        ORDER BY g.level ASC, s.name ASC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

$pageTitle       = 'Subjects';
$pageDescription = 'Manage subjects for each grade.';
$breadcrumbs     = [['label' => 'Academic'], ['label' => 'Subjects']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/subjects/create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Subject</a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4"><input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search by name or code…"></div>
    <div class="col-md-3">
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $filterGrade === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Search</button></div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100"><i class="bi bi-x-circle me-1"></i> Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No subjects found', 'Create subjects for your grades.', 'bi-journal-bookmark',
                     url('admin/subjects/create.php'), 'New Subject') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Code</th><th>Name</th><th>Grade</th>
                        <th>Credit</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-muted"><?= (int)$r['id'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['code']) ?></span></td>
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><?= e((string)$r['credit']) ?></td>
                        <td><?= ui_badge(ucfirst($r['status']), $r['status'] === 'active' ? 'success' : 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/subjects/edit.php?id='   . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/subjects/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete subject "' . $r['name'] . '"?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['q' => $search, 'grade_id' => $filterGrade]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>