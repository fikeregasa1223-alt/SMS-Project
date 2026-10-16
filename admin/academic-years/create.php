<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_role('admin');

$errors = [];
$data   = ['name' => '', 'start_date' => '', 'end_date' => '', 'is_current' => 0, 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    $data['name']       = clean((string) post('name', ''));
    $data['start_date'] = clean((string) post('start_date', ''));
    $data['end_date']   = clean((string) post('end_date', ''));
    $data['is_current'] = (int) post('is_current', 0) === 1 ? 1 : 0;
    $data['status']     = (string) post('status', 'active');

    if (!v_required($data['name']))       $errors['name']       = 'Name is required.';
    if (!v_date($data['start_date']))     $errors['start_date'] = 'Valid start date is required.';
    if (!v_date($data['end_date']))       $errors['end_date']   = 'Valid end date is required.';
    if (!$errors && $data['end_date'] <= $data['start_date']) $errors['end_date'] = 'End date must be after start date.';

    if (!$errors) {
        $exists = Database::fetch("SELECT id FROM academic_years WHERE name = ?", [$data['name']]);
        if ($exists) $errors['name'] = 'This academic year already exists.';
    }

    if (!$errors) {
        Database::begin();
        try {
            if ($data['is_current'] === 1) {
                Database::conn()->exec("UPDATE academic_years SET is_current = 0");
            }
            $id = Database::insert('academic_years', $data);
            audit((int)current_user()['id'], 'academic_year_created', 'academic_years', $id, 'Academic year created: ' . $data['name']);
            Database::commit();
            flash_set('success', 'Academic year created successfully.');
            redirect(url('admin/academic-years/index.php'));
        } catch (Throwable $e) {
            Database::rollback();
            $errors['form'] = 'Failed to create academic year. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }

    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Academic Year';
$pageDescription = 'Create a new academic year.';
$breadcrumbs     = [['label' => 'Academic Years', 'url' => 'admin/academic-years/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/ui.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="page-desc"><?= e($pageDescription) ?></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>

                    <?= ui_field('name', 'Name', ui_input('name', $data['name'], 'text', [
                        'placeholder' => 'e.g. 2025/2026', 'required' => 'required'
                    ]), $errors['name'] ?? null, 'Format: YYYY/YYYY', true) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= ui_field('start_date', 'Start Date', ui_input('start_date', $data['start_date'], 'date', ['required' => 'required']), $errors['start_date'] ?? null, null, true) ?>
                        </div>
                        <div class="col-md-6">
                            <?= ui_field('end_date', 'End Date', ui_input('end_date', $data['end_date'], 'date', ['required' => 'required']), $errors['end_date'] ?? null, null, true) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= ui_field('status', 'Status',
                                '<select name="status" id="status" class="form-select">'
                              . '<option value="active"'   . ($data['status'] === 'active'   ? ' selected' : '') . '>Active</option>'
                              . '<option value="archived"' . ($data['status'] === 'archived' ? ' selected' : '') . '>Archived</option>'
                              . '</select>', $errors['status'] ?? null) ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Set as current</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_current" name="is_current" value="1" <?= $data['is_current'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_current">Mark as current academic year</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
                        <a href="<?= e(url('admin/academic-years/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>