<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('student');

$user = current_user();
$student = student_for_user((int)$user['id']);
$enrollment = $student ? student_current_enrollment((int)$student['id']) : null;

$list = announcement_feed('student', (int)$user['id'],
    $enrollment ? (int)$enrollment['grade_id'] : null,
    $enrollment ? (int)$enrollment['section_id'] : null,
    50
);

// Mark all as read on page load
if ($list) {
    mark_announcements_read((int)$user['id'], array_column($list, 'id'));
}

$pageTitle       = 'Announcements';
$pageDescription = 'News and notices for you.';
$breadcrumbs     = [['label' => 'Announcements']];
$extraCss        = ['assets/css/announcements.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<?php if (!$list): ?>
    <div class="table-wrap">
        <?= ui_empty('No announcements', 'You have no announcements right now.', 'bi-megaphone') ?>
    </div>
<?php else: ?>
    <div class="ann-list">
        <?php foreach ($list as $a): ?>
            <div class="ann-card priority-<?= e($a['priority']) ?>">
                <div class="header">
                    <div class="flex-grow-1">
                        <h3 class="title"><?= e($a['title']) ?></h3>
                        <div class="meta">
                            <span><i class="bi bi-calendar"></i> <?= e(fmt_date($a['publish_date'], 'd M Y')) ?></span>
                            <?php if ($a['expiry_date']): ?>
                                <span><i class="bi bi-hourglass"></i> until <?= e(fmt_date($a['expiry_date'], 'd M Y')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ann-badges">
                        <?= announcement_priority_badge($a['priority']) ?>
                        <?= announcement_audience_badge($a['audience']) ?>
                    </div>
                </div>
                <p class="excerpt"><?= e(mb_substr($a['content'], 0, 260)) ?><?= mb_strlen($a['content']) > 260 ? '…' : '' ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>