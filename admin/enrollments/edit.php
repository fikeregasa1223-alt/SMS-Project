<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_students');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM enrollments WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Enrollment not found.'); redirect(url('admin/enrollments/index.php')); }

$errors = [];
$data   = $row;

$years    = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['academic_year_id'] = (int) post('academic_year_id', 0);
    $data['grade_id']         = (int) post('grade_id', 0);
    $data['section_id']       = (int) post('section_id', 0);
    $data['roll_no']          = clean((string) post('roll_no', ''));
    $data['enrollment_date']  = clean((string) post('enrollment_date', ''));
    $data['status']           = (string) post('status', 'active');

    if ($data['academic_year_id'] <= 0) $errors['academic_year_id'] = 'Academic year is required.';
    if ($data['grade_id'] <= 0)         $errors['grade_id']         = 'Grade is required.';
    if ($data['section_id'] <= 0)       $errors['section_id']       = 'Section is required.';
    if (!v_date($data['enrollment_date'])) $errors['enrollment_date'] = 'Valid date required.';

    if (!$errors) {
        Database::update('enrollments', [
            'academic_year_id' => $data['academic_year_id'],
            'grade_id'         => $data['grade_id'],
            'section_id'       => $data['section_id'],
            'roll_no'          => $data['roll_no'] !== '' ? (int)$data['roll_no'] : null,
            'enrollment_date'  => $data['enrollment_date'],
            'status'           => $data['status'],
        ], 'id = :id', ['id' => $id]);
        audit((int)current_user()['id'], 'enrollment_updated', 'enrollments', $id, 'Enrollment updated');
        flash_set('success', 'Enrollment updated.');
        redirect(url('admin/enrollments/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit Enrollment';
$pageDescription = 'Update enrollment.';
$breadcrumbs     = [['label' => 'Enrollments', 'url' => 'admin/enrollments/index.php'], ['label' => 'Edit']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <select name="academic_year_id" class="form-select" required>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= (int)$data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>>
                            <?= e($y['name']) ?><?= (int)$y['is_current'] ? ' (Current)' : '' ?>
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
            <div class="col-md-3 mb-3">
                <label class="form-label">Roll No</label>
                <input type="number" name="roll_no" class="form-control" value="<?= e((string)($data['roll_no'] ?? '')) ?>" min="1">
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
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button>
            <a href="<?= e(url('admin/enrollments/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const gradeSel = document.getElementById('gradeSelect');
    const secSel   = document.getElementById('sectionSelect');
    if (!gradeSel || !secSel) return;
    function filter() {
        const gid = gradeSel.value;
        Array.from(secSel.options).forEach(opt => {
            const g = opt.getAttribute('data-grade');
            opt.hidden = gid !== '0' && g !== gid;
        });
    }
    gradeSel.addEventListener('change', filter);
    filter();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>