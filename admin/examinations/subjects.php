<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_marks');

$examId = (int) ($_GET['id'] ?? 0);
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

$errors = [];

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'add') {
    csrf_check_or_die();
    $subjectId = (int) post('subject_id', 0);
    $sectionId = (int) post('section_id', 0);
    $examDate  = clean((string) post('exam_date', ''));
    $maxMark   = (float) post('max_mark', 100);
    $passMark  = (float) post('pass_mark', 50);

    if ($subjectId <= 0) $errors['form'] = 'Subject is required.';
    elseif ($sectionId <= 0) $errors['form'] = 'Section is required.';

    if (!$errors) {
        $dup = Database::fetch(
            "SELECT id FROM examination_subjects WHERE examination_id = ? AND subject_id = ? AND section_id = ?",
            [$examId, $subjectId, $sectionId]
        );
        if ($dup) $errors['form'] = 'This subject/section combination already exists.';
        else {
            Database::insert('examination_subjects', [
                'examination_id' => $examId,
                'subject_id'     => $subjectId,
                'section_id'     => $sectionId,
                'exam_date'      => $examDate ?: null,
                'max_mark'       => $maxMark,
                'pass_mark'      => $passMark,
            ]);
            audit((int)current_user()['id'], 'exam_subject_added', 'examination_subjects', null, 'Subject added to exam');
            flash_set('success', 'Subject added.');
            redirect(url('admin/examinations/subjects.php?id=' . $examId));
        }
    }
}

// Remove
if (($_GET['remove'] ?? '') !== '' && ($_GET['csrf'] ?? '') !== '') {
    if (!hash_equals(csrf_token(), (string)$_GET['csrf'])) exit('Invalid CSRF token.');
    $esId = (int) $_GET['remove'];
    Database::conn()->prepare("DELETE FROM examination_subjects WHERE id = ? AND examination_id = ?")->execute([$esId, $examId]);
    audit((int)current_user()['id'], 'exam_subject_removed', 'examination_subjects', $esId, 'Subject removed from exam');
    flash_set('success', 'Subject removed.');
    redirect(url('admin/examinations/subjects.php?id=' . $examId));
}

$subjects  = Database::fetchAll("SELECT id, code, name FROM subjects WHERE grade_id = ? AND status='active' ORDER BY name ASC", [$exam['grade_id']]);
$sections  = Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? AND status='active' ORDER BY name ASC", [$exam['grade_id']]);
$current   = exam_subjects($examId);

$pageTitle       = 'Exam Subjects';
$pageDescription = 'Add subjects to ' . $exam['name'];
$breadcrumbs     = [
    ['label' => 'Examinations', 'url' => 'admin/examinations/index.php'],
    ['label' => $exam['name'], 'url' => 'admin/examinations/show.php?id=' . $examId],
    ['label' => 'Subjects'],
];
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

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-lg me-2"></i>Add Subject</div>
            <div class="card-body">
                <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
                <?php if (!$subjects || !$sections): ?>
                    <div class="alert alert-warning small">
                        No subjects or sections found for <strong><?= e($exam['grade_name']) ?></strong>.
                        Create subjects and sections first.
                    </div>
                <?php else: ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="mode" value="add">

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="0">— Select —</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>"><?= e($s['code']) ?> — <?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <select name="section_id" class="form-select" required>
                                <option value="0">— Select —</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= (int)$sec['id'] ?>"><?= e($sec['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Exam Date</label>
                            <input type="date" name="exam_date" class="form-control" value="<?= e(date('Y-m-d')) ?>">
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Max Mark</label>
                                <input type="number" step="0.01" min="1" name="max_mark" class="form-control" value="100">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Pass Mark</label>
                                <input type="number" step="0.01" min="0" name="pass_mark" class="form-control" value="50">
                            </div>
                        </div>

                        <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Add Subject</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-check me-2"></i>Current Subjects (<?= count($current) ?>)</div>
            <div class="card-body p-0">
                <?php if (!$current): ?>
                    <?= ui_empty('No subjects yet', 'Add a subject using the form on the left.', 'bi-list-check') ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Subject</th><th>Section</th><th>Exam Date</th><th>Max</th><th>Pass</th><th class="text-end"></th></tr></thead>
                            <tbody>
                            <?php foreach ($current as $es): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($es['subject_name']) ?></div>
                                        <div class="text-muted small"><?= e($es['subject_code']) ?></div>
                                    </td>
                                    <td><?= e($es['section_name']) ?></td>
                                    <td><?= e(fmt_date($es['exam_date'])) ?></td>
                                    <td><?= e((string)$es['max_mark']) ?></td>
                                    <td><?= e((string)$es['pass_mark']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= e(url('admin/examinations/subjects.php?id=' . $examId . '&remove=' . (int)$es['id'] . '&csrf=' . csrf_token())) ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           data-confirm="Remove this subject from the exam? All marks for this subject will be deleted.">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>