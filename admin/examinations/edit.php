<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_marks');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM examinations WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Examination not found.'); redirect(url('admin/examinations/index.php')); }

$errors = [];
$data   = $row;

$years  = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$terms  = Database::fetchAll("SELECT id, academic_year_id, name FROM terms ORDER BY sequence ASC");
$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['name']             = clean((string) post('name', ''));
    $data['type']             = (string) post('type', 'midterm');
    $data['academic_year_id'] = (int) post('academic_year_id', 0);
    $data['term_id']          = (int) post('term_id', 0);
    $data['grade_id']         = (int) post('grade_id', 0);
    $data['start_date']       = clean((string) post('start_date', ''));
    $data['end_date']         = clean((string) post('end_date', ''));
    $data['max_mark']         = clean((string) post('max_mark', '100'));
    $data['pass_mark']        = clean((string) post('pass_mark', '50'));
    $data['weight']           = clean((string) post('weight', '100'));
    $data['status']           = (string) post('status', 'draft');

    if (!v_required($data['name'])) $errors['name'] = 'Name is required.';
    if (!v_in_array($data['type'], exam_type_list())) $errors['type'] = 'Invalid type.';
    if (!v_in_array($data['status'], exam_status_list())) $errors['status'] = 'Invalid status.';
    if ($data['academic_year_id'] <= 0) $errors['academic_year_id'] = 'Year required.';
    if ($data['term_id'] <= 0) $errors['term_id'] = 'Term required.';
    if ($data['grade_id'] <= 0) $errors['grade_id'] = 'Grade required.';

    if (!$errors) {
        Database::update('examinations', [
            'name'             => $data['name'],
            'type'             => $data['type'],
            'academic_year_id' => $data['academic_year_id'],
            'term_id'          => $data['term_id'],
            'grade_id'         => $data['grade_id'],
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'] ?: null,
            'max_mark'         => (float)$data['max_mark'],
            'pass_mark'        => (float)$data['pass_mark'],
            'weight'           => (float)$data['weight'],
            'status'           => $data['status'],
        ], 'id = :id', ['id' => $id]);

        audit((int)current_user()['id'], 'examination_updated', 'examinations', $id, 'Examination updated: ' . $data['name']);
        flash_set('success', 'Examination updated.');
        redirect(url('admin/examinations/show.php?id=' . $id));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit Examination';
$pageDescription = 'Update examination details.';
$breadcrumbs     = [
    ['label' => 'Examinations', 'url' => 'admin/examinations/index.php'],
    ['label' => $data['name'], 'url' => 'admin/examinations/show.php?id=' . $id],
    ['label' => 'Edit'],
];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Examination Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($data['name']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required>
                    <?php foreach (exam_type_list() as $ty): ?>
                        <option value="<?= e($ty) ?>" <?= $data['type'] === $ty ? 'selected' : '' ?>><?= e(exam_type_label($ty)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select" required>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= (int)$data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select" required>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (int)$data['term_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Grade</label>
                <select name="grade_id" class="form-select" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= (int)$data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($data['start_date']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e((string)$data['end_date']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Max Mark</label>
                <input type="number" step="0.01" min="1" name="max_mark" class="form-control" value="<?= e((string)$data['max_mark']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Pass Mark</label>
                <input type="number" step="0.01" min="0" name="pass_mark" class="form-control" value="<?= e((string)$data['pass_mark']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Weight %</label>
                <input type="number" step="0.01" min="1" max="100" name="weight" class="form-control" value="<?= e((string)$data['weight']) ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (exam_status_list() as $st): ?>
                    <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Changes</button>
            <a href="<?= e(url('admin/examinations/show.php?id=' . $id)) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>