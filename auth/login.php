<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/validation.php';

sms_session_start();
require_guest();

$errors   = [];
$oldLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    // Rate limit by client IP
    $rlKey = 'login:' . client_ip();
    rate_limit_or_429($rlKey, 12, 300);

    $login    = clean((string) post('login', ''));
    $password = (string) post('password', '');
    $remember = (bool) post('remember', false);

    $oldLogin = $login;

    if (!v_required($login))    $errors['login']    = 'Username or email is required.';
    if (!v_required($password)) $errors['password'] = 'Password is required.';

    if (!$errors) {
        $result = attempt_login($login, $password);

        if ($result['ok']) {
            rate_limit_clear($rlKey);
            login_user($result['user']);

            audit((int)$result['user']['id'], 'login', 'users', (int)$result['user']['id'], 'User signed in');

            flash_set('success', 'Welcome back, ' . $result['user']['full_name'] . '!');

            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            $role = $result['user']['primary_role'];
            redirect($redirect ?: url(role_home($role)));
        }

        $errors['form'] = $result['message'];
        audit(null, 'login_failed', 'users', null, 'Failed login for: ' . $login);
    } else {
        $errors['form'] = 'Please correct the highlighted fields.';
    }
}

$schoolName    = setting('school_name', 'SMS Secondary School');
$schoolLogo    = setting('school_logo', '');
$motto         = setting('school_motto', 'Excellence in Education');
$currentLocale = function_exists('current_locale') ? current_locale() : 'en';
$locales       = defined('SMS_SUPPORTED_LOCALES') ? SMS_SUPPORTED_LOCALES : ['en' => 'English'];
?>
<!doctype html>
<html lang="<?= e($currentLocale) ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In · <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_FULLNAME) ?> — Sign in to your account.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/login.css')) ?>" rel="stylesheet">
</head>
<body style="margin:0;">

<div class="login-shell">

    <!-- ============================================================
         Top-right: language + theme
         ============================================================ -->
    <div class="login-controls">
        <div class="dropdown">
            <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Change language" title="Language">
                <i class="bi bi-translate"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header"><i class="bi bi-translate me-1"></i> Language</h6></li>
                <?php foreach ($locales as $code => $label): ?>
                    <li>
                        <a class="dropdown-item <?= $currentLocale === $code ? 'active' : '' ?>"
                           href="?<?= http_build_query(array_merge($_GET, ['lang' => $code])) ?>">
                            <?php if ($currentLocale === $code): ?>
                                <i class="bi bi-check2"></i>
                            <?php else: ?>
                                <i class="bi bi-circle" style="font-size:.5rem;opacity:.5;"></i>
                            <?php endif; ?>
                            <?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle theme" title="Theme">
            <i class="bi bi-moon-stars-fill"></i>
        </button>
    </div>

    <!-- ============================================================
         Login card
         ============================================================ -->
    <div class="login-card">

        <!-- Header -->
        <div class="login-header">
            <div class="logo-center">
                <?php if ($schoolLogo && file_exists(path('uploads/school/' . $schoolLogo))): ?>
                    <img src="<?= e(url('uploads/school/' . $schoolLogo)) ?>" alt="Logo">
                <?php else: ?>
                    <?= e(mb_substr($schoolName, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <h1 class="welcome">Login Form</h1>
            <p class="sub"><?= e($schoolName) ?></p>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors['form'])): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= e($errors['form']) ?></span>
            </div>
        <?php endif; ?>

        <?= flash_render() ?>

        <!-- Form -->
        <form method="post" novalidate autocomplete="on">

            <?= csrf_field() ?>

            <!-- Email / Username -->
            <div class="form-group-login">
                <label for="login" class="form-label">Enter your email</label>
                <input type="text"
                       id="login"
                       name="login"
                       class="<?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                       value="<?= e($oldLogin) ?>"
                       placeholder=""
                       autocomplete="username"
                       autofocus
                       required>
                <i class="bi bi-person input-icon-right"></i>
                <?php if (isset($errors['login'])): ?>
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <?= e($errors['login']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group-login">
                <label for="password" class="form-label">Enter your password</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       placeholder=""
                       autocomplete="current-password"
                       required>
                <button type="button"
                        class="toggle-pwd"
                        data-toggle-pwd="#password"
                        aria-label="Show password">
                    <i class="bi bi-eye"></i>
                </button>
                <?php if (isset($errors['password'])): ?>
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <?= e($errors['password']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Remember + Forgot -->
            <div class="form-row-between">
                <label class="checkbox-modern">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
                <a href="<?= e(url('auth/forgot-password.php')) ?>" class="forgot-link">
                    Forgot password?
                </a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-login" id="loginBtn">
                <span class="spinner"></span>
                <span class="btn-label">Log In</span>
            </button>

            <!-- Register -->
           

            <!-- Demo credentials (dev) -->
            <?php if (SMS_ENV === 'development'): ?>
               
            <?php endif; ?>
        </form>
    </div>

    <!-- Footer -->
    <div class="login-footer-note">
        &copy; <?= date('Y') ?> <?= e($schoolName) ?> · <?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password visibility toggle
document.querySelectorAll('[data-toggle-pwd]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-toggle-pwd'));
        if (!target) return;
        var show = target.type === 'password';
        target.type = show ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
});

// Loading state on submit
document.querySelector('form')?.addEventListener('submit', function () {
    var btn = document.getElementById('loginBtn');
    if (!btn) return;
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>
</body>
</html>