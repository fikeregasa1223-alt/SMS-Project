<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$search   = table_filter('q');
$filterAy = (int) ($_GET['academic_year_id'] ?? 0);
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 10;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '')   { $where .= " AND t.name LIKE :q"; $params['q'] = '%' . $search . '%'; }
if ($filterAy > 0)    { $where .= " AND t.academic_year_id = :ay"; $params['ay'] = $filterAy; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM terms t $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pg = paginate($total, $perPage, $page);

$sql = "SELECT t.*, ay.name AS ay_name
        FROM terms t
        JOIN academic_years ay ON ay.id = t.academic_year_id
        $where
        ORDER BY ay.start_date DESC, t.sequence ASC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$years = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");

$pageTitle       = 'Terms';
$pageDescription = 'Manage terms for each academic year.';
$breadcrumbs     = [['label' => 'Academic'], ['label' => 'Terms']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/terms/create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Term</a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search terms…">
    </div>
    <div class="col-md-3">
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <option value="0">All academic years</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $filterAy === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search me-1"></i> Search</button>
    </div>
    <div class="col-md-2">
        <a href="?" class="btn btn-ghost w-100"><i class="bi bi-x-circle me-1"></i> Reset</a>
    </div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No terms found', 'Create terms under an academic year.', 'bi-calendar-week',
                     url('admin/terms/create.php'), 'New Term') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Academic Year</th>
                        <th>Term</th>
                        <th>Seq.</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Current</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-muted"><?= (int)$r['id'] ?></td>
                        <td><?= e($r['ay_name']) ?></td>
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><?= (int)$r['sequence'] ?></td>
                        <td><?= e(fmt_date($r['start_date'])) ?></td>
                        <td><?= e(fmt_date($r['end_date'])) ?></td>
                        <td><?= ui_bool_badge((bool)$r['is_current']) ?></td>
                        <td><?= ui_badge(ucfirst($r['status']), $r['status'] === 'active' ? 'success' : 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/terms/edit.php?id=' . (int)$r['id']),   'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/terms/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete term "' . $r['name'] . '"?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['q' => $search, 'academic_year_id' => $filterAy]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>