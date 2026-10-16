<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('parent');

$user = current_user();
$parent = parent_for_user((int)$user['id']);

$pageTitle       = 'My Profile';
$pageDescription = 'Your parent account details.';
$breadcrumbs     = [['label' => 'Profile']];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('auth/change-password.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-key me-1"></i> Change Password
        </a>
    </div>
</div>

<?php if (!$parent): ?>
    <div class="table-wrap"><?= ui_empty('No parent record linked', 'Contact the administrator.', 'bi-person') ?></div>
<?php else: ?>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Personal</h6>
            <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($parent['full_name']) ?></span></div>
            <div class="info-row"><span class="label">Parent Code</span><span class="value"><?= e($parent['parent_code']) ?></span></div>
            <div class="info-row"><span class="label">Phone</span><span class="value"><?= e($parent['phone']) ?></span></div>
            <div class="info-row"><span class="label">Email</span><span class="value"><?= e($parent['email'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Occupation</span><span class="value"><?= e($parent['occupation'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Address</span><span class="value"><?= e($parent['address'] ?: '—') ?></span></div>
        </div></div></div>

        <div class="col-lg-6"><div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Children</h6>
            <?php $children = parent_children((int)$parent['id']); ?>
            <?php if (!$children): ?>
                <p class="text-muted small mb-0">No children linked to this account.</p>
            <?php else: ?>
                <?php foreach ($children as $c): ?>
                    <div class="info-row">
                        <span class="label"><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?></span>
                        <span class="value"><?= e($c['student_code']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div></div></div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>