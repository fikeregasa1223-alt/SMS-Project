<?php
declare(strict_types=1);

/**
 * Read sort/direction from query string with whitelist.
 */
function table_sort(string $default = 'id', string $defaultDir = 'desc', array $allowed = []): array
{
    $sort = (string) ($_GET['sort'] ?? $default);
    $dir  = strtolower((string) ($_GET['dir'] ?? $defaultDir));

    if ($allowed && !in_array($sort, $allowed, true)) {
        $sort = $default;
    }
    if (!in_array($dir, ['asc','desc'], true)) {
        $dir = $defaultDir;
    }
    return ['sort' => $sort, 'dir' => $dir];
}

/**
 * Sort link for a table header.
 */
function table_sort_link(string $label, string $column, string $currentSort, string $currentDir, array $queryArgs = []): string
{
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $args    = array_merge($queryArgs, ['sort' => $column, 'dir' => $nextDir]);
    $url     = '?' . http_build_query($args);

    $icon = '';
    if ($currentSort === $column) {
        $icon = $currentDir === 'asc'
            ? ' <i class="bi bi-caret-up-fill"></i>'
            : ' <i class="bi bi-caret-down-fill"></i>';
    }
    return '<a href="' . e($url) . '" class="text-decoration-none text-reset">'
         . e($label) . $icon . '</a>';
}

/**
 * Read simple filters from GET.
 */
function table_filter(string $name, string $default = ''): string
{
    return trim((string) ($_GET[$name] ?? $default));
}