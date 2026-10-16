<?php
declare(strict_types=1);

function user_roles(): array
{
    return $_SESSION['user']['roles'] ?? [];
}

function user_permissions(): array
{
    return $_SESSION['user']['permissions'] ?? [];
}

function user_has_role(string ...$slugs): bool
{
    return (bool) array_intersect($slugs, user_roles());
}

function user_can(string $permission): bool
{
    if (user_has_role('admin')) return true;
    return in_array($permission, user_permissions(), true);
}

function require_role(string ...$slugs): void
{
    if (!user_has_role(...$slugs)) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'You do not have permission to access that area.';
        redirect(url('index.php'));
    }
}

function require_permission(string $permission): void
{
    if (!user_can($permission)) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'You do not have permission to perform this action.';
        redirect(url('index.php'));
    }
}