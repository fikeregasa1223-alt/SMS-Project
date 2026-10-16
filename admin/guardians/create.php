<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_students');

$errors = [];
$data = ['full_name'=>'','relationship'=>'father','phone'=>'','email'=>'','address'=>'','occupation'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach ($data as $k => $_) $data[$k] = clean((string) post($k, $data[$k]));
    $data['relationship'] = (string) post('relationship', 'father');

    if (!v_required($data['full_name'])) $errors['full_name'] = 'Name is required.';
    if (!v_required($data['phone']))     $errors['phone']     = 'Phone is required.';
    if (!v_in_array($data['relationship'], guardian_relationship_list())) $errors['relationship'] = 'Invalid relationship.';
    if ($data['email'] !== '' && !v_email($data['email'])) $errors['email'] = 'Invalid email.';

    if (!$errors) {
        $id = Database::insert('guardians', [
            'full_name'    => $data['full_name'],
            'relationship' => $data['relationship'],
            'phone'        => $data['phone'],
            'email'        => $data['email'] ?: null,
            'address'      => $data['address'] ?: null,
            'occupation'   => $data['occupation'] ?: null,
        ]);
        audit((int)current_user()['id'], 'guardian_created', 'guardians', $id, 'Guardian created: ' . $data['full_name']);
        flash_set('success', 'Guardian created.');
        redirect(url('admin/guardians/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Guardian';
$pageDescription = 'Add a new guardian record.';
$breadcrumbs     = [['label' => 'Guardians', 'url' => 'admin/guardians/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" value="<?= e($data['full_name']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Relationship <span class="text-danger">*</span></label>
                <select name="relationship" class="form-select" required>
                    <?php foreach (guardian_relationship_list() as $r): ?>
                        <option value="<?= e($r) ?>" <?= $data['relationship'] === $r ? 'selected' : '' ?>>
                            <?= e(ucfirst(str_replace('_', ' ', $r))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= e($data['address']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Occupation</label>
                <input type="text" name="occupation" class="form-control" value="<?= e($data['occupation']) ?>">
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/guardians/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>