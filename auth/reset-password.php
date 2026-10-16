<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/validation.php';

sms_session_start();
require_guest();

$token  = (string) ($_GET['token'] ?? post('token', ''));
$uid    = (int)    ($_GET['uid']   ?? post('uid', 0));

$errors   = [];
$done     = false;
$tokenRow = null;

if ($token && $uid) {
    $tokenHash = hash('sha256', $token);
    $tokenRow  = Database::fetch(
        "SELECT * FROM password_resets
         WHERE user_id = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1",
        [$uid, $tokenHash]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    $new     = (string) post('new_password', '');
    $confirm = (string) post('confirm_password', '');

    if (!$tokenRow) {
        $errors['form'] = 'This reset link is invalid or has expired.';
    } else {
        [$pwdOk, $pwdMsg] = v_password_strength($new);
        if (!$pwdOk) $errors['new_password'] = $pwdMsg;
        if ($new !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

        if (!$errors) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            Database::update('users', ['password_hash' => $hash], 'id = :id', ['id' => $uid]);
            Database::update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $tokenRow['id']]);

            audit($uid, 'password_reset_completed', 'users', $uid, 'Password reset completed');
            $done = true;
        }
    }
}

$schoolName = setting('school_name', 'SMS');
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password · <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-shell">
    <div class="login-left">
        <div class="brand-lg">
            <div class="logo"><?= e(mb_substr($schoolName, 0, 1)) ?></div>
            <div>
                <div class="title"><?= e($schoolName) ?></div>
                <div class="subtitle"><?= e(APP_FULLNAME) ?></div>
            </div>
        </div>
        <div class="hero">
            <h2>Create a new password</h2>
            <p>Choose a strong password to secure your account.</p>
        </div>
        <div class="footer-note">&copy; <?= date('Y') ?> <?= e($schoolName) ?></div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <?php if ($done): ?>
                <h1>Password updated</h1>
                <p class="sub">You can now sign in with your new password.</p>
                <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </a>
            <?php elseif (!$tokenRow): ?>
                <h1>Invalid link</h1>
                <p class="sub">This password reset link is invalid or has expired.</p>
                <a href="<?= e(url('auth/forgot-password.php')) ?>" class="btn btn-primary w-100">
                    Request a new link
                </a>
            <?php else: ?>
                <h1>New password</h1>
                <p class="sub">Set your new password below.</p>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" novalidate data-validate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="uid" value="<?= (int)$uid ?>">

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-icon">
                            <i class="bi bi-key"></i>
                            <input type="password" name="new_password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                                   data-pwd-meter="#pwd-meter-reset" required>
                            <button type="button" class="toggle-pwd" data-toggle-pwd="[name=new_password]"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="pwd-meter" id="pwd-meter-reset"><span></span><span></span><span></span><span></span></div>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['new_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-icon">
                            <i class="bi bi-key-fill"></i>
                            <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
                            <button type="button" class="toggle-pwd" data-toggle-pwd="[name=confirm_password]"><i class="bi bi-eye"></i></button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-check2 me-1"></i> Update Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>