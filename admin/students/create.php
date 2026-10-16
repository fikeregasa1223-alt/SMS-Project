<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('create_students');

$errors = [];
$data = [
    'student_code'    => next_student_code(),
    'admission_no'    => next_admission_no(),
    'first_name'      => '',
    'middle_name'     => '',
    'last_name'       => '',
    'gender'          => 'male',
    'date_of_birth'   => '',
    'phone'           => '',
    'email'           => '',
    'address'         => '',
    'emergency_name'  => '',
    'emergency_phone' => '',
    'admission_date'  => date('Y-m-d'),
    'status'          => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    foreach ($data as $k => $_) {
        $data[$k] = clean((string) post($k, $data[$k]));
    }
    $data['gender'] = (string) post('gender', 'male');
    $data['status'] = (string) post('status', 'active');

    // Validation
    if (!v_required($data['first_name'])) $errors['first_name'] = 'First name is required.';
    if (!v_required($data['last_name']))  $errors['last_name']  = 'Last name is required.';
    if (!v_in_array($data['gender'], gender_list())) $errors['gender'] = 'Invalid gender.';
    if (!v_date($data['date_of_birth']))  $errors['date_of_birth'] = 'Valid date of birth is required.';
    if (!v_date($data['admission_date'])) $errors['admission_date'] = 'Valid admission date is required.';
    if (!v_in_array($data['status'], student_status_list())) $errors['status'] = 'Invalid status.';
    if ($data['email'] !== '' && !v_email($data['email'])) $errors['email'] = 'Invalid email format.';
    if ($data['phone'] !== '' && !v_phone($data['phone'])) $errors['phone'] = 'Invalid phone format.';

    // Uniqueness
    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM students WHERE student_code = ?", [$data['student_code']]);
        if ($dup) $errors['student_code'] = 'Student code already exists.';
    }
    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM students WHERE admission_no = ?", [$data['admission_no']]);
        if ($dup) $errors['admission_no'] = 'Admission number already exists.';
    }

    // Photo
    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $up = upload_image($_FILES['photo'], 'profiles', 'student');
        if (!$up['ok']) {
            $errors['photo'] = $up['message'];
        } else {
            $photoName = $up['filename'];
        }
    }

    if (!$errors) {
        Database::begin();
        try {
            $id = Database::insert('students', [
                'student_code'    => $data['student_code'],
                'admission_no'    => $data['admission_no'],
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
                'photo'           => $photoName,
                'admission_date'  => $data['admission_date'],
                'status'          => $data['status'],
            ]);

            audit((int)current_user()['id'], 'student_created', 'students', $id, 'Student created: ' . $data['student_code']);
            Database::commit();

            flash_set('success', 'Student created successfully.');
            redirect(url('admin/students/show.php?id=' . $id));
        } catch (Throwable $e) {
            Database::rollback();
            if ($photoName) delete_upload($photoName, 'profiles');
            $errors['form'] = 'Failed to create student. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Add Student';
$pageDescription = 'Register a new student.';
$breadcrumbs     = [['label' => 'Students', 'url' => 'admin/students/index.php'], ['label' => 'Add']];
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
        <!-- LEFT: Photo -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="photo-preview mx-auto mb-3" data-photo-input="#photoInput">
                        <i class="bi bi-person"></i>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('photoInput').click()">
                        <i class="bi bi-upload me-1"></i> Choose Photo
                    </button>
                    <div class="form-text mt-2">JPG, PNG, WEBP · max 2 MB</div>
                    <?php if (isset($errors['photo'])): ?>
                        <div class="text-danger small mt-1"><?= e($errors['photo']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: Form sections -->
        <div class="col-lg-9">

            <!-- Personal -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['first_name']) ?>" required>
                            <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback d-block"><?= e($errors['first_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" value="<?= e($data['middle_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['last_name']) ?>" required>
                            <?php if (isset($errors['last_name'])): ?><div class="invalid-feedback d-block"><?= e($errors['last_name']) ?></div><?php endif; ?>
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
                            <input type="date" name="date_of_birth" class="form-control <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['date_of_birth']) ?>" required>
                            <?php if (isset($errors['date_of_birth'])): ?><div class="invalid-feedback d-block"><?= e($errors['date_of_birth']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-telephone me-2"></i>Contact Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['phone']) ?>" placeholder="+251 9xx xxx xxx">
                            <?php if (isset($errors['phone'])): ?><div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['email']) ?>">
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($data['address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic + Emergency -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi bi-mortarboard me-2"></i>Academic</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Student Code <span class="text-danger">*</span></label>
                                <input type="text" name="student_code" class="form-control <?= isset($errors['student_code']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($data['student_code']) ?>" required>
                                <?php if (isset($errors['student_code'])): ?><div class="invalid-feedback d-block"><?= e($errors['student_code']) ?></div><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission No <span class="text-danger">*</span></label>
                                <input type="text" name="admission_no" class="form-control <?= isset($errors['admission_no']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($data['admission_no']) ?>" required>
                                <?php if (isset($errors['admission_no'])): ?><div class="invalid-feedback d-block"><?= e($errors['admission_no']) ?></div><?php endif; ?>
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
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create Student</button>
                <a href="<?= e(url('admin/students/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = ['assets/js/students.js'];
include __DIR__ . '/../../includes/footer.php';
?>