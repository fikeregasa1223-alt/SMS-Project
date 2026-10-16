<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_students');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM students WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Student not found.'); redirect(url('admin/students/index.php')); }

$errors = [];
$data   = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    foreach (['first_name','middle_name','last_name','phone','email','address','emergency_name','emergency_phone','date_of_birth','admission_date'] as $k) {
        $data[$k] = clean((string) post($k, $data[$k] ?? ''));
    }
    $data['gender'] = (string) post('gender', 'male');
    $data['status'] = (string) post('status', 'active');

    if (!v_required($data['first_name'])) $errors['first_name'] = 'First name is required.';
    if (!v_required($data['last_name']))  $errors['last_name']  = 'Last name is required.';
    if (!v_in_array($data['gender'], gender_list())) $errors['gender'] = 'Invalid gender.';
    if (!v_date($data['date_of_birth']))  $errors['date_of_birth'] = 'Valid date of birth is required.';
    if (!v_date($data['admission_date'])) $errors['admission_date'] = 'Valid admission date is required.';
    if (!v_in_array($data['status'], student_status_list())) $errors['status'] = 'Invalid status.';
    if ($data['email'] !== '' && !v_email($data['email'])) $errors['email'] = 'Invalid email format.';
    if ($data['phone'] !== '' && !v_phone($data['phone'])) $errors['phone'] = 'Invalid phone format.';

    $newPhoto = null;
    $removePhoto = (int) post('remove_photo', 0) === 1;

    if (!empty($_FILES['photo']['name'])) {
        $up = upload_image($_FILES['photo'], 'profiles', 'student');
        if (!$up['ok']) {
            $errors['photo'] = $up['message'];
        } else {
            $newPhoto = $up['filename'];
        }
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

            Database::update('students', [
                'first_name'      => $data['first_name'],
                'middle_name'     => $data['middle_name'] ?: null,
                'last_name'       => $data['last_name'],
                'gender'          => $data['gender'],
                'date_of_birth'   => $data['date_of_birth'],
                'phone'           => $data['phone'] ?: null,
                'email'           => $data['email'] ?: null,
                'address'         => $data['address'] ?: null,
                'emergency_name'  => $data['emergency_name'] ?: null,
                'emergency_phone' => $data['emergency_phone'] ?: null,
                'photo'           => $photo,
                'admission_date'  => $data['admission_date'],
                'status'          => $data['status'],
            ], 'id = :id', ['id' => $id]);

            audit((int)current_user()['id'], 'student_updated', 'students', $id, 'Student updated: ' . $data['first_name'] . ' ' . $data['last_name']);
            Database::commit();

            flash_set('success', 'Student updated successfully.');
            redirect(url('admin/students/show.php?id=' . $id));
        } catch (Throwable $e) {
            Database::rollback();
            if ($newPhoto) delete_upload($newPhoto, 'profiles');
            $errors['form'] = 'Failed to update. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$photoUrl = student_photo_url($row);

$pageTitle       = 'Edit Student';
$pageDescription = 'Update student record.';
$breadcrumbs     = [
    ['label' => 'Students', 'url' => 'admin/students/index.php'],
    ['label' => student_full_name($row), 'url' => 'admin/students/show.php?id=' . $id],
    ['label' => 'Edit'],
];
$extraCss        = ['assets/css/students.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="photo-preview mx-auto mb-3" data-photo-input="#photoInput">
                        <?php if ($photoUrl): ?>
                            <img src="<?= e($photoUrl) ?>" alt="">
                        <?php else: ?>
                            <i class="bi bi-person"></i>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('photoInput').click()">
                        <i class="bi bi-upload me-1"></i> Replace Photo
                    </button>
                    <?php if ($photoUrl): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                            <label class="form-check-label small" for="remove_photo">Remove current photo</label>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($errors['photo'])): ?>
                        <div class="text-danger small mt-1"><?= e($errors['photo']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
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
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= e($data['date_of_birth']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-telephone me-2"></i>Contact Information</div>
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
                        <div class="card-header"><i class="bi bi-mortarboard me-2"></i>Academic</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Student Code</label>
                                <input type="text" class="form-control" value="<?= e($data['student_code']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission No</label>
                                <input type="text" class="form-control" value="<?= e($data['admission_no']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission Date <span class="text-danger">*</span></label>
                                <input type="date" name="admission_date" class="form-control" value="<?= e($data['admission_date']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (student_status_list() as $st): ?>
                                        <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi bi-shield-exclamation me-2"></i>Emergency Contact</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Contact Name</label>
                                <input type="text" name="emergency_name" class="form-control" value="<?= e($data['emergency_name']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="emergency_phone" class="form-control" value="<?= e($data['emergency_phone']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Changes</button>
                <a href="<?= e(url('admin/students/show.php?id=' . $id)) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = ['assets/js/students.js'];
include __DIR__ . '/../../includes/footer.php';
?>