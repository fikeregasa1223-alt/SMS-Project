<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_teachers');

$search = table_filter('q');
$status = (string) ($_GET['employment_status'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage= 20;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (first_name LIKE :q OR last_name LIKE :q OR teacher_code LIKE :q
                     OR employee_no LIKE :q OR email LIKE :q)";
    $params['q'] = '%' . $search . '%';
}
if ($status !== '') {
    $where .= " AND employment_status = :st";
    $params['st'] = $status;
}

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM teachers $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pg = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare("SELECT * FROM teachers $where ORDER BY id DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle       = 'Teachers';
$pageDescription = 'Manage teacher records, assignments, and profiles.';
$breadcrumbs     = [['label' => 'Teachers']];
$extraCss        = ['assets/css/teachers.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/teachers/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Teacher
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control"
               placeholder="Search by name, code, employee no, email…">
    </div>
    <div class="col-md-3">
        <select name="employment_status" class="form-select" data-auto-submit>
            <option value="">All employment types</option>
            <?php foreach (employment_status_list() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
                    <?= e(ucfirst(str_replace('_', ' ', $st))) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
    </div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No teachers found', 'Add your first teacher.', 'bi-person-badge',
                     url('admin/teachers/create.php'), 'Add Teacher') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Code</th>
                        <th>Employee No</th>
                        <th>Specialization</th>
                        <th>Employment</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $t):
                    $photo = teacher_photo_url($t);
                    $name  = teacher_full_name($t);
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm">
                                    <?php if ($photo): ?>
                                        <img src="<?= e($photo) ?>" alt="">
                                    <?php else: ?>
                                        <?= e(mb_strtoupper(mb_substr($t['first_name'] ?? 'T', 0, 1) . mb_substr($t['last_name'] ?? '', 0, 1))) ?>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <div class="fw-semibold"><?= e($name) ?></div>
                                    <div class="text-muted small"><?= e($t['email'] ?: '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e($t['teacher_code']) ?></span></td>
                        <td><span class="text-muted small"><?= e($t['employee_no']) ?></span></td>
                        <td><?= e($t['specialization'] ?: '—') ?></td>
                        <td><?= employment_status_badge($t['employment_status']) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'View',   'href' => url('admin/teachers/show.php?id='   . (int)$t['id']), 'icon' => 'bi-eye',    'tone' => 'outline-primary'],
                                ['label' => 'Edit',   'href' => url('admin/teachers/edit.php?id='   . (int)$t['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/teachers/delete.php?id=' . (int)$t['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete teacher "' . $name . '"?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['q' => $search, 'employment_status' => $status]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>