<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_students');

$preselectedStudent = (int) ($_GET['student_id'] ?? 0);
$errors = [];
$data = [
    'student_id'       => $preselectedStudent,
    'academic_year_id' => 0,
    'grade_id'         => 0,
    'section_id'       => 0,
    'roll_no'          => '',
    'enrollment_date'  => date('Y-m-d'),
    'status'           => 'active',
];

$students  = Database::fetchAll("SELECT id, student_code, first_name, middle_name, last_name FROM students ORDER BY first_name, last_name");
$years     = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$grades    = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections  = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['student_id']       = (int) post('student_id', 0);
    $data['academic_year_id'] = (int) post('academic_year_id', 0);
    $data['grade_id']         = (int) post('grade_id', 0);
    $data['section_id']       = (int) post('section_id', 0);
    $data['roll_no']          = clean((string) post('roll_no', ''));
    $data['enrollment_date']  = clean((string) post('enrollment_date', ''));
    $data['status']           = (string) post('status', 'active');

    if ($data['student_id'] <= 0)       $errors['student_id']       = 'Student is required.';
    if ($data['academic_year_id'] <= 0) $errors['academic_year_id'] = 'Academic year is required.';
    if ($data['grade_id'] <= 0)         $errors['grade_id']         = 'Grade is required.';
    if ($data['section_id'] <= 0)       $errors['section_id']       = 'Section is required.';
    if (!v_date($data['enrollment_date'])) $errors['enrollment_date'] = 'Valid date required.';

    if (!$errors) {
        $dup = Database::fetch(
            "SELECT id FROM enrollments WHERE student_id = ? AND academic_year_id = ?",
            [$data['student_id'], $data['academic_year_id']]
        );
        if ($dup) $errors['student_id'] = 'This student is already enrolled for the selected academic year.';
    }

    if (!$errors) {
        $id = Database::insert('enrollments', [
            'student_id'       => $data['student_id'],
            'academic_year_id' => $data['academic_year_id'],
            'grade_id'         => $data['grade_id'],
            'section_id'       => $data['section_id'],
            'roll_no'          => $data['roll_no'] !== '' ? (int)$data['roll_no'] : null,
            'enrollment_date'  => $data['enrollment_date'],
            'status'           => $data['status'],
        ]);
        audit((int)current_user()['id'], 'enrollment_created', 'enrollments', $id, 'Student enrolled');
        flash_set('success', 'Enrollment created successfully.');
        redirect(url('admin/enrollments/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Enrollment';
$pageDescription = 'Enroll a student.';
$breadcrumbs     = [['label' => 'Enrollments', 'url' => 'admin/enrollments/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Student <span class="text-danger">*</span></label>
            <select name="student_id" class="form-select" required>
                <option value="0">— Select student —</option>
                <?php foreach ($students as $st):
                    $nm = trim(($st['first_name'] ?? '') . ' ' . ($st['middle_name'] ?? '') . ' ' . ($st['last_name'] ?? ''));
                ?>
                    <option value="<?= (int)$st['id'] ?>" <?= $data['student_id'] === (int)$st['id'] ? 'selected' : '' ?>>
                        <?= e($st['student_code']) ?> — <?= e($nm) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="0">— Select year —</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= $data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>>
                            <?= e($y['name']) ?><?= (int)$y['is_current'] ? ' (Current)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Grade <span class="text-danger">*</span></label>
                <select name="grade_id" id="gradeSelect" class="form-select" required>
                    <option value="0">— Select grade —</option>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= $data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Section <span class="text-danger">*</span></label>
                <select name="section_id" id="sectionSelect" class="form-select" required>
                    <option value="0">— Select section —</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>" data-grade="<?= (int)$sec['grade_id'] ?>"
                                <?= $data['section_id'] === (int)$sec['id'] ? 'selected' : '' ?>>
                            <?= e($sec['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Roll No</label>
                <input type="number" name="roll_no" class="form-control" value="<?= e($data['roll_no']) ?>" min="1">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['active','completed','transferred','withdrawn'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Enrollment Date <span class="text-danger">*</span></label>
            <input type="date" name="enrollment_date" class="form-control" value="<?= e($data['enrollment_date']) ?>" required>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Enroll</button>
            <a href="<?= e(url('admin/enrollments/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<script>
// Filter sections by selected grade
document.addEventListener('DOMContentLoaded', () => {
    const gradeSel = document.getElementById('gradeSelect');
    const secSel   = document.getElementById('sectionSelect');
    if (!gradeSel || !secSel) return;

    function filter() {
        const gid = gradeSel.value;
        Array.from(secSel.options).forEach(opt => {
            if (opt.value === '0') return;
            const g = opt.getAttribute('data-grade');
            opt.hidden = gid !== '0' && g !== gid;
        });
        // If current selection hidden, reset
        if (secSel.selectedOptions[0] && secSel.selectedOptions[0].hidden) {
            secSel.value = '0';
        }
    }
    gradeSel.addEventListener('change', filter);
    filter();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>