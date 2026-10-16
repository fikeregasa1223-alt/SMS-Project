<?php
declare(strict_types=1);

/**
 * SMS UI helpers — reusable presentational components
 * Path: includes/ui.php
 */

// ============================================================
// GUARD — protect against double-loading
// ============================================================
if (defined('SMS_UI_LOADED')) {
    return;
}
define('SMS_UI_LOADED', true);

// ============================================================
// Helpers
// ============================================================

if (!function_exists('ui_badge')) {
    function ui_badge(string $label, string $tone = 'secondary'): string
    {
        $tone = in_array($tone, ['primary','secondary','success','warning','danger','info','light','dark'], true)
            ? $tone : 'secondary';
        return '<span class="badge bg-' . $tone . '-subtle text-' . $tone . '-emphasis border border-' . $tone . '-subtle">'
             . e($label) . '</span>';
    }
}

if (!function_exists('ui_bool_badge')) {
    function ui_bool_badge(bool $flag, string $trueLabel = 'Yes', string $falseLabel = 'No'): string
    {
        return $flag
            ? ui_badge($trueLabel, 'success')
            : ui_badge($falseLabel, 'secondary');
    }
}

if (!function_exists('ui_avatar')) {
    function ui_avatar(?string $photoUrl, string $name, string $size = 'md'): string
    {
        $sizeClass = 'avatar-' . $size;
        $initials  = '';
        foreach (preg_split('/\s+/', trim($name)) ?: [] as $p) {
            $initials .= mb_strtoupper(mb_substr($p, 0, 1));
            if (mb_strlen($initials) >= 2) break;
        }
        $initials = $initials !== '' ? $initials : '?';

        if ($photoUrl) {
            return '<span class="avatar ' . e($sizeClass) . '"><img src="' . e($photoUrl) . '" alt=""></span>';
        }
        return '<span class="avatar ' . e($sizeClass) . '">' . e($initials) . '</span>';
    }
}

if (!function_exists('ui_row_actions')) {
    function ui_row_actions(array $actions): string
    {
        $html = '<div class="d-inline-flex gap-1">';
        foreach ($actions as $a) {
            $tone    = $a['tone']    ?? 'ghost';
            $icon    = $a['icon']    ?? 'bi-three-dots';
            $label   = $a['label']   ?? '';
            $confirm = $a['confirm'] ?? '';
            $data    = $a['data']    ?? [];

            $attrs = '';
            foreach ($data as $k => $v) {
                $attrs .= ' data-' . e($k) . '="' . e((string)$v) . '"';
            }
            if ($confirm !== '') {
                $attrs .= ' data-confirm="' . e($confirm) . '"';
            }

            if (!empty($a['href'])) {
                $html .= '<a class="btn btn-sm btn-' . e($tone) . '" href="' . e($a['href']) . '"'
                       . $attrs . ' title="' . e($label) . '"><i class="bi ' . e($icon) . '"></i></a>';
            } elseif (!empty($a['action'])) {
                $html .= '<button type="button" class="btn btn-sm btn-' . e($tone) . '"'
                       . ' data-action="' . e($a['action']) . '"' . $attrs
                       . ' title="' . e($label) . '"><i class="bi ' . e($icon) . '"></i></button>';
            }
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('ui_field')) {
    function ui_field(string $name, string $label, string $inputHtml, ?string $error = null, ?string $help = null, bool $required = false): string
    {
        $req   = $required ? ' <span class="text-danger">*</span>' : '';
        $inv   = $error ? ' is-invalid' : '';
        $html  = '<div class="mb-3">';
        $html .= '<label class="form-label" for="' . e($name) . '">' . e($label) . $req . '</label>';
        $html .= $inputHtml;
        if ($help) {
            $html .= '<div class="form-text">' . e($help) . '</div>';
        }
        if ($error) {
            $html .= '<div class="invalid-feedback d-block">' . e($error) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('ui_input')) {
    function ui_input(string $name, ?string $value, string $type = 'text', array $extra = []): string
    {
        $attrs = '';
        foreach ($extra as $k => $v) {
            $attrs .= ' ' . e($k) . '="' . e((string)$v) . '"';
        }
        return '<input type="' . e($type) . '" id="' . e($name) . '" name="' . e($name) . '"'
             . ' class="form-control" value="' . e((string)$value) . '"' . $attrs . '>';
    }
}

if (!function_exists('ui_empty')) {
    function ui_empty(string $title, string $message, string $icon = 'bi-inbox', ?string $ctaHref = null, ?string $ctaLabel = null): string
    {
        $html  = '<div class="empty-state text-center py-5">';
        $html .= '<div class="empty-icon"><i class="bi ' . e($icon) . '"></i></div>';
        $html .= '<h5 class="mt-3 mb-1">' . e($title) . '</h5>';
        $html .= '<p class="text-muted small mb-3">' . e($message) . '</p>';
        if ($ctaHref && $ctaLabel) {
            $html .= '<a href="' . e($ctaHref) . '" class="btn btn-primary btn-sm">'
                   . '<i class="bi bi-plus-lg me-1"></i>' . e($ctaLabel) . '</a>';
        }
        $html .= '</div>';
        return $html;
    }
}