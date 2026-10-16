<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_attendance');

$years  = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$terms  = Database::fetchAll("SELECT id, academic_year_id, name, is_current FROM terms ORDER BY sequence ASC");
$grades = Database::fetchAll("SELECT id, name FROM grades WHERE status='active' ORDER BY level ASC");

// Current context — from GET, or defaults
$yearId    = (int) ($_GET['academic_year_id'] ?? 0);
$termId    = (int) ($_GET['term_id'] ?? 0);
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);
$subjectId = (int) ($_GET['subject_id'] ?? 0) ?: null;
$date      = (string) ($_GET['date'] ?? date('Y-m-d'));

// Default to current year / term
if ($yearId === 0 && $years) {
    foreach ($years as $y) if ((int)$y['is_current']) { $yearId = (int)$y['id']; break; }
    if ($yearId === 0) $yearId = (int)$years[0]['id'];
}
if ($termId === 0 && $terms) {
    foreach ($terms as $t) if ((int)$t['is_current'] && (int)$t['academic_year_id'] === $yearId) { $termId = (int)$t['id']; break; }
    if ($termId === 0) {
        foreach ($terms as $t) if ((int)$t['academic_year_id'] === $yearId) { $termId = (int)$t['id']; break; }
    }
}

// Sections available for the chosen grade
$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? AND status='active' ORDER BY name ASC", [$gradeId])
    : [];

// Subjects available for the chosen grade
$subjects = $gradeId > 0
    ? Database::fetchAll("SELECT id, code, name FROM subjects WHERE grade_id = ? AND status='active' ORDER BY name ASC", [$gradeId])
    : [];

// If grade picked but no section yet, auto-pick first section
if ($gradeId > 0 && $sectionId === 0 && $sections) {
    $sectionId = (int)$sections[0]['id'];
}

// Load students if context complete
$students = [];
$existing = [];
if ($yearId > 0 && $gradeId > 0 && $sectionId > 0) {
    $students = students_in_section($sectionId, $yearId);

    // Existing attendance for the day
    $sql = "SELECT student_id, status FROM attendance
            WHERE section_id = ? AND date = ?";
    $params = [$sectionId, $date];
    if ($subjectId) { $sql .= " AND subject_id = ?"; $params[] = $subjectId; }
    else            { $sql .= " AND subject_id IS NULL"; }
    foreach (Database::fetchAll($sql, $params) as $r) {
        $existing[(int)$r['student_id']] = $r['status'];
    }
}

$pageTitle       = 'Take Attendance';
$pageDescription = 'Mark attendance for a class in seconds.';
$breadcrumbs     = [['label' => 'Attendance', 'url' => 'admin/attendance/index.php'], ['label' => 'Take']];
$extraCss        = ['assets/css/attendance.css'];
$extraJs         = ['assets/js/attendance.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<!-- Context selector -->
<form class="filter-bar row g-2 align-items-center mb-3" method="get" id="contextForm">
    <div class="col-md-2">
        <label class="form-label small mb-1">Academic Year</label>
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>>
                    <?= e($y['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Term</label>
        <select name="term_id" class="form-select" data-auto-submit>
            <?php foreach ($terms as $t): ?>
                <?php if ((int)$t['academic_year_id'] !== $yearId) continue; ?>
                <option value="<?= (int)$t['id'] ?>" <?= $termId === (int)$t['id'] ? 'selected' : '' ?>>
                    <?= e($t['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">— Grade —</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Section</label>
        <select name="section_id" class="form-select" data-auto-submit <?= !$sections ? 'disabled' : '' ?>>
            <option value="0">— Section —</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Subject</label>
        <select name="subject_id" class="form-select" data-auto-submit>
            <option value="0">— Homeroom —</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= (int)$sub['id'] ?>" <?= $subjectId === (int)$sub['id'] ? 'selected' : '' ?>>
                    <?= e($sub['code']) ?> — <?= e($sub['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Date</label>
        <input type="date" name="date" class="form-control" value="<?= e($date) ?>" data-auto-submit>
    </div>
</form>

<?php if (!$students): ?>
    <div class="table-wrap">
        <?= ui_empty(
            'Select class to begin',
            'Choose academic year, term, grade, and section to take attendance.',
            'bi-check2-square'
        ) ?>
    </div>
<?php else: ?>

    <form method="post" action="<?= e(url('admin/attendance/save.php')) ?>" id="attForm">
        <?= csrf_field() ?>
        <input type="hidden" name="academic_year_id" value="<?= (int)$yearId ?>">
        <input type="hidden" name="term_id" value="<?= (int)$termId ?>">
        <input type="hidden" name="grade_id" value="<?= (int)$gradeId ?>">
        <input type="hidden" name="section_id" value="<?= (int)$sectionId ?>">
        <input type="hidden" name="subject_id" value="<?= $subjectId ? (int)$subjectId : '' ?>">
        <input type="hidden" name="date" value="<?= e($date) ?>">

        <!-- Toolbar -->
        <div class="att-toolbar">
            <span class="title">
                <i class="bi bi-people me-1"></i>
                <span id="attTotal"><?= count($students) ?></span> students
            </span>

            <button type="button" class="btn btn-outline-success" data-mark-all="present"><i class="bi bi-check2-all me-1"></i> All Present</button>
            <button type="button" class="btn btn-outline-danger"  data-mark-all="absent"><i class="bi bi-x-circle me-1"></i> All Absent</button>
            <button type="button" class="btn btn-outline-warning" data-mark-all="late"><i class="bi bi-clock me-1"></i> All Late</button>
            <button type="button" class="btn btn-outline-info"    data-mark-all="excused"><i class="bi bi-shield-check me-1"></i> All Excused</button>

            <div class="att-summary ms-auto">
                <span class="att-chip present"><i class="bi bi-check-circle-fill"></i> <span data-att-count="present">0</span></span>
                <span class="att-chip absent"><i class="bi bi-x-circle-fill"></i> <span data-att-count="absent">0</span></span>
                <span class="att-chip late"><i class="bi bi-clock-fill"></i> <span data-att-count="late">0</span></span>
                <span class="att-chip excused"><i class="bi bi-shield-fill-check"></i> <span data-att-count="excused">0</span></span>
            </div>
        </div>

        <!-- Student list -->
        <div class="att-list" id="attList">
            <?php foreach ($students as $i => $s):
                $current = $existing[(int)$s['id']] ?? 'present';
                $name    = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                $photo   = !empty($s['photo']) ? url('uploads/profiles/' . $s['photo']) : null;
                $initials = mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1));
            ?>
                <div class="att-row">
                    <span class="avatar avatar-sm">
                        <?php if ($photo): ?>
                            <img src="<?= e($photo) ?>" alt="">
                        <?php else: ?>
                            <?= e($initials) ?>
                        <?php endif; ?>
                    </span>

                    <div class="info">
                        <div>
                            <div class="name"><?= e($name) ?></div>
                            <div class="meta">
                                <?= e($s['student_code']) ?>
                                <?php if (!empty($s['roll_no'])): ?>
                                    · Roll <?= (int)$s['roll_no'] ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="att-status-group">
                        <?php foreach (attendance_status_list() as $st): ?>
                            <input type="radio" id="st-<?= (int)$s['id'] ?>-<?= $st ?>" name="status[<?= (int)$s['id'] ?>]" value="<?= $st ?>"
                                   <?= $current === $st ? 'checked' : '' ?>>
                            <label for="st-<?= (int)$s['id'] ?>-<?= $st ?>">
                                <i class="bi <?= match($st) {
                                    'present' => 'bi-check',
                                    'absent'  => 'bi-x',
                                    'late'    => 'bi-clock',
                                    'excused' => 'bi-shield-check',
                                    default   => 'bi-question'
                                } ?>"></i>
                                <?= e(ucfirst($st)) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Save Attendance
            </button>
            <a href="?" class="btn btn-outline-primary">Reset</a>
        </div>

        <p class="text-muted small mt-2">
            <i class="bi bi-info-circle me-1"></i>
            Shortcuts: <kbd>P</kbd> present, <kbd>A</kbd> absent, <kbd>L</kbd> late, <kbd>E</kbd> excused, <kbd>J</kbd>/<kbd>K</kbd> navigate.
        </p>
    </form>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>