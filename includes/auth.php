<?php
declare(strict_types=1);

/**
 * SMS Authentication core
 * Path: includes/auth.php
 *
 * Provides:
 *   - Session bootstrap (with idle timeout + periodic regeneration)
 *   - Session destroy
 *   - Session state helpers (current_user, is_logged_in, require_login, require_guest)
 *   - Role-based home redirect (role_home)
 *   - User loading with roles + permissions
 *   - Login attempt with brute-force throttling
 *   - Failed-attempt tracking and reset
 *   - login_user() / logout_user()
 *   - user_status_badge()
 */

// ============================================================
// Session bootstrap
// ============================================================
function sms_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Session fingerprint binding (anti-hijack)
    if (function_exists('bind_session_to_fingerprint')) {
        bind_session_to_fingerprint();
    }

    $now = time();

    // Idle timeout
    if (isset($_SESSION['last_activity'])
        && ($now - (int)$_SESSION['last_activity']) > SESSION_LIFETIME) {
        sms_session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session has expired. Please sign in again.';
    }
    $_SESSION['last_activity'] = $now;

    // Periodic session ID regeneration
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    } elseif (($now - (int)$_SESSION['created_at']) > SESSION_REGENERATE) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = $now;
    }

    // ============================================================
    // i18n BOOTSTRAP — MUST run after session is active
    // ============================================================
    if (function_exists('i18n_boot') && !defined('SMS_I18N_BOOTED')) {
        i18n_boot();
        define('SMS_I18N_BOOTED', true);
    }
}

function sms_session_destroy(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
}

// ============================================================
// Session state
// ============================================================
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
        redirect(url('auth/login.php'));
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        $role = $_SESSION['user']['primary_role'] ?? 'student';
        redirect(url(role_home($role)));
    }
}

/**
 * Return the landing page for a given role.
 */
function role_home(string $role): string
{
    return match ($role) {
        'admin'   => 'admin/dashboard.php',
        'teacher' => 'teacher/dashboard.php',
        'student' => 'student/dashboard.php',
        'parent'  => 'parent/dashboard.php',
        default   => 'auth/login.php',
    };
}

// ============================================================
// User loading
// ============================================================
/**
 * Load a user by username OR email, with roles + permissions attached.
 * Returns null when not found.
 *
 * NOTE: MySQL native prepare does NOT allow reusing a named placeholder,
 * so we use separate placeholders (:username and :email) even though the
 * same value is bound to both.
 */
function load_user_by_login(string $login): ?array
{
    $user = Database::fetch(
        "SELECT id, username, email, password_hash, full_name, phone, photo,
                status, failed_attempts, locked_until
         FROM users
         WHERE username = :username OR email = :email
         LIMIT 1",
        [
            'username' => $login,
            'email'    => $login,
        ]
    );

    if (!$user) {
        return null;
    }

    $user['roles']        = load_user_roles((int)$user['id']);
    $user['permissions']  = load_user_permissions((int)$user['id']);
    $user['primary_role'] = $user['roles'][0] ?? 'student';

    return $user;
}

function load_user_roles(int $userId): array
{
    $rows = Database::fetchAll(
        "SELECT r.slug
         FROM roles r
         JOIN user_roles ur ON ur.role_id = r.id
         WHERE ur.user_id = ?
         ORDER BY r.id ASC",
        [$userId]
    );
    return array_column($rows, 'slug');
}

function load_user_permissions(int $userId): array
{
    $rows = Database::fetchAll(
        "SELECT DISTINCT p.slug
         FROM permissions p
         JOIN role_permissions rp ON rp.permission_id = p.id
         JOIN user_roles ur       ON ur.role_id = rp.role_id
         WHERE ur.user_id = ?",
        [$userId]
    );
    return array_column($rows, 'slug');
}

// ============================================================
// Login flow
// ============================================================
/**
 * Attempt a login.
 *
 * @return array{ok:bool, message:string, user:?array}
 */
function attempt_login(string $login, string $password): array
{
    $user = load_user_by_login($login);

    // Generic message to avoid user enumeration
    $genericFail = [
        'ok'      => false,
        'message' => 'Invalid username or password.',
        'user'    => null,
    ];

    if (!$user) {
        // Mitigate user-enumeration timing attacks
        password_verify(
            $password,
            '$2y$10$usesomesillystringforsalt0000000000000000000000000000000000'
        );
        return $genericFail;
    }

    if (($user['status'] ?? '') !== 'active') {
        return [
            'ok'      => false,
            'message' => 'Your account is not active. Contact the administrator.',
            'user'    => null,
        ];
    }

    if (!empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time()) {
        $remain = (int) ceil((strtotime((string)$user['locked_until']) - time()) / 60);
        return [
            'ok'      => false,
            'message' => "Account locked. Try again in {$remain} minute(s).",
            'user'    => null,
        ];
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        record_failed_attempt((int)$user['id']);
        return $genericFail;
    }

    reset_failed_attempts((int)$user['id']);

    return [
        'ok'      => true,
        'message' => 'OK',
        'user'    => $user,
    ];
}

/**
 * Increment failed attempts and lock the account after MAX_LOGIN_ATTEMPTS.
 */
function record_failed_attempt(int $userId): void
{
    $row      = Database::fetch("SELECT failed_attempts FROM users WHERE id = ?", [$userId]);
    $attempts = (int)($row['failed_attempts'] ?? 0) + 1;

    $data = ['failed_attempts' => $attempts];

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $data['locked_until']    = date('Y-m-d H:i:s', time() + LOGIN_LOCK_MINUTES * 60);
        $data['failed_attempts'] = 0; // counter resets; lock takes over
    }

    Database::update('users', $data, 'id = :id', ['id' => $userId]);
}

/**
 * Clear lock state and record a successful login.
 */
function reset_failed_attempts(int $userId): void
{
    Database::update('users', [
        'failed_attempts' => 0,
        'locked_until'    => null,
        'last_login_at'   => date('Y-m-d H:i:s'),
        'last_login_ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
    ], 'id = :id', ['id' => $userId]);
}

/**
 * Persist the authenticated user into the session.
 */
function login_user(array $userRow): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'           => (int)$userRow['id'],
        'username'     => $userRow['username'],
        'email'        => $userRow['email'],
        'full_name'    => $userRow['full_name'],
        'photo'        => $userRow['photo'] ?? null,
        'status'       => $userRow['status'],
        'primary_role' => $userRow['primary_role'] ?? 'student',
        'roles'        => $userRow['roles'] ?? [],
        'permissions'  => $userRow['permissions'] ?? [],
    ];

    $_SESSION['created_at']    = time();
    $_SESSION['last_activity'] = time();
}

function logout_user(): void
{
    sms_session_destroy();
}

// ============================================================
// UI helpers
// ============================================================
/**
 * Render a status badge for a user record.
 */
function user_status_badge(string $status): string
{
    return match ($status) {
        'active'    => '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Active</span>',
        'inactive'  => '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Inactive</span>',
        'suspended' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Suspended</span>',
        'locked'    => '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Locked</span>',
        default     => '<span class="badge bg-light text-dark border">' . e($status) . '</span>',
    };
}