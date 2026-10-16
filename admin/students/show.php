<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_students');

$id = (int) ($_GET['id'] ?? 0);
$s  = Database::fetch("SELECT * FROM students WHERE id = ?", [$id]);
if (!$s) { flash_set('error', 'Student not found.'); redirect(url('admin/students/index.php')); }

$enrollment = student_current_enrollment($id);

// Attendance summary
$att = Database::fetch(
    "SELECT
        COUNT(*) AS total,
        SUM(status='present') AS present,
        SUM(status='absent')  AS absent,
        SUM(status='late')    AS late,
        SUM(status='excused') AS excused
     FROM attendance WHERE student_id = ?",
    [$id]
) ?: ['total'=>0,'present'=>0,'absent'=>0,'late'=>0,'excused'=>0];

$attPct = ($att['total'] > 0)
    ? round((($att['present'] + $att['late'] + $att['excused']) / $att['total']) * 100, 1)
    : 0;

$recentAttendance = Database::fetchAll(
    "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 10",
    [$id]
);

// Marks
$marks = Database::fetchAll(
    "SELECT m.*, sub.name AS subject_name, ex.name AS exam_name, ex.type AS exam_type
     FROM marks m
     JOIN subjects sub ON sub.id = m.subject_id
     JOIN examinations ex ON ex.id = m.examination_id
     WHERE m.student_id = ?
     ORDER BY ex.id DESC, sub.name ASC
     LIMIT 20",
    [$id]
);

// Enrollments
$allEnrollments = Database::fetchAll(
    "SELECT e.*, g.name AS grade_name, sec.name AS section_name, ay.name AS year_name
     FROM enrollments e
     JOIN grades g   ON g.id   = e.grade_id
     JOIN sections sec ON sec.id = e.section_id
     JOIN academic_years ay ON ay.id = e.academic_year_id
     WHERE e.student_id = ?
     ORDER BY ay.start_date DESC",
    [$id]
);

// Guardians
$guardians = Database::fetchAll(
    "SELECT gd.*, sg.is_primary
     FROM student_guardians sg
     JOIN guardians gd ON gd.id = sg.guardian_id
     WHERE sg.student_id = ?
     ORDER BY sg.is_primary DESC, gd.full_name ASC",
    [$id]
);

// Activity
$activity = Database::fetchAll(
    "SELECT a.action, a.description, a.created_at, u.full_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.entity = 'students' AND a.entity_id = ?
     ORDER BY a.id DESC LIMIT 20",
    [$id]
);

$photoUrl = student_photo_url($s);
$fullName = student_full_name($s);

$pageTitle       = $fullName;
$pageDescription = 'Student profile';
$breadcrumbs     = [
    ['label' => 'Students', 'url' => 'admin/students/index.php'],
    ['label' => $fullName],
];
$extraCss        = ['assets/css/students.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="profile-header">
    <div class="photo-lg">
        <?php if ($photoUrl): ?>
            <img src="<?= e($photoUrl) ?>" alt="">
        <?php else: ?>
            <?= e(mb_strtoupper(mb_substr($s['first_name'], 0, 1) . mb_substr($s['last_name'], 0, 1))) ?>
        <?php endif; ?>
    </div>
    <div class="info">
        <h1><?= e($fullName) ?></h1>
        <div class="meta">
            <span><i class="bi bi-hash"></i> <?= e($s['student_code']) ?></span>
            <span><i class="bi bi-card-text"></i> <?= e($s['admission_no']) ?></span>
            <?php if ($enrollment): ?>
                <span><i class="bi bi-mortarboard"></i> <?= e($enrollment['grade_name']) ?> — <?= e($enrollment['section_name']) ?></span>
            <?php endif; ?>
            <span><?= student_status_badge($s['status']) ?></span>
        </div>
    </div>
    <div class="actions">
        <a href="<?= e(url('admin/students/edit.php?id=' . $id)) ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="<?= e(url('admin/students/delete.php?id=' . $id)) ?>" class="btn btn-outline-danger"
           data-confirm="Delete this student? This cannot be undone.">
            <i class="bi bi-trash me-1"></i> Delete
        </a>
    </div>
</div>

<!-- Tabs -->
<nav class="nav-tabs-modern mb-3">
    <a href="#overview"     class="tab-link"><i class="bi bi-person"></i> Overview</a>
    <a href="#academic"     class="tab-link"><i class="bi bi-mortarboard"></i> Academic</a>
    <a href="#attendance"   class="tab-link"><i class="bi bi-check2-square"></i> Attendance</a>
    <a href="#examinations" class="tab-link"><i class="bi bi-clipboard-check"></i> Examinations</a>
    <a href="#guardians"    class="tab-link"><i class="bi bi-people"></i> Guardians</a>
    <a href="#activity"     class="tab-link"><i class="bi bi-activity"></i> Activity</a>
</nav>

<!-- OVERVIEW -->
<div id="tab-overview" class="tab-panel">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Personal Details</h6>
                <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($fullName) ?></span></div>
                <div class="info-row"><span class="label">Gender</span><span class="value"><?= e(ucfirst($s['gender'])) ?></span></div>
                <div class="info-row"><span class="label">Date of Birth</span><span class="value"><?= e(fmt_date($s['date_of_birth'])) ?></span></div>
                <div class="info-row"><span class="label">Admission Date</span><span class="value"><?= e(fmt_date($s['admission_date'])) ?></span></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Contact</h6>
                <div class="info-row"><span class="label">Phone</span><span class="value"><?= e($s['phone'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Email</span><span class="value"><?= e($s['email'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Address</span><span class="value"><?= e($s['address'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Emergency Contact</span><span class="value"><?= e($s['emergency_name'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Emergency Phone</span><span class="value"><?= e($s['emergency_phone'] ?: '—') ?></span></div>
            </div></div>
        </div>
    </div>
</div>

<!-- ACADEMIC -->
<div id="tab-academic" class="tab-panel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-mortarboard me-2"></i>Enrollment History</span>
            <a href="<?= e(url('admin/enrollments/create.php?student_id=' . $id)) ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Enrollment
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (!$allEnrollments): ?>
                <?= ui_empty('No enrollments', 'This student has not been enrolled yet.', 'bi-journal') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Year</th><th>Grade</th><th>Section</th><th>Roll</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($allEnrollments as $e): ?>
                            <tr>
                                <td><?= e($e['year_name']) ?></td>
                                <td><?= e($e['grade_name']) ?></td>
                                <td><?= e($e['section_name']) ?></td>
                                <td><?= e((string)($e['roll_no'] ?? '—')) ?></td>
                                <td><?= ui_badge(ucfirst($e['status']), $e['status'] === 'active' ? 'success' : 'secondary') ?></td>
                                <td><?= e(fmt_date($e['enrollment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ATTENDANCE -->
<div id="tab-attendance" class="tab-panel">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-body text-center">
                <div class="stat-value" style="font-size:2rem;"><?= $attPct ?>%</div>
                <div class="stat-label">Attendance Rate</div>
                <div class="mt-3 d-flex justify-content-around small">
                    <div><strong><?= (int)$att['present'] ?></strong><br><span class="text-muted">Present</span></div>
                    <div><strong><?= (int)$att['absent'] ?></strong><br><span class="text-muted">Absent</span></div>
                    <div><strong><?= (int)$att['late'] ?></strong><br><span class="text-muted">Late</span></div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-2"></i>Recent Attendance</div>
                <div class="card-body p-0">
                    <?php if (!$recentAttendance): ?>
                        <?= ui_empty('No attendance records', 'Attendance will appear here once recorded.', 'bi-check2-square') ?>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentAttendance as $a): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= e(fmt_date($a['date'], 'D, d M Y')) ?></span>
                                    <?= ui_badge(ucfirst($a['status']), match($a['status']) {
                                        'present' => 'success', 'absent' => 'danger',
                                        'late'    => 'warning', 'excused' => 'info', default => 'secondary'
                                    }) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EXAMINATIONS -->
<div id="tab-examinations" class="tab-panel">
    <div class="card">
        <div class="card-header"><i class="bi bi-clipboard-check me-2"></i>Recent Marks</div>
        <div class="card-body p-0">
            <?php if (!$marks): ?>
                <?= ui_empty('No marks recorded', 'Examination marks will appear here.', 'bi-clipboard-check') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Exam</th><th>Subject</th><th>Score</th><th>%</th><th>Grade</th><th>Remark</th></tr></thead>
                        <tbody>
                        <?php foreach ($marks as $m): ?>
                            <tr>
                                <td><?= e($m['exam_name']) ?> <span class="text-muted small">(<?= e($m['exam_type']) ?>)</span></td>
                                <td><?= e($m['subject_name']) ?></td>
                                <td><?= e((string)$m['score']) ?> / <?= e((string)$m['max_mark']) ?></td>
                                <td><?= $m['percentage'] !== null ? e((string)$m['percentage']) . '%' : '—' ?></td>
                                <td><?= $m['grade_letter'] ? ui_badge($m['grade_letter'], 'primary') : '—' ?></td>
                                <td class="text-muted small"><?= e($m['remarks'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- GUARDIANS -->
<div id="tab-guardians" class="tab-panel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-people me-2"></i>Guardians</span>
            <a href="<?= e(url('admin/students/attach-guardian.php?student_id=' . $id)) ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-person-plus me-1"></i> Attach Guardian
            </a>
        </div>
        <div class="card-body">
            <?php if (!$guardians): ?>
                <?= ui_empty('No guardians', 'Attach a guardian to this student.', 'bi-people',
                             url('admin/students/attach-guardian.php?student_id=' . $id), 'Attach Guardian') ?>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($guardians as $g): ?>
                        <div class="guardian-chip">
                            <div>
                                <div class="fw-semibold"><?= e($g['full_name']) ?></div>
                                <div class="role"><?= e(ucfirst(str_replace('_', ' ', $g['relationship']))) ?></div>
                            </div>
                            <div class="ms-auto small text-muted">
                                <div><i class="bi bi-telephone"></i> <?= e($g['phone']) ?></div>
                                <?php if ($g['email']): ?>
                                    <div><i class="bi bi-envelope"></i> <?= e($g['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ((int)$g['is_primary']): ?>
                                    <?= ui_badge('Primary', 'primary') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ACTIVITY -->
<div id="tab-activity" class="tab-panel">
    <div class="card">
        <div class="card-header"><i class="bi bi-activity me-2"></i>Activity Log</div>
        <div class="card-body p-0">
            <?php if (!$activity): ?>
                <?= ui_empty('No activity', 'Actions on this student will appear here.', 'bi-activity') ?>
            <?php else: ?>
                <ul class="activity-feed">
                    <?php foreach ($activity as $a): ?>
                        <li>
                            <span class="af-icon"><i class="bi bi-check2-circle"></i></span>
                            <div class="af-body">
                                <p class="af-title"><?= e($a['description'] ?: ucfirst(str_replace('_', ' ', $a['action']))) ?></p>
                                <div class="af-meta">
                                    <i class="bi bi-person"></i> <?= e($a['full_name'] ?: 'System') ?>
                                    · <?= e(fmt_date($a['created_at'], 'd M Y H:i')) ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$extraCss = ['assets/css/dashboard.css', 'assets/css/students.css'];
$extraJs  = ['assets/js/students.js'];
include __DIR__ . '/../../includes/footer.php';
?>