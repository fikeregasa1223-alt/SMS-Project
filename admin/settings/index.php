<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_settings');

$keys = [
    'general' => [
        'school_name', 'school_motto', 'school_address',
    ],
    'contact' => [
        'school_phone', 'school_email', 'school_website',
    ],
    'branding' => [
        'school_logo',
    ],
    'reports' => [
        'report_header', 'report_footer',
    ],
];

$allKeys = array_merge(...array_values($keys));
$placeholders = implode(',', array_fill(0, count($allKeys), '?'));
$rows = Database::fetchAll(
    "SELECT setting_key, setting_value FROM school_settings WHERE setting_key IN ($placeholders)",
    $allKeys
);
$settings = [];
foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

function s(array $s, string $k, string $d = ''): string
{
    return $s[$k] ?? $d;
}

$pageTitle       = 'School Settings';
$pageDescription = 'Configure school identity, contact, branding and reports.';
$breadcrumbs     = [['label' => 'Administration'], ['label' => 'Settings']];
$extraCss        = ['assets/css/admin-settings.css'];
$extraJs         = ['assets/js/admin-settings.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form method="post" action="<?= e(url('admin/settings/save.php')) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <!-- Navigation -->
        <div class="col-lg-3">
            <div class="settings-nav">
                <a class="link active" href="#section-general"><i class="bi bi-house"></i> General</a>
                <a class="link" href="#section-contact"><i class="bi bi-telephone"></i> Contact</a>
                <a class="link" href="#section-branding"><i class="bi bi-image"></i> Branding</a>
                <a class="link" href="#section-reports"><i class="bi bi-file-earmark-text"></i> Reports</a>
            </div>
        </div>

        <!-- Content -->
        <div class="col-lg-9">

            <!-- General -->
            <div class="card mb-3" id="section-general">
                <div class="card-header"><i class="bi bi-house me-2"></i>General</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">School Name <span class="text-danger">*</span></label>
                            <input type="text" name="school_name" class="form-control"
                                   value="<?= e(s($settings,'school_name','')) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Motto</label>
                            <input type="text" name="school_motto" class="form-control"
                                   value="<?= e(s($settings,'school_motto','')) ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="school_address" rows="2" class="form-control"><?= e(s($settings,'school_address','')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card mb-3" id="section-contact">
                <div class="card-header"><i class="bi bi-telephone me-2"></i>Contact</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="school_phone" class="form-control"
                                   value="<?= e(s($settings,'school_phone','')) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="school_email" class="form-control"
                                   value="<?= e(s($settings,'school_email','')) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Website</label>
                            <input type="text" name="school_website" class="form-control"
                                   value="<?= e(s($settings,'school_website','')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branding -->
            <div class="card mb-3" id="section-branding">
                <div class="card-header"><i class="bi bi-image me-2"></i>Branding</div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3 text-center">
                            <?php $logo = s($settings,'school_logo',''); ?>
                            <div class="logo-preview mx-auto" data-logo-input="#logoInput">
                                <?php if ($logo && file_exists(path('uploads/school/' . $logo))): ?>
                                    <img src="<?= e(url('uploads/school/' . $logo)) ?>" alt="Logo">
                                    <div class="hint">Change</div>
                                <?php else: ?>
                                    <i class="bi bi-image placeholder"></i>
                                    <div class="hint">Upload logo</div>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="school_logo" id="logoInput"
                                   accept="image/jpeg,image/png,image/webp" hidden>
                            <div class="form-text mt-2">JPG, PNG, WEBP · max 2 MB</div>
                        </div>
                        <div class="col-md-9">
                            <p class="text-muted mb-2">
                                The school logo appears in the sidebar, login page, reports, and print pages.
                            </p>
                            <?php if ($logo): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                                    <label class="form-check-label text-danger small" for="remove_logo">Remove current logo</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="card mb-3" id="section-reports">
                <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Reports</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Report Header</label>
                        <input type="text" name="report_header" class="form-control"
                               value="<?= e(s($settings,'report_header','')) ?>">
                        <div class="form-text">Appears at the top of printable reports.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Report Footer</label>
                        <input type="text" name="report_footer" class="form-control"
                               value="<?= e(s($settings,'report_footer','')) ?>">
                        <div class="form-text">Appears at the bottom of printable reports.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i> Save Settings
                </button>
                <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>