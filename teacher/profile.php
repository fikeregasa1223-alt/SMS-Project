<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('teacher');

$user = current_user();
$teacher = teacher_for_user((int)$user['id']);
if (!$teacher) { flash_set('error', 'No teacher record linked.'); redirect(url('teacher/dashboard.php')); }

$photoUrl = teacher_photo_url($teacher);
$fullName = teacher_full_name($teacher);
$initials = mb_strtoupper(mb_substr($teacher['first_name'] ?? 'T', 0, 1) . mb_substr($teacher['last_name'] ?? '', 0, 1));

$pageTitle       = 'My Profile';
$pageDescription = 'Your teacher profile.';
$breadcrumbs     = [['label' => 'Profile']];
$extraCss        = ['assets/css/dashboards.css'];
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

<div class="profile-header">
    <div class="photo-lg">
        <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="">
        <?php else: ?><?= e($initials) ?><?php endif; ?>
    </div>
    <div class="info">
        <h1><?= e($fullName) ?></h1>
        <div class="meta">
            <span><i class="bi bi-hash"></i> <?= e($teacher['teacher_code']) ?></span>
            <span><i class="bi bi-card-text"></i> <?= e($teacher['employee_no']) ?></span>
            <?php if ($teacher['specialization']): ?>
                <span><i class="bi bi-mortarboard"></i> <?= e($teacher['specialization']) ?></span>
            <?php endif; ?>
            <span><?= employment_status_badge($teacher['employment_status']) ?></span>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h6 class="text-muted text-uppercase small mb-3">Personal</h6>
        <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($fullName) ?></span></div>
        <div class="info-row"><span class="label">Gender</span><span class="value"><?= e(ucfirst($teacher['gender'])) ?></span></div>
        <div class="info-row"><span class="label">Date of Birth</span><span class="value"><?= e(fmt_date($teacher['date_of_birth'])) ?></span></div>
        <div class="info-row"><span class="label">Phone</span><span class="value"><?= e($teacher['phone'] ?: '—') ?></span></div>
        <div class="info-row"><span class="label">Email</span><span class="value"><?= e($teacher['email'] ?: '—') ?></span></div>
    </div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h6 class="text-muted text-uppercase small mb-3">Professional</h6>
        <div class="info-row"><span class="label">Qualification</span><span class="value"><?= e($teacher['qualification'] ?: '—') ?></span></div>
        <div class="info-row"><span class="label">Specialization</span><span class="value"><?= e($teacher['specialization'] ?: '—') ?></span></div>
        <div class="info-row"><span class="label">Hire Date</span><span class="value"><?= e(fmt_date($teacher['hire_date'])) ?></span></div>
        <div class="info-row"><span class="label">Employment</span><span class="value"><?= employment_status_badge($teacher['employment_status']) ?></span></div>
    </div></div></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>