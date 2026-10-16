<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_students');

$search = table_filter('q');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage= 20;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (full_name LIKE :q OR phone LIKE :q OR email LIKE :q)";
    $params['q'] = '%' . $search . '%';
}

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM guardians $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare("SELECT * FROM guardians $where ORDER BY id DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle       = 'Guardians';
$pageDescription = 'Manage guardians and their contact information.';
$breadcrumbs     = [['label' => 'Guardians']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/guardians/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Guardian
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search guardians…">
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Search</button></div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No guardians found', 'Create the first guardian.', 'bi-people',
                     url('admin/guardians/create.php'), 'New Guardian') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Relationship</th><th>Phone</th><th>Email</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $g): ?>
                    <tr>
                        <td class="text-muted"><?= (int)$g['id'] ?></td>
                        <td class="fw-semibold"><?= e($g['full_name']) ?></td>
                        <td><?= ui_badge(ucfirst(str_replace('_', ' ', $g['relationship'])), 'primary') ?></td>
                        <td><?= e($g['phone']) ?></td>
                        <td><?= e($g['email'] ?: '—') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/guardians/edit.php?id=' . (int)$g['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/guardians/delete.php?id=' . (int)$g['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete guardian "' . $g['full_name'] . '"?'],
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