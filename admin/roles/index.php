<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_roles');

$roles = Database::fetchAll(
    "SELECT r.*,
            (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) AS users_count,
            (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perms_count
     FROM roles r
     ORDER BY r.id ASC"
);

$pageTitle       = 'Roles & Permissions';
$pageDescription = 'Define roles and assign permissions.';
$breadcrumbs     = [['label' => 'Administration'], ['label' => 'Roles']];
$extraCss        = ['assets/css/admin-settings.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/roles/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Role
        </a>
    </div>
</div>

<div class="table-wrap">
    <?php if (!$roles): ?>
        <?= ui_empty('No roles', 'Create your first role.', 'bi-shield-lock',
                     url('admin/roles/create.php'), 'New Role') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Role</th><th>Slug</th>
                        <th class="text-center">Users</th>
                        <th class="text-center">Permissions</th>
                        <th>System</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($roles as $r): ?>
                    <tr>
                        <td>
                            <span class="role-pill <?= e($r['slug']) ?>"><?= e($r['name']) ?></span>
                            <div class="text-muted small mt-1"><?= e($r['description'] ?: '') ?></div>
                        </td>
                        <td class="text-muted"><code><?= e($r['slug']) ?></code></td>
                        <td class="text-center"><?= (int)$r['users_count'] ?></td>
                        <td class="text-center"><?= (int)$r['perms_count'] ?></td>
                        <td><?= (int)$r['is_system'] ? ui_badge('Yes', 'primary') : ui_badge('No', 'secondary') ?></td>
                        <td class="text-end">
                            <?= ui_row_actions(array_filter([
                                ['label' => 'Edit Permissions', 'href' => url('admin/roles/edit.php?id=' . (int)$r['id']), 'icon' => 'bi-shield-check', 'tone' => 'outline-primary'],
                                !(int)$r['is_system'] ? ['label' => 'Delete', 'href' => url('admin/roles/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger', 'confirm' => 'Delete role "' . $r['name'] . '"?'] : null,
                            ])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>