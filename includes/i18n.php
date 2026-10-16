<?php
declare(strict_types=1);

/**
 * SMS — Internationalization (i18n)
 * Path: includes/i18n.php
 */

if (defined('SMS_I18N_LOADED')) { return; }
define('SMS_I18N_LOADED', true);

/**
 * Supported locales.
 */
const SMS_SUPPORTED_LOCALES = [
    'en' => 'English',
    'am' => 'አማርኛ',
    'om' => 'Afaan Oromoo',
];

/**
 * Detect and set current locale.
 * Priority:
 *   1. ?lang=xx query param
 *   2. Session (persisted)
 *   3. User preference (DB)
 *   4. Browser Accept-Language header
 */
function i18n_boot(): void
{
    $locale = 'en';

    // 1. Explicit query param
    if (!empty($_GET['lang']) && array_key_exists($_GET['lang'], SMS_SUPPORTED_LOCALES)) {
        $locale = (string)$_GET['lang'];
        $_SESSION['locale'] = $locale;

        // Persist to DB if user is logged in
        if (!empty($_SESSION['user']['id'])) {
            try {
                Database::conn()->prepare(
                    "INSERT INTO user_preferences (user_id, pref_key, pref_value)
                     VALUES (?, 'locale', ?)
                     ON DUPLICATE KEY UPDATE pref_value = VALUES(pref_value)"
                )->execute([(int)$_SESSION['user']['id'], $locale]);
            } catch (Throwable $e) {
                error_log('[SMS][i18n] pref save failed: ' . $e->getMessage());
            }
        }
    }
    // 2. Session
    elseif (!empty($_SESSION['locale']) && array_key_exists($_SESSION['locale'], SMS_SUPPORTED_LOCALES)) {
        $locale = (string)$_SESSION['locale'];
    }
    // 3. User preference
    elseif (!empty($_SESSION['user']['id'])) {
        try {
            $row = Database::fetch(
                "SELECT pref_value FROM user_preferences
                 WHERE user_id = ? AND pref_key = 'locale'",
                [(int)$_SESSION['user']['id']]
            );
            if ($row && array_key_exists($row['pref_value'], SMS_SUPPORTED_LOCALES)) {
                $locale = (string)$row['pref_value'];
                $_SESSION['locale'] = $locale;
            }
        } catch (Throwable $e) {
            // user_preferences table may not exist yet — ignore
        }
    }
    // 4. Browser header
    elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = substr((string)$_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (array_key_exists($lang, SMS_SUPPORTED_LOCALES)) {
            $locale = $lang;
            $_SESSION['locale'] = $locale;
        }
    }

    $_SESSION['locale'] = $locale;

    // Load messages
    $file = APP_ROOT . '/locales/' . $locale . '/messages.php';
    $GLOBALS['__messages'] = is_file($file) ? (require $file) : [];
}

/**
 * Translate a key.
 * Supports placeholder replacement: __('welcome', ['name' => 'John'])
 * Falls back to English when key is missing, then to the raw key itself.
 */
function __(string $key, array $replace = []): string
{
    $messages = $GLOBALS['__messages'] ?? [];
    $value    = $messages[$key] ?? null;

    // Fallback to English if key is missing
    if ($value === null) {
        static $enCache = null;
        if ($enCache === null) {
            $enFile  = APP_ROOT . '/locales/en/messages.php';
            $enCache = is_file($enFile) ? (require $enFile) : [];
        }
        $value = $enCache[$key] ?? $key;
    }

    foreach ($replace as $k => $v) {
        $value = str_replace(':' . $k, (string)$v, (string)$value);
    }
    return (string)$value;
}

/**
 * Current locale code.
 */
function current_locale(): string
{
    return $_SESSION['locale'] ?? 'en';
}

/**
 * Locale label.
 */
function locale_label(string $code): string
{
    return SMS_SUPPORTED_LOCALES[$code] ?? $code;
}

/**
 * Save user locale preference (manual).
 */
function set_user_locale(int $userId, string $locale): void
{
    if (!array_key_exists($locale, SMS_SUPPORTED_LOCALES)) return;

    try {
        Database::conn()->prepare(
            "INSERT INTO user_preferences (user_id, pref_key, pref_value)
             VALUES (?, 'locale', ?)
             ON DUPLICATE KEY UPDATE pref_value = VALUES(pref_value)"
        )->execute([$userId, $locale]);
    } catch (Throwable $e) {
        error_log('[SMS][i18n] set_user_locale failed: ' . $e->getMessage());
    }

    $_SESSION['locale'] = $locale;
}

/**
 * Language switcher UI (reusable).
 *
 * @param string $style  'dropdown' (default) or 'inline'
 * @return string HTML
 */
function render_language_switcher(string $style = 'dropdown'): string
{
    $current = current_locale();

    // ------------------------------------------------------------
    // Inline style — buttons for settings page
    // ------------------------------------------------------------
    if ($style === 'inline') {
        $html = '<div class="language-switcher-inline d-flex gap-1 flex-wrap">';
        foreach (SMS_SUPPORTED_LOCALES as $code => $label) {
            $active = $current === $code ? 'active' : '';
            $url    = '?' . http_build_query(array_merge($_GET, ['lang' => $code]));
            $btnCls = $active ? 'btn-primary' : 'btn-outline-primary';
            $html  .= '<a href="' . e($url) . '" class="btn btn-sm ' . $btnCls . '">'
                    . e($label) . '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    // ------------------------------------------------------------
    // Default: dropdown (topbar)
    // ------------------------------------------------------------
    $html  = '<div class="dropdown">';
    $html .= '<button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Language" title="Language">';
    $html .= '<i class="bi bi-translate"></i>';
    $html .= '</button>';
    $html .= '<ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;">';
    $html .= '<li><h6 class="dropdown-header"><i class="bi bi-translate me-1"></i> Language</h6></li>';

    foreach (SMS_SUPPORTED_LOCALES as $code => $label) {
        $isActive = $current === $code;
        $url      = '?' . http_build_query(array_merge($_GET, ['lang' => $code]));
        $check    = $isActive
            ? '<i class="bi bi-check2 me-1 text-primary"></i>'
            : '<i class="bi bi-circle me-1 text-muted" style="font-size:.5rem;vertical-align:middle;"></i>';
        $active   = $isActive ? 'active' : '';

        $html .= '<li><a class="dropdown-item ' . $active . '" href="' . e($url) . '">'
               . $check . e($label) . '</a></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';
    return $html;
}