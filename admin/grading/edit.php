<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_marks');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM grading_rules WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Rule not found.'); redirect(url('admin/grading/index.php')); }

$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$errors = [];
$data   = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['grade_id']  = (int) post('grade_id', 0);
    $data['min_score'] = clean((string) post('min_score', ''));
    $data['max_score'] = clean((string) post('max_score', ''));
    $data['letter']    = strtoupper(clean((string) post('letter', '')));
    $data['points']    = clean((string) post('points', ''));
    $data['remark']    = clean((string) post('remark', ''));
    $data['is_active'] = (int) post('is_active', 1) === 1 ? 1 : 0;

    if (!is_numeric($data['min_score'])) $errors['min_score'] = 'Numeric required.';
    if (!is_numeric($data['max_score'])) $errors['max_score'] = 'Numeric required.';
    if (!v_required($data['letter']))    $errors['letter'] = 'Letter required.';
    if (!is_numeric($data['points']))    $errors['points'] = 'Points required.';

    if (!$errors) {
        Database::update('grading_rules', [
            'grade_id'  => $data['grade_id'] > 0 ? $data['grade_id'] : null,
            'min_score' => (float)$data['min_score'],
            'max_score' => (float)$data['max_score'],
            'letter'    => $data['letter'],
            'points'    => (float)$data['points'],
            'remark'    => $data['remark'] ?: null,
            'is_active' => $data['is_active'],
        ], 'id = :id', ['id' => $id]);
        audit((int)current_user()['id'], 'grading_rule_updated', 'grading_rules', $id, 'Rule updated');
        flash_set('success', 'Grading rule updated.');
        redirect(url('admin/grading/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit Grading Rule';
$pageDescription = 'Update grading boundary.';
$breadcrumbs     = [['label' => 'Grading Rules', 'url' => 'admin/grading/index.php'], ['label' => 'Edit']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Grade</label>
            <select name="grade_id" class="form-select">
                <option value="0">Global</option>
                <?php foreach ($grades as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= (int)$data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Min Score %</label>
                <input type="number" step="0.01" name="min_score" class="form-control" value="<?= e((string)$data['min_score']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Max Score %</label>
                <input type="number" step="0.01" name="max_score" class="form-control" value="<?= e((string)$data['max_score']) ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Letter</label>
                <input type="text" name="letter" class="form-control" value="<?= e($data['letter']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Points</label>
                <input type="number" step="0.01" name="points" class="form-control" value="<?= e((string)$data['points']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Remark</label>
                <input type="text" name="remark" class="form-control" value="<?= e((string)$data['remark']) ?>">
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (int)$data['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Active</label>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button>
            <a href="<?= e(url('admin/grading/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>