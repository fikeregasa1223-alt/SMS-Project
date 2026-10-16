<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_role('admin');

$errors = [];
$data   = ['name' => '', 'level' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['name']   = clean((string) post('name', ''));
    $data['level']  = (int) post('level', 0);
    $data['status'] = (string) post('status', 'active');

    if (!v_required($data['name'])) $errors['name']  = 'Name is required.';
    if ($data['level'] <= 0 || $data['level'] > 20) $errors['level'] = 'Level must be between 1 and 20.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM grades WHERE level = ?", [$data['level']]);
        if ($dup) $errors['level'] = 'This level already exists.';
    }

    if (!$errors) {
        $id = Database::insert('grades', $data);
        audit((int)current_user()['id'], 'grade_created', 'grades', $id, 'Grade created: ' . $data['name']);
        flash_set('success', 'Grade created successfully.');
        redirect(url('admin/grades/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Grade';
$pageDescription = 'Add a new grade level.';
$breadcrumbs     = [['label' => 'Grades', 'url' => 'admin/grades/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-6"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <?= ui_field('name',  'Name',  ui_input('name',  $data['name'],  'text', ['placeholder' => 'e.g. Grade 9', 'required' => 'required']), $errors['name']  ?? null, null, true) ?>
        <?= ui_field('level', 'Level', ui_input('level', (string)$data['level'], 'number', ['min' => 1, 'max' => 20, 'placeholder' => '9', 'required' => 'required']), $errors['level'] ?? null, 'Numeric level, e.g. 9, 10, 11, 12', true) ?>

        <?= ui_field('status', 'Status',
            '<select name="status" id="status" class="form-select">'
          . '<option value="active"'   . ($data['status'] === 'active'   ? ' selected' : '') . '>Active</option>'
          . '<option value="inactive"' . ($data['status'] === 'inactive' ? ' selected' : '') . '>Inactive</option>'
          . '</select>') ?>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/grades/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>