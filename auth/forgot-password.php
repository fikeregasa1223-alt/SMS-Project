<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/validation.php';

sms_session_start();
require_guest();

$sent   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $email = clean((string) post('email', ''));

    if (!v_required($email) || !v_email($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        $user = Database::fetch("SELECT id, full_name FROM users WHERE email = ? LIMIT 1", [$email]);

        if ($user) {
            $token      = bin2hex(random_bytes(32));
            $tokenHash  = hash('sha256', $token);
            $expires    = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            Database::insert('password_resets', [
                'user_id'    => $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expires,
            ]);

            $resetUrl = url('auth/reset-password.php?token=' . $token . '&uid=' . $user['id']);

            // Email architecture ready — in production use mail() / SMTP.
            // For dev, we log it.
            error_log('[SMS][password-reset] ' . $email . ' -> ' . $resetUrl);

            audit((int)$user['id'], 'password_reset_requested', 'users', (int)$user['id'], 'Reset requested');
        }

        $sent = true; // Do not reveal whether email exists
    }
}

$schoolName = setting('school_name', 'SMS');
$schoolLogo = setting('school_logo', '');
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password · <?= e(APP_NAME) ?></title>
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
            <h2>Forgot your password?</h2>
            <p>Enter your registered email and we'll send you a secure link to reset your password.</p>
        </div>
        <div class="footer-note">&copy; <?= date('Y') ?> <?= e($schoolName) ?></div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <h1>Reset Password</h1>
            <p class="sub">We'll email you a reset link.</p>

            <?php if ($sent): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>If that email exists, a reset link has been sent. Check your inbox.</span>
                </div>
                <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-left me-1"></i> Back to Sign In
                </a>
            <?php else: ?>
                <?php if (!empty($errors['email'])): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= e($errors['email']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-icon">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control"
                                   placeholder="you@example.com" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-send me-1"></i> Send Reset Link
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="<?= e(url('auth/login.php')) ?>" class="small">
                        <i class="bi bi-arrow-left"></i> Back to sign in
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>