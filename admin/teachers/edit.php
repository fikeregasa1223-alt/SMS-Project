<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_teachers');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM teachers WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Teacher not found.'); redirect(url('admin/teachers/index.php')); }

$errors = [];
$data   = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    foreach (['first_name','middle_name','last_name','phone','email','address','qualification','specialization','date_of_birth','hire_date'] as $k) {
        $data[$k] = clean((string) post($k, $data[$k] ?? ''));
    }
    $data['gender']            = (string) post('gender', 'male');
    $data['employment_status'] = (string) post('employment_status', 'full_time');

    if (!v_required($data['first_name'])) $errors['first_name'] = 'First name is required.';
    if (!v_required($data['last_name']))  $errors['last_name']  = 'Last name is required.';
    if (!v_in_array($data['gender'], gender_list())) $errors['gender'] = 'Invalid gender.';
    if (!v_in_array($data['employment_status'], employment_status_list())) $errors['employment_status'] = 'Invalid employment status.';
    if ($data['email'] !== '' && !v_email($data['email'])) $errors['email'] = 'Invalid email.';
    if ($data['phone'] !== '' && !v_phone($data['phone'])) $errors['phone'] = 'Invalid phone.';

    $newPhoto    = null;
    $removePhoto = (int) post('remove_photo', 0) === 1;

    if (!empty($_FILES['photo']['name'])) {
        $up = upload_image($_FILES['photo'], 'profiles', 'teacher');
        if (!$up['ok']) $errors['photo'] = $up['message'];
        else            $newPhoto = $up['filename'];
    }

    if (!$errors) {
        Database::begin();
        try {
            $photo = $data['photo'] ?? null;
            if ($newPhoto) {
                if (!empty($row['photo'])) delete_upload($row['photo'], 'profiles');
                $photo = $newPhoto;
            } elseif ($removePhoto) {
                if (!empty($row['photo'])) delete_upload($row['photo'], 'profiles');
                $photo = null;
            }

            Database::update('teachers', [
                'first_name'        => $data['first_name'],
                'middle_name'       => $data['middle_name'] ?: null,
                'last_name'         => $data['last_name'],
                'gender'            => $data['gender'],
                'date_of_birth'     => $data['date_of_birth'] ?: null,
                'phone'             => $data['phone'] ?: null,
                'email'             => $data['email'] ?: null,
                'address'           => $data['address'] ?: null,
                'qualification'     => $data['qualification'] ?: null,
                'specialization'    => $data['specialization'] ?: null,
                'hire_date'         => $data['hire_date'] ?: null,
                'employment_status' => $data['employment_status'],
                'photo'             => $photo,
            ], 'id = :id', ['id' => $id]);

            audit((int)current_user()['id'], 'teacher_updated', 'teachers', $id, 'Teacher updated: ' . teacher_full_name($data));
            Database::commit();
            flash_set('success', 'Teacher updated successfully.');
            redirect(url('admin/teachers/show.php?id=' . $id));
        } catch (Throwable $e) {
            Database::rollback();
            if ($newPhoto) delete_upload($newPhoto, 'profiles');
            $errors['form'] = 'Failed to update. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$photoUrl = teacher_photo_url($row);

$pageTitle       = 'Edit Teacher';
$pageDescription = 'Update teacher record.';
$breadcrumbs     = [
    ['label' => 'Teachers', 'url' => 'admin/teachers/index.php'],
    ['label' => teacher_full_name($row), 'url' => 'admin/teachers/show.php?id=' . $id],
    ['label' => 'Edit'],
];
$extraCss        = ['assets/css/teachers.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card"><div class="card-body text-center">
                <div class="photo-preview mx-auto mb-3" data-photo-input="#photoInput">
                    <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="">
                    <?php else: ?><i class="bi bi-person"></i><?php endif; ?>
                </div>
                <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                <button type="button" class="btn btn-outline-primary btn-sm"
                        onclick="document.getElementById('photoInput').click()">
                    <i class="bi bi-upload me-1"></i> Replace Photo
                </button>
                <?php if ($photoUrl): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                        <label class="form-check-label small" for="remove_photo">Remove current photo</label>
                    </div>
                <?php endif; ?>
            </div></div>
        </div>

        <div class="col-lg-9">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="<?= e($data['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" value="<?= e($data['middle_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="<?= e($data['last_name']) ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <?php foreach (gender_list() as $g): ?>
                                    <option value="<?= e($g) ?>" <?= $data['gender'] === $g ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= e($data['date_of_birth']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-telephone me-2"></i>Contact</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($data['address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi bi-mortarboard me-2"></i>Professional</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="<?= e($data['qualification']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="<?= e($data['specialization']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi bi-briefcase me-2"></i>Employment</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Teacher Code</label>
                                <input type="text" class="form-control" value="<?= e($data['teacher_code']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Employee No</label>
                                <input type="text" class="form-control" value="<?= e($data['employee_no']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hire Date</label>
                                <input type="date" name="hire_date" class="form-control" value="<?= e($data['hire_date']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Employment Status</label>
                                <select name="employment_status" class="form-select">
                                    <?php foreach (employment_status_list() as $st): ?>
                                        <option value="<?= e($st) ?>" <?= $data['employment_status'] === $st ? 'selected' : '' ?>>
                                            <?= e(ucfirst(str_replace('_', ' ', $st))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Changes</button>
                <a href="<?= e(url('admin/teachers/show.php?id=' . $id)) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = ['assets/js/teachers.js'];
include __DIR__ . '/../../includes/footer.php';
?>