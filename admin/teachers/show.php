<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_teachers');

$id = (int) ($_GET['id'] ?? 0);
$t  = Database::fetch("SELECT * FROM teachers WHERE id = ?", [$id]);
if (!$t) { flash_set('error', 'Teacher not found.'); redirect(url('admin/teachers/index.php')); }

$assignments = Database::fetchAll(
    "SELECT ta.*, ay.name AS year_name, g.name AS grade_name, sec.name AS section_name, sub.name AS subject_name, sub.code AS subject_code
     FROM teacher_assignments ta
     JOIN academic_years ay ON ay.id = ta.academic_year_id
     JOIN grades   g   ON g.id   = ta.grade_id
     JOIN sections sec ON sec.id = ta.section_id
     JOIN subjects sub ON sub.id = ta.subject_id
     WHERE ta.teacher_id = ?
     ORDER BY ay.start_date DESC, g.level ASC, sec.name ASC, sub.name ASC",
    [$id]
);

$years     = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");
$activity  = Database::fetchAll(
    "SELECT a.action, a.description, a.created_at, u.full_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.entity = 'teachers' AND a.entity_id = ?
     ORDER BY a.id DESC LIMIT 20",
    [$id]
);

$photoUrl = teacher_photo_url($t);
$fullName = teacher_full_name($t);

$pageTitle       = $fullName;
$pageDescription = 'Teacher profile';
$breadcrumbs     = [
    ['label' => 'Teachers', 'url' => 'admin/teachers/index.php'],
    ['label' => $fullName],
];
$extraCss        = ['assets/css/teachers.css', 'assets/css/dashboard.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="profile-header">
    <div class="photo-lg">
        <?php if ($photoUrl): ?>
            <img src="<?= e($photoUrl) ?>" alt="">
        <?php else: ?>
            <?= e(mb_strtoupper(mb_substr($t['first_name'], 0, 1) . mb_substr($t['last_name'], 0, 1))) ?>
        <?php endif; ?>
    </div>
    <div class="info">
        <h1><?= e($fullName) ?></h1>
        <div class="meta">
            <span><i class="bi bi-hash"></i> <?= e($t['teacher_code']) ?></span>
            <span><i class="bi bi-card-text"></i> <?= e($t['employee_no']) ?></span>
            <?php if ($t['specialization']): ?>
                <span><i class="bi bi-mortarboard"></i> <?= e($t['specialization']) ?></span>
            <?php endif; ?>
            <span><?= employment_status_badge($t['employment_status']) ?></span>
        </div>
    </div>
    <div class="actions">
        <a href="<?= e(url('admin/teachers/edit.php?id=' . $id)) ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="<?= e(url('admin/teacher-assignments/create.php?teacher_id=' . $id)) ?>" class="btn btn-outline-primary">
            <i class="bi bi-plus-lg me-1"></i> Assign
        </a>
    </div>
</div>

<nav class="nav-tabs-modern mb-3">
    <a href="#overview"    class="tab-link"><i class="bi bi-person"></i> Overview</a>
    <a href="#assignments" class="tab-link"><i class="bi bi-person-workspace"></i> Assignments</a>
    <a href="#classes"     class="tab-link"><i class="bi bi-diagram-3"></i> Classes</a>
    <a href="#activity"    class="tab-link"><i class="bi bi-activity"></i> Activity</a>
</nav>

<!-- OVERVIEW -->
<div id="tab-overview" class="tab-panel">
    <div class="mini-stats mb-3">
        <div class="mini-stat">
            <span class="v"><?= count($assignments) ?></span>
            <span class="l">Total Assignments</span>
        </div>
        <div class="mini-stat">
            <span class="v"><?= count(array_unique(array_column($assignments, 'section_id'))) ?></span>
            <span class="l">Sections</span>
        </div>
        <div class="mini-stat">
            <span class="v"><?= count(array_unique(array_column($assignments, 'subject_id'))) ?></span>
            <span class="l">Subjects</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Personal</h6>
                <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($fullName) ?></span></div>
                <div class="info-row"><span class="label">Gender</span><span class="value"><?= e(ucfirst($t['gender'])) ?></span></div>
                <div class="info-row"><span class="label">Date of Birth</span><span class="value"><?= e(fmt_date($t['date_of_birth'])) ?></span></div>
                <div class="info-row"><span class="label">Phone</span><span class="value"><?= e($t['phone'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Email</span><span class="value"><?= e($t['email'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Address</span><span class="value"><?= e($t['address'] ?: '—') ?></span></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Professional</h6>
                <div class="info-row"><span class="label">Qualification</span><span class="value"><?= e($t['qualification'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Specialization</span><span class="value"><?= e($t['specialization'] ?: '—') ?></span></div>
                <div class="info-row"><span class="label">Hire Date</span><span class="value"><?= e(fmt_date($t['hire_date'])) ?></span></div>
                <div class="info-row"><span class="label">Employment</span><span class="value"><?= employment_status_badge($t['employment_status']) ?></span></div>
            </div></div>
        </div>
    </div>
</div>

<!-- ASSIGNMENTS -->
<div id="tab-assignments" class="tab-panel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-person-workspace me-2"></i>Assignments</span>
            <a href="<?= e(url('admin/teacher-assignments/create.php?teacher_id=' . $id)) ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (!$assignments): ?>
                <?= ui_empty('No assignments', 'Assign this teacher to sections and subjects.', 'bi-person-workspace',
                             url('admin/teacher-assignments/create.php?teacher_id=' . $id), 'Add Assignment') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Year</th><th>Grade</th><th>Section</th><th>Subject</th><th>Class Teacher</th><th class="text-end"></th></tr></thead>
                        <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><?= e($a['year_name']) ?></td>
                                <td><?= e($a['grade_name']) ?></td>
                                <td><?= e($a['section_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($a['subject_code']) ?></span> <?= e($a['subject_name']) ?></td>
                                <td><?= (int)$a['is_class_teacher'] ? ui_badge('Yes', 'primary') : '—' ?></td>
                                <td class="text-end">
                                    <?= ui_row_actions([
                                        ['label' => 'Edit',   'href' => url('admin/teacher-assignments/edit.php?id='   . (int)$a['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                        ['label' => 'Delete', 'href' => url('admin/teacher-assignments/delete.php?id=' . (int)$a['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                         'confirm' => 'Delete this assignment?'],
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CLASSES -->
<div id="tab-classes" class="tab-panel">
    <div class="card">
        <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>Assigned Classes</div>
        <div class="card-body">
            <?php if (!$assignments): ?>
                <?= ui_empty('No classes', 'Assignments will appear here.', 'bi-diagram-3') ?>
            <?php else:
                $classes = [];
                foreach ($assignments as $a) {
                    $key = $a['year_name'] . '|' . $a['grade_name'] . '|' . $a['section_name'];
                    $classes[$key] = [
                        'year' => $a['year_name'], 'grade' => $a['grade_name'], 'section' => $a['section_name']
                    ];
                }
            ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($classes as $c): ?>
                        <div class="assignment-chip">
                            <i class="bi bi-diagram-3 text-primary"></i>
                            <span class="subject"><?= e($c['grade']) ?> — <?= e($c['section']) ?></span>
                            <span class="class-meta ms-auto"><?= e($c['year']) ?></span>
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
                <?= ui_empty('No activity', 'Actions on this teacher will appear here.', 'bi-activity') ?>
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
$extraJs = ['assets/js/students.js'];
include __DIR__ . '/../../includes/footer.php';
?>