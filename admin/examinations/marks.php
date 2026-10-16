<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('enter_marks');

$examId    = (int) ($_GET['exam_id'] ?? 0);
$subjectId = (int) ($_GET['subject_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);

$exam = Database::fetch(
    "SELECT e.*, g.name AS grade_name, ay.name AS year_name, t.name AS term_name
     FROM examinations e
     JOIN grades g ON g.id = e.grade_id
     JOIN academic_years ay ON ay.id = e.academic_year_id
     JOIN terms t ON t.id = e.term_id
     WHERE e.id = ?",
    [$examId]
);
if (!$exam) { flash_set('error', 'Examination not found.'); redirect(url('admin/examinations/index.php')); }

$examSubjects = exam_subjects($examId);
$subjects     = Database::fetchAll("SELECT id, code, name FROM subjects WHERE grade_id = ? AND status='active' ORDER BY name ASC", [$exam['grade_id']]);
$sections     = Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? AND status='active' ORDER BY name ASC", [$exam['grade_id']]);

// If none chosen, default to first
if ($subjectId === 0 && $examSubjects) $subjectId = (int)$examSubjects[0]['subject_id'];
if ($sectionId === 0 && $examSubjects) $sectionId = (int)$examSubjects[0]['section_id'];

// Find the exam_subject record
$examSubject = null;
if ($subjectId && $sectionId) {
    $examSubject = Database::fetch(
        "SELECT * FROM examination_subjects WHERE examination_id = ? AND subject_id = ? AND section_id = ?",
        [$examId, $subjectId, $sectionId]
    );
    if (!$examSubject) {
        flash_set('warning', 'This subject is not part of the exam. Add it first.');
        redirect(url('admin/examinations/subjects.php?id=' . $examId));
    }
}

$students = [];
if ($examSubject) {
    $students = students_for_marks($examId, $subjectId, $sectionId, (int)$exam['academic_year_id']);
}

$pageTitle       = 'Enter Marks';
$pageDescription = $exam['name'] . ' — ' . $exam['grade_name'];
$breadcrumbs     = [
    ['label' => 'Examinations', 'url' => 'admin/examinations/index.php'],
    ['label' => $exam['name'], 'url' => 'admin/examinations/show.php?id=' . $examId],
    ['label' => 'Enter Marks'],
];
$extraCss        = ['assets/css/examinations.css'];
$extraJs         = ['assets/js/marks.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/examinations/show.php?id=' . $examId)) ?>" class="btn btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Selector -->
<form class="filter-bar row g-2 align-items-center mb-3" method="get">
    <input type="hidden" name="exam_id" value="<?= (int)$examId ?>">
    <div class="col-md-5">
        <label class="form-label small mb-1">Subject</label>
        <select name="subject_id" class="form-select" data-auto-submit required>
            <option value="0">— Select subject —</option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= $subjectId === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= e($s['code']) ?> — <?= e($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">Section</label>
        <select name="section_id" class="form-select" data-auto-submit required>
            <option value="0">— Select section —</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!$examSubject): ?>
    <div class="table-wrap">
        <?= ui_empty('Choose subject and section', 'Select the subject and section to enter marks.', 'bi-pencil-square') ?>
    </div>
<?php elseif (!$students): ?>
    <div class="table-wrap">
        <?= ui_empty('No students', 'No active enrollments found for this section.', 'bi-people') ?>
    </div>
<?php else: ?>

    <div class="exam-progress mb-3">
        <i class="bi bi-info-circle"></i>
        <span>Max mark: <?= e((string)$examSubject['max_mark']) ?> · Pass mark: <?= e((string)$examSubject['pass_mark']) ?></span>
        <span class="ms-auto"><?= count($students) ?> students</span>
    </div>

    <form method="post" action="<?= e(url('admin/examinations/save-marks.php')) ?>" id="marksForm">
        <?= csrf_field() ?>
        <input type="hidden" name="examination_id"    value="<?= (int)$examId ?>">
        <input type="hidden" name="exam_subject_id"   value="<?= (int)$examSubject['id'] ?>">
        <input type="hidden" name="subject_id"        value="<?= (int)$subjectId ?>">
        <input type="hidden" name="section_id"        value="<?= (int)$sectionId ?>">
        <input type="hidden" name="academic_year_id"  value="<?= (int)$exam['academic_year_id'] ?>">
        <input type="hidden" name="max_mark"          value="<?= e((string)$examSubject['max_mark']) ?>">

        <div class="table-responsive">
            <table class="marks-grid">
                <thead>
                    <tr>
                        <th style="width:44px;">#</th>
                        <th>Student</th>
                        <th style="width:120px;">Score</th>
                        <th style="width:100px;">%</th>
                        <th style="width:80px;">Grade</th>
                        <th style="width:90px;">Absent</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($students as $s):
                    $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                    $photo = !empty($s['photo']) ? url('uploads/profiles/' . $s['photo']) : null;
                    $initials = mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1));
                    $score = $s['score'] !== null ? rtrim(rtrim((string)$s['score'], '0'), '.') : '';
                    $isAbsent = (int)($s['is_absent'] ?? 0) === 1;
                ?>
                    <tr>
                        <td class="text-muted"><?= $i++ ?></td>
                        <td>
                            <div class="student-cell">
                                <span class="avatar avatar-sm">
                                    <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="">
                                    <?php else: ?><?= e($initials) ?><?php endif; ?>
                                </span>
                                <div>
                                    <div class="fw-semibold"><?= e($name) ?></div>
                                    <div class="text-muted small"><?= e($s['student_code']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="<?= e((string)$examSubject['max_mark']) ?>"
                                   name="score[<?= (int)$s['id'] ?>]"
                                   value="<?= e($score) ?>"
                                   class="marks-input">
                            <span class="marks-max" data-max="<?= e((string)$examSubject['max_mark']) ?>">/ <?= e((string)$examSubject['max_mark']) ?></span>
                        </td>
                        <td class="marks-pct-cell"><?= $s['percentage'] !== null ? e((string)$s['percentage']) . '%' : '—' ?></td>
                        <td class="marks-letter-cell"><?= grade_badge($s['grade_letter'] ?? null) ?></td>
                        <td>
                            <input type="checkbox" class="absent-check" data-absent-toggle
                                   name="absent[<?= (int)$s['id'] ?>]" value="1"
                                   <?= $isAbsent ? 'checked' : '' ?>>
                        </td>
                        <td>
                            <input type="text" name="remarks[<?= (int)$s['id'] ?>]"
                                   value="<?= e((string)($s['remarks'] ?? '')) ?>"
                                   class="form-control form-control-sm"
                                   placeholder="Optional">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary" id="saveMarksBtn">
                <i class="bi bi-check2 me-1"></i> Save Marks
            </button>
            <a href="<?= e(url('admin/examinations/show.php?id=' . $examId)) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
        <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Press <kbd>Enter</kbd> to jump to the next student.</p>
    </form>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>