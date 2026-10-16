<?php
declare(strict_types=1);

/**
 * Render a Bootstrap pagination control.
 *
 * @param int   $total      Total rows
 * @param int   $perPage    Rows per page
 * @param int   $current    Current page (1-based)
 * @param array $queryArgs  Extra query string args to preserve
 */
function render_pagination(int $total, int $perPage, int $current, array $queryArgs = []): string
{
    $pages = (int) ceil($total / max(1, $perPage));
    if ($pages <= 1) return '';

    $buildUrl = function (int $page) use ($queryArgs): string {
        $q = array_merge($queryArgs, ['page' => $page]);
        return '?' . http_build_query($q);
    };

    $html  = '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">';

    // Prev
    $prevDisabled = $current <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $prevDisabled . '">'
           . '<a class="page-link" href="' . e($buildUrl(max(1, $current - 1))) . '">'
           . '<i class="bi bi-chevron-left"></i></a></li>';

    // Window of pages
    $start = max(1, $current - 2);
    $end   = min($pages, $current + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl(1)) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current ? ' active' : '';
        $html  .= '<li class="page-item' . $active . '">'
                . '<a class="page-link" href="' . e($buildUrl($i)) . '">' . $i . '</a></li>';
    }

    if ($end < $pages) {
        if ($end < $pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl($pages)) . '">' . $pages . '</a></li>';
    }

    // Next
    $nextDisabled = $current >= $pages ? ' disabled' : '';
    $html .= '<li class="page-item' . $nextDisabled . '">'
           . '<a class="page-link" href="' . e($buildUrl(min($pages, $current + 1))) . '">'
           . '<i class="bi bi-chevron-right"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Compute LIMIT/OFFSET from a page number.
 */
function paginate(int $total, int $perPage, int $current): array
{
    $perPage = max(1, $perPage);
    $pages   = max(1, (int) ceil($total / $perPage));
    $current = max(1, min($pages, $current));
    return [
        'pages'  => $pages,
        'page'   => $current,
        'limit'  => $perPage,
        'offset' => ($current - 1) * $perPage,
        'total'  => $total,
    ];
}