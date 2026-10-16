<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('parent');

$user = current_user();
$parent = parent_for_user((int)$user['id']);

if (!$parent) {
    $pageTitle = 'Parent Dashboard';
    $breadcrumbs = [['label' => 'Dashboard']];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-exclamation-circle" style="font-size:3rem;color:var(--sms-warning);"></i>
        <h5 class="mt-3">Parent record not linked</h5>
        <p class="text-muted">Your account is not yet linked to a parent record. Contact the administrator.</p>
    </div></div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$children = parent_children((int)$parent['id']);
$announcements = announcement_feed('parent', (int)$user['id'], null, null, 5);

$parentName = $parent['full_name'] ?? ($user['full_name'] ?? 'Parent');

$pageTitle       = 'Parent Dashboard';
$pageDescription = 'Monitor your children\'s progress.';
$breadcrumbs     = [['label' => 'Dashboard']];
$extraCss        = ['assets/css/dashboards.css', 'assets/css/announcements.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="hero-card">
    <div class="hero-avatar">
        <?= e(mb_strtoupper(mb_substr($parentName, 0, 1))) ?>
    </div>
    <div>
        <h1>Welcome, <?= e($parentName) ?></h1>
        <p class="sub"><?= count($children) ?> child(ren) · <?= e(setting('school_name', 'SMS')) ?></p>
    </div>
    <div class="hero-actions">
        <a href="<?= e(url('parent/attendance.php')) ?>" class="btn btn-sm"><i class="bi bi-check2-square me-1"></i> Attendance</a>
        <a href="<?= e(url('parent/results.php')) ?>" class="btn btn-sm"><i class="bi bi-trophy me-1"></i> Results</a>
    </div>
</div>

<!-- Children cards -->
<div class="row g-3 mb-3">
    <?php if (!$children): ?>
        <div class="col-12">
            <div class="card"><div class="card-body text-center py-4">
                <i class="bi bi-people text-muted" style="font-size:2rem;"></i>
                <h6 class="mt-3 mb-1">No children linked</h6>
                <p class="text-muted small mb-0">Contact the administrator to link your children to this account.</p>
            </div></div>
        </div>
    <?php else: ?>
        <?php foreach ($children as $c):
            $photo = !empty($c['photo']) ? url('uploads/profiles/' . $c['photo']) : null;
            $name  = trim(($c['first_name'] ?? '') . ' ' . ($c['middle_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            $init  = mb_strtoupper(mb_substr($c['first_name'] ?? 'S', 0, 1) . mb_substr($c['last_name'] ?? '', 0, 1));
            $att   = student_attendance_summary((int)$c['id']);
            $perf  = student_performance_overview((int)$c['id']);
        ?>
            <div class="col-md-6 col-xl-4">
                <div class="child-card">
                    <div class="photo">
                        <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="">
                        <?php else: ?><?= e($init) ?><?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="name text-truncate mb-0"><?= e($name) ?></p>
                        <div class="meta">
                            <?= e($c['student_code']) ?>
                            <?php if (!empty($c['grade_name'])): ?>
                                · <?= e($c['grade_name']) ?> — <?= e($c['section_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stats">
                        <div class="mini-stat">
                            <div class="v"><?= (int)$att['percentage'] ?>%</div>
                            <div class="l">Attend</div>
                        </div>
                        <div class="mini-stat">
                            <div class="v"><?= $perf['avg_pct'] ?>%</div>
                            <div class="l">Avg</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <a href="<?= e(url('parent/attendance.php?student_id=' . (int)$c['id'])) ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                        <i class="bi bi-check2-square me-1"></i> Attendance
                    </a>
                    <a href="<?= e(url('parent/results.php?student_id=' . (int)$c['id'])) ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                        <i class="bi bi-trophy me-1"></i> Results
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Announcements -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-megaphone me-2"></i>Announcements</span>
        <a href="<?= e(url('parent/announcements.php')) ?>" class="small">View all</a>
    </div>
    <div class="card-body">
        <?php if (!$announcements): ?>
            <?= ui_empty('No announcements', 'You have no notices right now.', 'bi-megaphone') ?>
        <?php else: ?>
            <?php foreach ($announcements as $a): ?>
                <?= render_announcement_banner($a) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>