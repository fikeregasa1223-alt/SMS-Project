<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_marks');

$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$errors = [];
$data   = ['grade_id'=>0,'min_score'=>'','max_score'=>'','letter'=>'','points'=>'','remark'=>'','is_active'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['grade_id']  = (int) post('grade_id', 0);
    $data['min_score'] = clean((string) post('min_score', ''));
    $data['max_score'] = clean((string) post('max_score', ''));
    $data['letter']    = strtoupper(clean((string) post('letter', '')));
    $data['points']    = clean((string) post('points', ''));
    $data['remark']    = clean((string) post('remark', ''));
    $data['is_active'] = (int) post('is_active', 1) === 1 ? 1 : 0;

    if (!is_numeric($data['min_score'])) $errors['min_score'] = 'Min score must be numeric.';
    if (!is_numeric($data['max_score'])) $errors['max_score'] = 'Max score must be numeric.';
    if ($data['min_score'] !== '' && $data['max_score'] !== '' && (float)$data['min_score'] > (float)$data['max_score'])
        $errors['min_score'] = 'Min cannot exceed Max.';
    if (!v_required($data['letter']))  $errors['letter'] = 'Letter is required.';
    if (!is_numeric($data['points']))  $errors['points'] = 'Points must be numeric.';

    if (!$errors) {
        $gradeId = $data['grade_id'] > 0 ? $data['grade_id'] : null;
        $id = Database::insert('grading_rules', [
            'grade_id'  => $gradeId,
            'min_score' => (float)$data['min_score'],
            'max_score' => (float)$data['max_score'],
            'letter'    => $data['letter'],
            'points'    => (float)$data['points'],
            'remark'    => $data['remark'] ?: null,
            'is_active' => $data['is_active'],
        ]);
        audit((int)current_user()['id'], 'grading_rule_created', 'grading_rules', $id, 'Rule created: ' . $data['letter']);
        flash_set('success', 'Grading rule created.');
        redirect(url('admin/grading/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Grading Rule';
$pageDescription = 'Add a grade boundary.';
$breadcrumbs     = [['label' => 'Grading Rules', 'url' => 'admin/grading/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Grade (optional)</label>
            <select name="grade_id" class="form-select">
                <option value="0">Global (all grades)</option>
                <?php foreach ($grades as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= $data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Min Score % <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="100" name="min_score" class="form-control" value="<?= e($data['min_score']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Max Score % <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="100" name="max_score" class="form-control" value="<?= e($data['max_score']) ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Letter <span class="text-danger">*</span></label>
                <input type="text" name="letter" class="form-control" value="<?= e($data['letter']) ?>" placeholder="A+" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Grade Points <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="5" name="points" class="form-control" value="<?= e($data['points']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Remark</label>
                <input type="text" name="remark" class="form-control" value="<?= e($data['remark']) ?>" placeholder="Excellent">
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Active</label>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/grading/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>