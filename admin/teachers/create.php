<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('create_teachers');

$errors = [];
$data = [
    'teacher_code'      => next_teacher_code(),
    'employee_no'       => next_employee_no(),
    'first_name'        => '',
    'middle_name'       => '',
    'last_name'         => '',
    'gender'            => 'male',
    'date_of_birth'     => '',
    'phone'             => '',
    'email'             => '',
    'address'           => '',
    'qualification'     => '',
    'specialization'    => '',
    'hire_date'         => date('Y-m-d'),
    'employment_status' => 'full_time',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();

    foreach ($data as $k => $_) {
        $data[$k] = clean((string) post($k, $data[$k]));
    }
    $data['gender']            = (string) post('gender', 'male');
    $data['employment_status'] = (string) post('employment_status', 'full_time');

    if (!v_required($data['first_name'])) $errors['first_name'] = 'First name is required.';
    if (!v_required($data['last_name']))  $errors['last_name']  = 'Last name is required.';
    if (!v_in_array($data['gender'], gender_list())) $errors['gender'] = 'Invalid gender.';
    if (!v_in_array($data['employment_status'], employment_status_list())) $errors['employment_status'] = 'Invalid employment status.';
    if ($data['date_of_birth'] !== '' && !v_date($data['date_of_birth'])) $errors['date_of_birth'] = 'Invalid date of birth.';
    if ($data['hire_date'] !== '' && !v_date($data['hire_date'])) $errors['hire_date'] = 'Invalid hire date.';
    if ($data['email'] !== '' && !v_email($data['email'])) $errors['email'] = 'Invalid email format.';
    if ($data['phone'] !== '' && !v_phone($data['phone'])) $errors['phone'] = 'Invalid phone format.';

    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM teachers WHERE teacher_code = ?", [$data['teacher_code']]);
        if ($dup) $errors['teacher_code'] = 'Teacher code already exists.';
    }
    if (!$errors) {
        $dup = Database::fetch("SELECT id FROM teachers WHERE employee_no = ?", [$data['employee_no']]);
        if ($dup) $errors['employee_no'] = 'Employee number already exists.';
    }

    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $up = upload_image($_FILES['photo'], 'profiles', 'teacher');
        if (!$up['ok']) $errors['photo'] = $up['message'];
        else            $photoName = $up['filename'];
    }

    if (!$errors) {
        Database::begin();
        try {
            $id = Database::insert('teachers', [
                'teacher_code'      => $data['teacher_code'],
                'employee_no'       => $data['employee_no'],
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
                'photo'             => $photoName,
            ]);

            audit((int)current_user()['id'], 'teacher_created', 'teachers', $id, 'Teacher created: ' . $data['teacher_code']);
            Database::commit();

            flash_set('success', 'Teacher created successfully.');
            redirect(url('admin/teachers/show.php?id=' . $id));
        } catch (Throwable $e) {
            Database::rollback();
            if ($photoName) delete_upload($photoName, 'profiles');
            $errors['form'] = 'Failed to create teacher. ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Add Teacher';
$pageDescription = 'Register a new teacher.';
$breadcrumbs     = [['label' => 'Teachers', 'url' => 'admin/teachers/index.php'], ['label' => 'Add']];
$extraCss        = ['assets/css/teachers.css'];
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
                        <i class="bi bi-person"></i>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="document.getElementById('photoInput').click()">
                        <i class="bi bi-upload me-1"></i> Choose Photo
                    </button>
                    <div class="form-text mt-2">JPG, PNG, WEBP · max 2 MB</div>
                    <?php if (isset($errors['photo'])): ?>
                        <div class="text-danger small mt-1"><?= e($errors['photo']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <!-- Personal -->
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

            <!-- Contact -->
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

            <!-- Professional + Employment -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi bi-mortarboard me-2"></i>Professional</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="<?= e($data['qualification']) ?>"
                                       placeholder="e.g. B.Ed, M.Sc">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="<?= e($data['specialization']) ?>"
                                       placeholder="e.g. Mathematics">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi bi-briefcase me-2"></i>Employment</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Teacher Code <span class="text-danger">*</span></label>
                                <input type="text" name="teacher_code" class="form-control" value="<?= e($data['teacher_code']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Employee No <span class="text-danger">*</span></label>
                                <input type="text" name="employee_no" class="form-control" value="<?= e($data['employee_no']) ?>" required>
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
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create Teacher</button>
                <a href="<?= e(url('admin/teachers/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = ['assets/js/teachers.js'];
include __DIR__ . '/../../includes/footer.php';
?>