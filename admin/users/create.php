<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_users');

$errors = [];
$data = [
    'username'  => '',
    'email'     => '',
    'full_name' => '',
    'phone'     => '',
    'status'    => 'active',
    'role_ids'  => [],
];

$roles = Database::fetchAll("SELECT id, name, slug FROM roles ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['username']  = clean((string) post('username', ''));
    $data['email']     = clean((string) post('email', ''));
    $data['full_name'] = clean((string) post('full_name', ''));
    $data['phone']     = clean((string) post('phone', ''));
    $data['status']    = (string) post('status', 'active');
    $data['role_ids']  = array_map('intval', (array) post('role_ids', []));

    $password = (string) post('password', '');
    $confirm  = (string) post('confirm_password', '');

    if (!v_required($data['username']) || !v_username($data['username'])) $errors['username'] = 'Valid username required (3-60, letters/numbers/._-).';
    if (!v_required($data['email']) || !v_email($data['email']))          $errors['email']    = 'Valid email required.';
    if (!v_required($data['full_name']))                                   $errors['full_name']= 'Full name required.';
    if (!v_in_array($data['status'], ['active','inactive','suspended']))   $errors['status']   = 'Invalid status.';
    if (!$data['role_ids'])                                                $errors['role_ids'] = 'Select at least one role.';

    [$pwdOk, $pwdMsg] = v_password_strength($password);
    if (!$pwdOk)                 $errors['password'] = $pwdMsg;
    if ($password !== $confirm)  $errors['confirm_password'] = 'Passwords do not match.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$data['username'], $data['email']]);
        if ($dup) $errors['username'] = 'Username or email already exists.';
    }

    if (!$errors) {
        Database::begin();
        try {
            $userId = Database::insert('users', [
                'username'      => $data['username'],
                'email'         => $data['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name'     => $data['full_name'],
                'phone'         => $data['phone'] ?: null,
                'status'        => $data['status'],
                'must_change_pwd'=> 1,
            ]);

            foreach ($data['role_ids'] as $rid) {
                Database::insert('user_roles', ['user_id' => $userId, 'role_id' => (int)$rid]);
            }

            audit((int)current_user()['id'], 'user_created', 'users', $userId, 'User created: ' . $data['username']);
            Database::commit();
            flash_set('success', 'User created successfully.');
            redirect(url('admin/users/index.php'));
        } catch (Throwable $e) {
            Database::rollback();
            $errors['form'] = 'Failed to create user. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New User';
$pageDescription = 'Create a new system user.';
$breadcrumbs     = [['label' => 'Users', 'url' => 'admin/users/index.php'], ['label' => 'New']];
$extraCss        = ['assets/css/admin-settings.css'];
$extraJs         = ['assets/js/admin-settings.js'];
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
            <div class="card">
                <div class="card-header"><i class="bi bi-person me-2"></i>Account Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['username']) ?>" required>
                            <?php if (isset($errors['username'])): ?><div class="invalid-feedback d-block"><?= e($errors['username']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['full_name']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password"
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   data-strength-input="#pwdMeter" required>
                            <div class="pwd-meter" id="pwdMeter"><span></span><span></span><span></span><span></span></div>
                            <div class="form-text">Min 8 chars with uppercase, lowercase and a number.</div>
                            <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password"
                                   class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active','inactive','suspended'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Roles</div>
                <div class="card-body">
                    <?php if (isset($errors['role_ids'])): ?>
                        <div class="alert alert-warning py-2 small"><?= e($errors['role_ids']) ?></div>
                    <?php endif; ?>
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
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i> User must change password on first login.
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2 me-1"></i> Create User</button>
                <a href="<?= e(url('admin/users/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>