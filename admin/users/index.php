<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_users');

$search = table_filter('q');
$roleSlug = (string) ($_GET['role'] ?? '');
$status   = (string) ($_GET['status'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 20;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (u.username LIKE :q OR u.email LIKE :q OR u.full_name LIKE :q)";
    $params['q'] = '%' . $search . '%';
}
if ($status !== '') { $where .= " AND u.status = :st"; $params['st'] = $status; }

if ($roleSlug !== '') {
    $where .= " AND EXISTS (
        SELECT 1 FROM user_roles ur2
        JOIN roles r2 ON r2.id = ur2.role_id
        WHERE ur2.user_id = u.id AND r2.slug = :rs
    )";
    $params['rs'] = $roleSlug;
}

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM users u $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare(
    "SELECT u.*,
            (SELECT GROUP_CONCAT(r.slug ORDER BY r.id SEPARATOR ',')
             FROM user_roles ur JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = u.id) AS roles_csv
     FROM users u
     $where
     ORDER BY u.id DESC
     LIMIT {$pg['limit']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$roles = Database::fetchAll("SELECT id, name, slug FROM roles ORDER BY id ASC");

$pageTitle       = 'Users';
$pageDescription = 'Manage system user accounts and roles.';
$breadcrumbs     = [['label' => 'Administration'], ['label' => 'Users']];
$extraCss        = ['assets/css/admin-settings.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/users/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> New User
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search username, email, name…">
    </div>
    <div class="col-md-2">
        <select name="role" class="form-select" data-auto-submit>
            <option value="">All roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= e($r['slug']) ?>" <?= $roleSlug === $r['slug'] ? 'selected' : '' ?>>
                    <?= e($r['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select" data-auto-submit>
            <option value="">All statuses</option>
            <?php foreach (['active','inactive','suspended','locked'] as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Filter</button></div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No users found', 'Create your first user.', 'bi-person-gear',
                     url('admin/users/create.php'), 'New User') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th><th>Username</th><th>Roles</th>
                        <th>Status</th><th>Last login</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $u):
                    $initials = mb_strtoupper(mb_substr($u['full_name'] ?? 'U', 0, 1));
                    $rolesArr = explode(',', (string)$u['roles_csv']);
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm"><?= e($initials) ?></span>
                                <div>
                                    <div class="fw-semibold"><?= e($u['full_name']) ?></div>
                                    <div class="text-muted small"><?= e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['username']) ?></td>
                        <td>
                            <?php foreach ($rolesArr as $rs): ?>
                                <span class="role-pill <?= e($rs) ?>"><?= e(ucfirst($rs)) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= user_status_badge($u['status']) ?></td>
                        <td class="text-muted small"><?= $u['last_login_at'] ? e(fmt_date($u['last_login_at'], 'd M Y H:i')) : '—' ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit',   'href' => url('admin/users/edit.php?id=' . (int)$u['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Reset',  'href' => url('admin/users/reset-password.php?id=' . (int)$u['id']), 'icon' => 'bi-key', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/users/delete.php?id=' . (int)$u['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete user "' . $u['username'] . '"? This cannot be undone.'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['q' => $search, 'role' => $roleSlug, 'status' => $status]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>