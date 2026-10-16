<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_role('admin');

$grades = Database::fetchAll("SELECT id, name FROM grades WHERE status='active' ORDER BY level ASC");
$errors = [];
$data   = ['grade_id' => 0, 'name' => '', 'capacity' => 50, 'room' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['grade_id'] = (int) post('grade_id', 0);
    $data['name']     = clean((string) post('name', ''));
    $data['capacity'] = (int) post('capacity', 50);
    $data['room']     = clean((string) post('room', ''));
    $data['status']   = (string) post('status', 'active');

    if ($data['grade_id'] <= 0)       $errors['grade_id'] = 'Grade is required.';
    if (!v_required($data['name']))   $errors['name']     = 'Section name is required.';
    if ($data['capacity'] < 1)        $errors['capacity'] = 'Capacity must be at least 1.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM sections WHERE grade_id = ? AND name = ?",
                               [$data['grade_id'], $data['name']]);
        if ($dup) $errors['name'] = 'This section already exists for the selected grade.';
    }

    if (!$errors) {
        $id = Database::insert('sections', $data);
        audit((int)current_user()['id'], 'section_created', 'sections', $id, 'Section created: ' . $data['name']);
        flash_set('success', 'Section created successfully.');
        redirect(url('admin/sections/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Section';
$pageDescription = 'Create a class section.';
$breadcrumbs     = [['label' => 'Sections', 'url' => 'admin/sections/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <?php
            $opts = '<option value="0">— Select grade —</option>';
            foreach ($grades as $g) {
                $sel   = (int)$data['grade_id'] === (int)$g['id'] ? ' selected' : '';
                $opts .= '<option value="' . (int)$g['id'] . '"' . $sel . '>' . e($g['name']) . '</option>';
            }
            echo ui_field('grade_id', 'Grade', '<select name="grade_id" id="grade_id" class="form-select">' . $opts . '</select>', $errors['grade_id'] ?? null, null, true);
        ?>

        <div class="row">
            <div class="col-md-6"><?= ui_field('name', 'Section Name', ui_input('name', $data['name'], 'text', ['placeholder' => 'e.g. A', 'required' => 'required']), $errors['name'] ?? null, null, true) ?></div>
            <div class="col-md-6"><?= ui_field('room', 'Room', ui_input('room', $data['room'], 'text', ['placeholder' => 'e.g. 101']), $errors['room'] ?? null) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= ui_field('capacity', 'Capacity', ui_input('capacity', (string)$data['capacity'], 'number', ['min' => 1, 'max' => 200, 'required' => 'required']), $errors['capacity'] ?? null, null, true) ?></div>
            <div class="col-md-6">
                <?= ui_field('status', 'Status',
                    '<select name="status" id="status" class="form-select">'
                  . '<option value="active"'   . ($data['status'] === 'active'   ? ' selected' : '') . '>Active</option>'
                  . '<option value="inactive"' . ($data['status'] === 'inactive' ? ' selected' : '') . '>Inactive</option>'
                  . '</select>') ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/sections/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>