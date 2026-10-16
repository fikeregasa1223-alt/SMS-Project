<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_roles');

$errors = [];
$data = ['name' => '', 'slug' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['name']        = clean((string) post('name', ''));
    $data['slug']        = clean((string) post('slug', ''));
    $data['description'] = clean((string) post('description', ''));

    if (!v_required($data['name'])) $errors['name'] = 'Name required.';
    if (!v_required($data['slug']) || !preg_match('/^[a-z0-9_-]+$/', $data['slug'])) {
        $errors['slug'] = 'Slug must be lowercase letters, numbers, dash, underscore.';
    }

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM roles WHERE slug = ?", [$data['slug']]);
        if ($dup) $errors['slug'] = 'Slug already in use.';
    }

    if (!$errors) {
        $rid = Database::insert('roles', [
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?: null,
            'is_system'   => 0,
        ]);
        audit((int)current_user()['id'], 'role_created', 'roles', $rid, 'Role created: ' . $data['slug']);
        flash_set('success', 'Role created. Now assign permissions.');
        redirect(url('admin/roles/edit.php?id=' . $rid));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Role';
$pageDescription = 'Create a new role.';
$breadcrumbs     = [['label' => 'Roles', 'url' => 'admin/roles/index.php'], ['label' => 'New']];
$extraCss        = ['assets/css/admin-settings.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-6"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($data['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug <span class="text-danger">*</span></label>
            <input type="text" name="slug" class="form-control" value="<?= e($data['slug']) ?>"
                   placeholder="e.g. librarian" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" value="<?= e($data['description']) ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/roles/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>