<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

sms_session_start();

header('Content-Type: text/plain; charset=utf-8');

echo "=== i18n Debug ===\n\n";

echo "SMS_I18N_LOADED: " . (defined('SMS_I18N_LOADED') ? 'YES' : 'NO') . "\n";
echo "i18n_boot exists: " . (function_exists('i18n_boot') ? 'YES' : 'NO') . "\n";
echo "__ exists: " . (function_exists('__') ? 'YES' : 'NO') . "\n";
echo "current_locale() exists: " . (function_exists('current_locale') ? 'YES' : 'NO') . "\n\n";

echo "URL lang param: " . ($_GET['lang'] ?? '(none)') . "\n";
echo "Session locale: " . ($_SESSION['locale'] ?? '(none)') . "\n";
echo "current_locale(): " . (function_exists('current_locale') ? current_locale() : 'N/A') . "\n\n";

echo "Messages loaded: " . (isset($GLOBALS['__messages']) ? count($GLOBALS['__messages']) . ' keys' : 'NOT LOADED') . "\n\n";

if (function_exists('__')) {
    echo "Test translations:\n";
    echo "  __('nav.dashboard') = " . __('nav.dashboard') . "\n";
    echo "  __('common.save')   = " . __('common.save') . "\n";
    echo "  __('common.logout') = " . __('common.logout') . "\n";
} else {
    echo "⚠️  __() function NOT DEFINED — i18n.php not loaded!\n";
}