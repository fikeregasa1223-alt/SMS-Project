<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_role('admin');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM sections WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Section not found.'); redirect(url('admin/sections/index.php')); }

$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$errors = [];
$data   = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['grade_id'] = (int) post('grade_id', 0);
    $data['name']     = clean((string) post('name', ''));
    $data['capacity'] = (int) post('capacity', 50);
    $data['room']     = clean((string) post('room', ''));
    $data['status']   = (string) post('status', 'active');

    if ($data['grade_id'] <= 0)     $errors['grade_id'] = 'Grade is required.';
    if (!v_required($data['name'])) $errors['name']     = 'Section name is required.';
    if ($data['capacity'] < 1)      $errors['capacity'] = 'Capacity must be at least 1.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM sections WHERE grade_id = ? AND name = ? AND id <> ?",
                               [$data['grade_id'], $data['name'], $id]);
        if ($dup) $errors['name'] = 'This section already exists for the selected grade.';
    }

    if (!$errors) {
        Database::update('sections', [
            'grade_id' => $data['grade_id'],
            'name'     => $data['name'],
            'capacity' => $data['capacity'],
            'room'     => $data['room'],
            'status'   => $data['status'],
        ], 'id = :id', ['id' => $id]);
        audit((int)current_user()['id'], 'section_updated', 'sections', $id, 'Section updated: ' . $data['name']);
        flash_set('success', 'Section updated successfully.');
        redirect(url('admin/sections/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit Section';
$pageDescription = 'Update section details.';
$breadcrumbs     = [['label' => 'Sections', 'url' => 'admin/sections/index.php'], ['label' => 'Edit']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <?php
            $opts = '';
            foreach ($grades as $g) {
                $sel   = (int)$data['grade_id'] === (int)$g['id'] ? ' selected' : '';
                $opts .= '<option value="' . (int)$g['id'] . '"' . $sel . '>' . e($g['name']) . '</option>';
            }
            echo ui_field('grade_id', 'Grade', '<select name="grade_id" id="grade_id" class="form-select">' . $opts . '</select>', $errors['grade_id'] ?? null, null, true);
        ?>

        <div class="row">
            <div class="col-md-6"><?= ui_field('name', 'Section Name', ui_input('name', $data['name'], 'text', ['required' => 'required']), $errors['name'] ?? null, null, true) ?></div>
            <div class="col-md-6"><?= ui_field('room', 'Room', ui_input('room', $data['room'], 'text'), $errors['room'] ?? null) ?></div>
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
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Changes</button>
            <a href="<?= e(url('admin/sections/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>