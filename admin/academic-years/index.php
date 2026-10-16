<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('admin');

$search = table_filter('q');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where  = '';
$params = [];
if ($search !== '') {
    $where  = "WHERE name LIKE :q";
    $params = ['q' => '%' . $search . '%'];
}

$total = (int) Database::conn()->prepare("SELECT COUNT(*) FROM academic_years $where")
    ->execute($params) ?: 0;
$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM academic_years $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pg = paginate($total, $perPage, $page);

$sql = "SELECT * FROM academic_years $where ORDER BY start_date DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle       = 'Academic Years';
$pageDescription = 'Manage academic years for your school.';
$breadcrumbs     = [['label' => 'Academic'], ['label' => 'Academic Years']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="page-desc"><?= e($pageDescription) ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/academic-years/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Academic Year
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search academic years…">
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
        <?= ui_empty('No academic years found', 'Create your first academic year to get started.', 'bi-calendar3',
                     url('admin/academic-years/create.php'), 'New Academic Year') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="tblAcademicYears">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
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
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><?= e(fmt_date($r['start_date'])) ?></td>
                        <td><?= e(fmt_date($r['end_date'])) ?></td>
                        <td><?= ui_bool_badge((bool)$r['is_current'], 'Yes', 'No') ?></td>
                        <td><?= ui_badge(ucfirst($r['status']), $r['status'] === 'active' ? 'success' : 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit', 'href' => url('admin/academic-years/edit.php?id=' . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/academic-years/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete "' . $r['name'] . '"? Related terms may also be removed.'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['q' => $search]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>