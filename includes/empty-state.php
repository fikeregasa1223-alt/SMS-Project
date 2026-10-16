<?php
declare(strict_types=1);

// Alias to ui_empty() for legacy use.
require_once __DIR__ . '/ui.php';

if (!function_exists('empty_state')) {
    function empty_state(string $title, string $message, string $icon = 'bi-inbox'): string
    {
        return ui_empty($title, $message, $icon);
    }
}