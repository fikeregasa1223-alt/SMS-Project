<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/validation.php';

sms_session_start();
require_login();

$user   = current_user();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    $current = (string) post('current_password', '');
    $new     = (string) post('new_password', '');
    $confirm = (string) post('confirm_password', '');

    if (!v_required($current)) {
        $errors['current_password'] = 'Current password is required.';
    }
    [$pwdOk, $pwdMsg] = v_password_strength($new);
    if (!$pwdOk) $errors['new_password'] = $pwdMsg;

    if ($new !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    if ($current && $new && $current === $new) {
        $errors['new_password'] = 'New password must differ from current password.';
    }

    if (!$errors) {
        $row = Database::fetch("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            Database::update('users', ['password_hash' => $hash], 'id = :id', ['id' => $user['id']]);
            audit((int)$user['id'], 'password_changed', 'users', (int)$user['id'], 'Password changed by user');
            flash_set('success', 'Your password has been updated.');
            redirect(url('auth/change-password.php'));
        }
    }

    if ($errors) {
        $errors['form'] = 'Please correct the highlighted fields.';
    }
}

$pageTitle       = 'Change Password';
$pageDescription = 'Update your account password securely.';
$breadcrumbs     = [['label' => 'Change Password']];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="page-desc"><?= e($pageDescription) ?></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= e($errors['form']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate data-validate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="current_password"
                                   class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                                   autocomplete="current-password" required>
                            <button type="button" class="toggle-pwd" data-toggle-pwd="[name=current_password]">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-key"></i>
                            <input type="password" name="new_password"
                                   class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                                   data-pwd-meter="#pwd-meter-change"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-pwd" data-toggle-pwd="[name=new_password]">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="pwd-meter" id="pwd-meter-change">
                            <span></span><span></span><span></span><span></span>
                        </div>
                        <div class="form-text">Minimum 8 characters with uppercase, lowercase and a number.</div>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['new_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-key-fill"></i>
                            <input type="password" name="confirm_password"
                                   class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-pwd" data-toggle-pwd="[name=confirm_password]">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2 me-1"></i> Update Password
                        </button>
                        <a href="<?= e(url(role_home($user['primary_role']))) ?>" class="btn btn-outline-primary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-shield-check me-2"></i>Password Security Tips</div>
            <div class="card-body small text-soft">
                <ul class="mb-0 ps-3">
                    <li>Use at least 8 characters.</li>
                    <li>Mix uppercase, lowercase, numbers, symbols.</li>
                    <li>Avoid using your name, birthday or common words.</li>
                    <li>Don't reuse passwords from other services.</li>
                    <li>Consider using a password manager.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>