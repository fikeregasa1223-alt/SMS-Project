<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_role('admin');

$grades = Database::fetchAll("SELECT id, name FROM grades WHERE status='active' ORDER BY level ASC");
$errors = [];
$data   = ['code' => '', 'name' => '', 'grade_id' => 0, 'credit' => '1.0', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['code']     = strtoupper(clean((string) post('code', '')));
    $data['name']     = clean((string) post('name', ''));
    $data['grade_id'] = (int) post('grade_id', 0);
    $data['credit']   = (string) post('credit', '1.0');
    $data['status']   = (string) post('status', 'active');

    if (!v_required($data['code']))   $errors['code']     = 'Code is required.';
    if (!v_required($data['name']))   $errors['name']     = 'Name is required.';
    if ($data['grade_id'] <= 0)       $errors['grade_id'] = 'Grade is required.';
    if ((float)$data['credit'] <= 0)  $errors['credit']   = 'Credit must be > 0.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM subjects WHERE code = ?", [$data['code']]);
        if ($dup) $errors['code'] = 'This subject code already exists.';
    }

    if (!$errors) {
        $id = Database::insert('subjects', [
            'code'     => $data['code'],
            'name'     => $data['name'],
            'grade_id' => $data['grade_id'],
            'credit'   => (float)$data['credit'],
            'status'   => $data['status'],
        ]);
        audit((int)current_user()['id'], 'subject_created', 'subjects', $id, 'Subject created: ' . $data['name']);
        flash_set('success', 'Subject created successfully.');
        redirect(url('admin/subjects/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Subject';
$pageDescription = 'Create a new subject.';
$breadcrumbs     = [['label' => 'Subjects', 'url' => 'admin/subjects/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="row">
            <div class="col-md-4"><?= ui_field('code', 'Code', ui_input('code', $data['code'], 'text', ['placeholder' => 'MATH9', 'required' => 'required']), $errors['code'] ?? null, 'Uppercase letters/numbers', true) ?></div>
            <div class="col-md-8"><?= ui_field('name', 'Name', ui_input('name', $data['name'], 'text', ['placeholder' => 'Mathematics', 'required' => 'required']), $errors['name'] ?? null, null, true) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?php
                    $opts = '<option value="0">— Select grade —</option>';
                    foreach ($grades as $g) {
                        $sel   = (int)$data['grade_id'] === (int)$g['id'] ? ' selected' : '';
                        $opts .= '<option value="' . (int)$g['id'] . '"' . $sel . '>' . e($g['name']) . '</option>';
                    }
                    echo ui_field('grade_id', 'Grade', '<select name="grade_id" id="grade_id" class="form-select">' . $opts . '</select>', $errors['grade_id'] ?? null, null, true);
                ?>
            </div>
            <div class="col-md-3"><?= ui_field('credit', 'Credit', ui_input('credit', $data['credit'], 'number', ['step' => '0.5', 'min' => '0.5', 'max' => '10', 'required' => 'required']), $errors['credit'] ?? null, null, true) ?></div>
            <div class="col-md-3">
                <?= ui_field('status', 'Status',
                    '<select name="status" id="status" class="form-select">'
                  . '<option value="active"'   . ($data['status'] === 'active'   ? ' selected' : '') . '>Active</option>'
                  . '<option value="inactive"' . ($data['status'] === 'inactive' ? ' selected' : '') . '>Inactive</option>'
                  . '</select>') ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/subjects/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>