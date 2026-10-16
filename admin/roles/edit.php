<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_roles');

$id  = (int) ($_GET['id'] ?? 0);
$role = Database::fetch("SELECT * FROM roles WHERE id = ?", [$id]);
if (!$role) { flash_set('error', 'Role not found.'); redirect(url('admin/roles/index.php')); }

// Group permissions
$perms = Database::fetchAll("SELECT * FROM permissions ORDER BY module ASC, id ASC");
$grouped = [];
foreach ($perms as $p) {
    $grouped[$p['module']][] = $p;
}

// Current role permissions
$current = array_map(
    fn($r) => (int)$r['permission_id'],
    Database::fetchAll("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$id])
);

$pageTitle       = 'Edit Role — ' . $role['name'];
$pageDescription = 'Assign permissions to this role.';
$breadcrumbs     = [['label' => 'Roles', 'url' => 'admin/roles/index.php'], ['label' => 'Edit']];
$extraCss        = ['assets/css/admin-settings.css'];
$extraJs         = ['assets/js/admin-settings.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="page-desc"><?= e($pageDescription) ?></p>
    </div>
    <div class="page-actions">
        <span class="role-pill <?= e($role['slug']) ?>"><?= e($role['name']) ?></span>
    </div>
</div>

<form method="post" action="<?= e(url('admin/roles/save-permissions.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="role_id" value="<?= (int)$id ?>">

    <div class="row g-3">
        <?php foreach ($grouped as $module => $list): ?>
            <div class="col-md-6 col-xl-4">
                <div class="perm-group">
                    <h6>
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="checkbox" class="form-check-input"
                                   data-toggle-group="mod-<?= e($module) ?>">
                            <span><?= e(ucfirst($module)) ?></span>
                        </label>
                    </h6>
                    <?php foreach ($list as $p): ?>
                        <div class="perm-row">
                            <input class="form-check-input" type="checkbox"
                                   name="permission_ids[]" value="<?= (int)$p['id'] ?>"
                                   data-perm-group="mod-<?= e($module) ?>"
                                   id="perm-<?= (int)$p['id'] ?>"
                                   <?= in_array((int)$p['id'], $current, true) ? 'checked' : '' ?>>
                            <label for="perm-<?= (int)$p['id'] ?>">
                                <div class="fw-semibold"><?= e($p['name']) ?></div>
                                <div class="text-muted small"><?= e($p['slug']) ?></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Permissions</button>
        <a href="<?= e(url('admin/roles/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>