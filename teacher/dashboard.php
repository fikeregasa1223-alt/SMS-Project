<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('teacher');

$user = current_user();
$teacher = teacher_for_user((int)$user['id']);

if (!$teacher) {
    $pageTitle = 'Teacher Dashboard';
    $breadcrumbs = [['label' => 'Dashboard']];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-exclamation-circle" style="font-size:3rem;color:var(--sms-warning);"></i>
        <h5 class="mt-3">Teacher record not linked</h5>
        <p class="text-muted">Your account is not yet linked to a teacher record. Contact the administrator.</p>
    </div></div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$currentYear = Database::fetch("SELECT id, name FROM academic_years WHERE is_current = 1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : null;

$assignedStudents = teacher_student_count((int)$teacher['id'], $yearId);
$assignedSections = teacher_assigned_sections((int)$teacher['id'], $yearId);
$assignedSubjects = teacher_assigned_subjects((int)$teacher['id'], $yearId);
$todayAtt         = teacher_today_attendance((int)$teacher['id']);
$pendingMarks     = teacher_pending_marks((int)$teacher['id']);
$recentStudents   = teacher_recent_students((int)$teacher['id'], 5);
$announcements    = announcement_feed('teacher', (int)$user['id'], null, null, 5);

$photoUrl = teacher_photo_url($teacher);
$fullName = teacher_full_name($teacher);
$initials = mb_strtoupper(mb_substr($teacher['first_name'] ?? 'T', 0, 1) . mb_substr($teacher['last_name'] ?? '', 0, 1));

$pageTitle       = 'Teacher Dashboard';
$pageDescription = 'Your teaching overview.';
$breadcrumbs     = [['label' => 'Dashboard']];
$extraCss        = ['assets/css/dashboards.css', 'assets/css/announcements.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="hero-card">
    <div class="hero-avatar">
        <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="">
        <?php else: ?><?= e($initials) ?><?php endif; ?>
    </div>
    <div>
        <h1>Welcome, <?= e($teacher['first_name']) ?> 👋</h1>
        <p class="sub">
            <?= e($teacher['teacher_code']) ?>
            <?php if ($teacher['specialization']): ?> · <?= e($teacher['specialization']) ?><?php endif; ?>
            <?php if ($currentYear): ?> · <?= e($currentYear['name']) ?><?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="<?= e(url('teacher/attendance/take.php')) ?>" class="btn btn-sm"><i class="bi bi-check2-square me-1"></i> Take Attendance</a>
        <a href="<?= e(url('teacher/marks/index.php')) ?>" class="btn btn-sm"><i class="bi bi-pencil-square me-1"></i> Enter Marks</a>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-primary"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= number_format($assignedStudents) ?></div>
                <div class="stat-label">My Students</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-info"><i class="bi bi-diagram-3-fill"></i></div>
            <div>
                <div class="stat-value"><?= count($assignedSections) ?></div>
                <div class="stat-label">My Classes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-success"><i class="bi bi-check2-square"></i></div>
            <div>
                <div class="stat-value"><?= $todayAtt['percentage'] ?>%</div>
                <div class="stat-label">Today's Attendance</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-warning"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value"><?= $pendingMarks ?></div>
                <div class="stat-label">Pending Marks</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</div>
            <div class="card-body">
                <div class="qa-grid">
                    <a class="qa-tile" href="<?= e(url('teacher/attendance/take.php')) ?>">
                        <span class="icon"><i class="bi bi-check2-square"></i></span>
                        <span class="label">Take Attendance</span>
                        <span class="desc">Mark today's attendance</span>
                    </a>
                    <a class="qa-tile" href="<?= e(url('teacher/marks/index.php')) ?>">
                        <span class="icon"><i class="bi bi-pencil-square"></i></span>
                        <span class="label">Enter Marks</span>
                        <span class="desc">Record exam scores</span>
                    </a>
                    <a class="qa-tile" href="<?= e(url('teacher/students/index.php')) ?>">
                        <span class="icon"><i class="bi bi-people"></i></span>
                        <span class="label">My Students</span>
                        <span class="desc">View all students</span>
                    </a>
                    <a class="qa-tile" href="<?= e(url('teacher/classes/index.php')) ?>">
                        <span class="icon"><i class="bi bi-diagram-3"></i></span>
                        <span class="label">My Classes</span>
                        <span class="desc">See assignments</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Assigned classes -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>My Classes</div>
            <div class="card-body p-0">
                <?php if (!$assignedSections): ?>
                    <?= ui_empty('No classes assigned', 'You have no assigned classes yet.', 'bi-diagram-3') ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Grade</th><th>Section</th><th>Year</th></tr></thead>
                            <tbody>
                            <?php foreach ($assignedSections as $s): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($s['grade_name']) ?></td>
                                    <td><?= e($s['section_name']) ?></td>
                                    <td class="text-muted small"><?= e($s['year_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Announcements -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-megaphone me-2"></i>Announcements</span>
                <a href="<?= e(url('teacher/announcements/index.php')) ?>" class="small">View all</a>
            </div>
            <div class="card-body">
                <?php if (!$announcements): ?>
                    <?= ui_empty('No announcements', 'You have no new notices.', 'bi-megaphone') ?>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <?= render_announcement_banner($a) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent students -->
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>Recent Students</span>
        <a href="<?= e(url('teacher/students/index.php')) ?>" class="small">View all</a>
    </div>
    <div class="card-body p-0">
        <?php if (!$recentStudents): ?>
            <?= ui_empty('No students', 'You have no students yet.', 'bi-people') ?>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentStudents as $s):
                    $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                    $photo = !empty($s['photo']) ? url('uploads/profiles/' . $s['photo']) : null;
                    $initials = mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1));
                ?>
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm">
                            <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="">
                            <?php else: ?><?= e($initials) ?><?php endif; ?>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= e($name) ?></div>
                            <div class="text-muted small"><?= e($s['student_code']) ?></div>
                        </div>
                        <a href="<?= e(url('admin/students/show.php?id=' . (int)$s['id'])) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>