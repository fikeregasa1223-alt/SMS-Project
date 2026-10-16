<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('student');

$user = current_user();
$student = student_for_user((int)$user['id']);
if (!$student) {
    flash_set('error', 'Your account is not linked to a student record.');
    redirect(url('student/dashboard.php'));
}

$enrollment = student_current_enrollment((int)$student['id']);
$photoUrl   = student_photo_url($student);
$fullName   = student_full_name($student);
$initials   = mb_strtoupper(mb_substr($student['first_name'] ?? 'S', 0, 1) . mb_substr($student['last_name'] ?? '', 0, 1));

$pageTitle       = 'My Profile';
$pageDescription = 'Your personal information.';
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
            <span><i class="bi bi-hash"></i> <?= e($student['student_code']) ?></span>
            <span><i class="bi bi-card-text"></i> <?= e($student['admission_no']) ?></span>
            <?php if ($enrollment): ?>
                <span><i class="bi bi-mortarboard"></i> <?= e($enrollment['grade_name']) ?> — <?= e($enrollment['section_name']) ?></span>
            <?php endif; ?>
            <span><?= student_status_badge($student['status']) ?></span>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Personal</h6>
            <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($fullName) ?></span></div>
            <div class="info-row"><span class="label">Gender</span><span class="value"><?= e(ucfirst($student['gender'])) ?></span></div>
            <div class="info-row"><span class="label">Date of Birth</span><span class="value"><?= e(fmt_date($student['date_of_birth'])) ?></span></div>
            <div class="info-row"><span class="label">Admission Date</span><span class="value"><?= e(fmt_date($student['admission_date'])) ?></span></div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Contact</h6>
            <div class="info-row"><span class="label">Phone</span><span class="value"><?= e($student['phone'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Email</span><span class="value"><?= e($student['email'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Address</span><span class="value"><?= e($student['address'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Emergency</span><span class="value"><?= e($student['emergency_name'] ?: '—') ?></span></div>
        </div></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>