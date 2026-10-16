<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/settings/index.php'));
}
csrf_check_or_die();

$allowed = [
    'school_name', 'school_motto', 'school_address',
    'school_phone', 'school_email', 'school_website',
    'report_header', 'report_footer',
];

Database::begin();
try {
    foreach ($allowed as $key) {
        $val = trim((string) post($key, ''));
        Database::conn()->prepare(
            "INSERT INTO school_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        )->execute([$key, $val]);
    }

    // Logo
    $removeLogo = (int) post('remove_logo', 0) === 1;

    if (!empty($_FILES['school_logo']['name'])) {
        $up = upload_image($_FILES['school_logo'], 'school', 'logo');
        if (!$up['ok']) {
            Database::rollback();
            flash_set('error', 'Logo upload failed: ' . $up['message']);
            redirect(url('admin/settings/index.php'));
        }

        // Remove old
        $old = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key='school_logo'");
        if ($old && !empty($old['setting_value'])) {
            delete_upload($old['setting_value'], 'school');
        }

        Database::conn()->prepare(
            "INSERT INTO school_settings (setting_key, setting_value)
             VALUES ('school_logo', ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        )->execute([$up['filename']]);
    } elseif ($removeLogo) {
        $old = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key='school_logo'");
        if ($old && !empty($old['setting_value'])) {
            delete_upload($old['setting_value'], 'school');
        }
        Database::conn()->prepare(
            "INSERT INTO school_settings (setting_key, setting_value) VALUES ('school_logo', '')
             ON DUPLICATE KEY UPDATE setting_value = ''"
        )->execute();
    }

    audit((int)current_user()['id'], 'settings_updated', 'school_settings', null, 'School settings updated');
    Database::commit();
    flash_set('success', 'Settings saved successfully.');
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Failed to save settings. ' . (SMS_ENV === 'development' ? $e->getMessage() : ''));
}

redirect(url('admin/settings/index.php'));