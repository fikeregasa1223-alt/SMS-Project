<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_users');

$id  = (int) ($_GET['id'] ?? 0);
$u   = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
if (!$u) { flash_set('error', 'User not found.'); redirect(url('admin/users/index.php')); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $pwd     = (string) post('password', '');
    $confirm = (string) post('confirm_password', '');

    [$ok, $msg] = v_password_strength($pwd);
    if (!$ok)             $errors['password'] = $msg;
    if ($pwd !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if (!$errors) {
        Database::update('users', [
            'password_hash'   => password_hash($pwd, PASSWORD_DEFAULT),
            'must_change_pwd' => 1,
            'failed_attempts' => 0,
            'locked_until'    => null,
        ], 'id = :id', ['id' => $id]);

        audit((int)current_user()['id'], 'user_password_reset', 'users', $id, 'Password reset by admin for ' . $u['username']);
        flash_set('success', 'Password reset. The user must change it on next login.');
        redirect(url('admin/users/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Reset Password — ' . $u['username'];
$pageDescription = 'Set a new password for this user.';
$breadcrumbs     = [['label' => 'Users', 'url' => 'admin/users/index.php'], ['label' => 'Reset Password']];
$extraCss        = ['assets/css/admin-settings.css'];
$extraJs         = ['assets/js/admin-settings.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           data-strength-input="#pwdMeter" required>
                    <div class="pwd-meter" id="pwdMeter"><span></span><span></span><span></span><span></span></div>
                    <div class="form-text">Min 8 chars, uppercase, lowercase, and a number.</div>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password"
                           class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
                    <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Reset Password</button>
                    <a href="<?= e(url('admin/users/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
                </div>
            </form>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">User</h6>
            <div class="info-row"><span class="label">Username</span><span class="value"><?= e($u['username']) ?></span></div>
            <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($u['full_name']) ?></span></div>
            <div class="info-row"><span class="label">Email</span><span class="value"><?= e($u['email']) ?></span></div>
            <div class="info-row"><span class="label">Status</span><span class="value"><?= user_status_badge($u['status']) ?></span></div>
            <div class="alert alert-warning small mt-3 mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                The user will be required to change their password on next login.
            </div>
        </div></div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>