<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_users');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'User not found.'); redirect(url('admin/users/index.php')); }

$errors = [];
$data   = $row;
$data['role_ids'] = array_map(fn($r) => (int)$r['role_id'], Database::fetchAll("SELECT role_id FROM user_roles WHERE user_id = ?", [$id]));

$roles = Database::fetchAll("SELECT id, name, slug FROM roles ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['username']  = clean((string) post('username', ''));
    $data['email']     = clean((string) post('email', ''));
    $data['full_name'] = clean((string) post('full_name', ''));
    $data['phone']     = clean((string) post('phone', ''));
    $data['status']    = (string) post('status', 'active');
    $data['role_ids']  = array_map('intval', (array) post('role_ids', []));

    if (!v_required($data['username']) || !v_username($data['username'])) $errors['username'] = 'Valid username required.';
    if (!v_required($data['email']) || !v_email($data['email']))          $errors['email']    = 'Valid email required.';
    if (!v_required($data['full_name']))                                   $errors['full_name']= 'Full name required.';
    if (!v_in_array($data['status'], ['active','inactive','suspended']))   $errors['status']   = 'Invalid status.';
    if (!$data['role_ids'])                                                $errors['role_ids'] = 'Select at least one role.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?",
                               [$data['username'], $data['email'], $id]);
        if ($dup) $errors['username'] = 'Username or email already in use.';
    }

    if (!$errors) {
        Database::begin();
        try {
            Database::update('users', [
                'username'  => $data['username'],
                'email'     => $data['email'],
                'full_name' => $data['full_name'],
                'phone'     => $data['phone'] ?: null,
                'status'    => $data['status'],
            ], 'id = :id', ['id' => $id]);

            Database::conn()->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$id]);
            foreach ($data['role_ids'] as $rid) {
                Database::insert('user_roles', ['user_id' => $id, 'role_id' => (int)$rid]);
            }

            audit((int)current_user()['id'], 'user_updated', 'users', $id, 'User updated: ' . $data['username']);
            Database::commit();
            flash_set('success', 'User updated.');
            redirect(url('admin/users/index.php'));
        } catch (Throwable $e) {
            Database::rollback();
            $errors['form'] = 'Failed to update. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit User';
$pageDescription = 'Update user account.';
$breadcrumbs     = [['label' => 'Users', 'url' => 'admin/users/index.php'], ['label' => 'Edit']];
$extraCss        = ['assets/css/admin-settings.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= e($data['username']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>" required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= e($data['full_name']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active','inactive','suspended','locked'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i> Use the Reset Password page to change this user's password.
                </div>
            </div></div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Roles</h6>
                <?php foreach ($roles as $r): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="role_ids[]" value="<?= (int)$r['id'] ?>"
                               id="role-<?= (int)$r['id'] ?>"
                               <?= in_array((int)$r['id'], $data['role_ids'], true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role-<?= (int)$r['id'] ?>">
                            <span class="role-pill <?= e($r['slug']) ?>"><?= e($r['name']) ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div></div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2 me-1"></i> Save</button>
                <a href="<?= e(url('admin/users/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>