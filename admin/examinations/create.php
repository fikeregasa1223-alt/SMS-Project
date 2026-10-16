<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('enter_marks');

$errors = [];
$data = [
    'name'             => '',
    'type'             => 'midterm',
    'academic_year_id' => 0,
    'term_id'          => 0,
    'grade_id'         => 0,
    'start_date'       => date('Y-m-d'),
    'end_date'         => '',
    'max_mark'         => '100',
    'pass_mark'        => '50',
    'weight'           => '100',
    'status'           => 'draft',
];

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

    if (!v_required($data['name']))      $errors['name']             = 'Name is required.';
    if (!v_in_array($data['type'], exam_type_list()))    $errors['type']   = 'Invalid type.';
    if (!v_in_array($data['status'], exam_status_list())) $errors['status'] = 'Invalid status.';
    if ($data['academic_year_id'] <= 0)  $errors['academic_year_id'] = 'Academic year required.';
    if ($data['term_id'] <= 0)           $errors['term_id']          = 'Term required.';
    if ($data['grade_id'] <= 0)          $errors['grade_id']         = 'Grade required.';
    if (!v_date($data['start_date']))    $errors['start_date']       = 'Valid start date required.';
    if ($data['end_date'] !== '' && !v_date($data['end_date'])) $errors['end_date'] = 'Invalid end date.';
    if (!is_numeric($data['max_mark'])  || (float)$data['max_mark']  <= 0) $errors['max_mark']  = 'Max mark must be > 0.';
    if (!is_numeric($data['pass_mark']) || (float)$data['pass_mark'] < 0)  $errors['pass_mark'] = 'Pass mark must be ≥ 0.';
    if (!is_numeric($data['weight'])    || (float)$data['weight']    <= 0) $errors['weight']    = 'Weight must be > 0.';

    if (!$errors) {
        $id = Database::insert('examinations', [
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
            'created_by'       => (int) current_user()['id'],
        ]);
        audit((int)current_user()['id'], 'examination_created', 'examinations', $id, 'Examination created: ' . $data['name']);
        flash_set('success', 'Examination created successfully. Now add subjects.');
        redirect(url('admin/examinations/subjects.php?id=' . $id));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Examination';
$pageDescription = 'Create an examination for a grade and term.';
$breadcrumbs     = [['label' => 'Examinations', 'url' => 'admin/examinations/index.php'], ['label' => 'New']];
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
                <input type="text" name="name" class="form-control" value="<?= e($data['name']) ?>"
                       placeholder="e.g. Midterm Examination — Term 1" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required>
                    <?php foreach (exam_type_list() as $ty): ?>
                        <option value="<?= e($ty) ?>" <?= $data['type'] === $ty ? 'selected' : '' ?>>
                            <?= e(exam_type_label($ty)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <select name="academic_year_id" id="aySelect" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= $data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>>
                            <?= e($y['name']) ?><?= (int)$y['is_current'] ? ' (Current)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Term <span class="text-danger">*</span></label>
                <select name="term_id" id="termSelect" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" data-year="<?= (int)$t['academic_year_id'] ?>"
                                <?= $data['term_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= e($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Grade <span class="text-danger">*</span></label>
                <select name="grade_id" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= $data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="<?= e($data['start_date']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($data['end_date']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Max Mark <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="1" name="max_mark" class="form-control" value="<?= e($data['max_mark']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Pass Mark <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="pass_mark" class="form-control" value="<?= e($data['pass_mark']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Weight % <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="1" max="100" name="weight" class="form-control" value="<?= e($data['weight']) ?>" required>
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
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create & Continue</button>
            <a href="<?= e(url('admin/examinations/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const aySel   = document.getElementById('aySelect');
    const termSel = document.getElementById('termSelect');
    if (!aySel || !termSel) return;

    function filter() {
        const aid = aySel.value;
        Array.from(termSel.options).forEach(opt => {
            if (opt.value === '0') return;
            const y = opt.getAttribute('data-year');
            opt.hidden = aid !== '0' && y !== aid;
        });
        if (termSel.selectedOptions[0] && termSel.selectedOptions[0].hidden) termSel.value = '0';
    }
    aySel.addEventListener('change', filter);
    filter();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>