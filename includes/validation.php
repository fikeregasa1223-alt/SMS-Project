<?php
declare(strict_types=1);

/**
 * Input validation helpers
 */

function v_required($value): bool
{
    return !is_null($value) && trim((string)$value) !== '';
}

function v_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function v_phone(string $phone): bool
{
    return preg_match('/^[0-9 +\-()]{7,30}$/', $phone) === 1;
}

function v_date(string $date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function v_int($value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

function v_min_len(string $value, int $min): bool
{
    return mb_strlen($value) >= $min;
}

function v_max_len(string $value, int $max): bool
{
    return mb_strlen($value) <= $max;
}

/**
 * Password strength: min 8, one uppercase, one lowercase, one digit.
 * Returns [bool $ok, string $message]
 */
function v_password_strength(string $password): array
{
    if (mb_strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return [false, 'Password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return [false, 'Password must contain at least one lowercase letter.'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return [false, 'Password must contain at least one number.'];
    }
    return [true, 'OK'];
}

/**
 * Username: letters, numbers, dot, underscore, dash; 3-60 chars.
 */
function v_username(string $username): bool
{
    return preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username) === 1;
}

function v_in_array($value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

/**
 * Return first error from a set of [field => [value, validators...]] arrays.
 * Or null if all pass.
 */
function v_first_error(array $checks): ?string
{
    foreach ($checks as $errorMessage) {
        if ($errorMessage !== null) {
            return $errorMessage;
        }
    }
    return null;
}