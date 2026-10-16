<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_teachers');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM teacher_assignments WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Assignment not found.'); redirect(url('admin/teacher-assignments/index.php')); }

$errors = [];
$data   = $row;

$teachers = Database::fetchAll("SELECT id, teacher_code, first_name, middle_name, last_name FROM teachers ORDER BY first_name");
$years    = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");
$subjects = Database::fetchAll("SELECT id, grade_id, code, name FROM subjects ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['teacher_id','academic_year_id','grade_id','section_id','subject_id'] as $k) {
        $data[$k] = (int) post($k, 0);
    }
    $data['is_class_teacher'] = (int) post('is_class_teacher', 0) === 1 ? 1 : 0;

    if ($data['teacher_id'] <= 0)       $errors['teacher_id']       = 'Teacher is required.';
    if ($data['academic_year_id'] <= 0) $errors['academic_year_id'] = 'Academic year is required.';
    if ($data['grade_id'] <= 0)         $errors['grade_id']         = 'Grade is required.';
    if ($data['section_id'] <= 0)       $errors['section_id']       = 'Section is required.';
    if ($data['subject_id'] <= 0)       $errors['subject_id']       = 'Subject is required.';

    if (!$errors) {
        $dup = Database::fetch(
            "SELECT id FROM teacher_assignments
             WHERE teacher_id = ? AND academic_year_id = ? AND section_id = ? AND subject_id = ? AND id <> ?",
            [$data['teacher_id'], $data['academic_year_id'], $data['section_id'], $data['subject_id'], $id]
        );
        if ($dup) $errors['subject_id'] = 'Duplicate assignment.';
    }

    if (!$errors) {
        if ($data['is_class_teacher'] === 1) {
            Database::conn()->prepare(
                "UPDATE teacher_assignments SET is_class_teacher = 0
                 WHERE academic_year_id = ? AND section_id = ? AND id <> ?"
            )->execute([$data['academic_year_id'], $data['section_id'], $id]);
        }

        Database::update('teacher_assignments', [
            'teacher_id'       => $data['teacher_id'],
            'academic_year_id' => $data['academic_year_id'],
            'grade_id'         => $data['grade_id'],
            'section_id'       => $data['section_id'],
            'subject_id'       => $data['subject_id'],
            'is_class_teacher' => $data['is_class_teacher'],
        ], 'id = :id', ['id' => $id]);

        audit((int)current_user()['id'], 'teacher_assignment_updated', 'teacher_assignments', $id, 'Assignment updated');
        flash_set('success', 'Assignment updated.');
        redirect(url('admin/teacher-assignments/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit Assignment';
$pageDescription = 'Update teacher assignment.';
$breadcrumbs     = [['label' => 'Teacher Assignments', 'url' => 'admin/teacher-assignments/index.php'], ['label' => 'Edit']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Teacher <span class="text-danger">*</span></label>
            <select name="teacher_id" class="form-select" required>
                <?php foreach ($teachers as $t):
                    $nm = trim(($t['first_name'] ?? '') . ' ' . ($t['middle_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
                ?>
                    <option value="<?= (int)$t['id'] ?>" <?= (int)$data['teacher_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= e($t['teacher_code']) ?> — <?= e($nm) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <select name="academic_year_id" class="form-select" required>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= (int)$data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>>
                            <?= e($y['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Grade <span class="text-danger">*</span></label>
                <select name="grade_id" id="gradeSelect" class="form-select" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= (int)$data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Section <span class="text-danger">*</span></label>
                <select name="section_id" id="sectionSelect" class="form-select" required>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>" data-grade="<?= (int)$sec['grade_id'] ?>"
                                <?= (int)$data['section_id'] === (int)$sec['id'] ? 'selected' : '' ?>>
                            <?= e($sec['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Subject <span class="text-danger">*</span></label>
                <select name="subject_id" id="subjectSelect" class="form-select" required>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= (int)$sub['id'] ?>" data-grade="<?= (int)$sub['grade_id'] ?>"
                                <?= (int)$data['subject_id'] === (int)$sub['id'] ? 'selected' : '' ?>>
                            <?= e($sub['code']) ?> — <?= e($sub['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_class_teacher" name="is_class_teacher" value="1"
                   <?= (int)$data['is_class_teacher'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_class_teacher">Class teacher</label>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Changes</button>
            <a href="<?= e(url('admin/teacher-assignments/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const gradeSel = document.getElementById('gradeSelect');
    const secSel   = document.getElementById('sectionSelect');
    const subjectSel = document.getElementById('subjectSelect');
    function filterSelect(sel) {
        const gid = gradeSel.value;
        Array.from(sel.options).forEach(opt => {
            const g = opt.getAttribute('data-grade');
            opt.hidden = gid !== '0' && g !== gid;
        });
    }
    gradeSel.addEventListener('change', () => { filterSelect(secSel); filterSelect(subjectSel); });
    filterSelect(secSel);
    filterSelect(subjectSel);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>