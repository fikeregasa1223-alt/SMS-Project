<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('edit_students');

$studentId = (int) ($_GET['student_id'] ?? 0);
$student   = Database::fetch("SELECT * FROM students WHERE id = ?", [$studentId]);
if (!$student) { flash_set('error', 'Student not found.'); redirect(url('admin/students/index.php')); }

$errors = [];

// Attach existing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'attach') {
    csrf_check_or_die();
    $guardianId = (int) post('guardian_id', 0);
    $isPrimary  = (int) post('is_primary', 0) === 1 ? 1 : 0;

    if ($guardianId <= 0) {
        $errors['form'] = 'Please select a guardian.';
    } else {
        $dup = Database::fetch("SELECT 1 FROM student_guardians WHERE student_id = ? AND guardian_id = ?", [$studentId, $guardianId]);
        if ($dup) {
            $errors['form'] = 'This guardian is already attached.';
        } else {
            Database::begin();
            try {
                if ($isPrimary) {
                    Database::conn()->prepare("UPDATE student_guardians SET is_primary = 0 WHERE student_id = ?")->execute([$studentId]);
                }
                Database::insert('student_guardians', [
                    'student_id'  => $studentId,
                    'guardian_id' => $guardianId,
                    'is_primary'  => $isPrimary,
                ]);
                audit((int)current_user()['id'], 'guardian_attached', 'students', $studentId, 'Guardian attached to student');
                Database::commit();
                flash_set('success', 'Guardian attached.');
                redirect(url('admin/students/attach-guardian.php?student_id=' . $studentId));
            } catch (Throwable $e) {
                Database::rollback();
                $errors['form'] = 'Failed to attach guardian.';
            }
        }
    }
}

// Detach
if (($_GET['detach'] ?? '') !== '' && ($_GET['csrf'] ?? '') !== '') {
    if (!hash_equals(csrf_token(), (string)$_GET['csrf'])) {
        exit('Invalid CSRF token.');
    }
    $gid = (int) $_GET['detach'];
    Database::conn()->prepare("DELETE FROM student_guardians WHERE student_id = ? AND guardian_id = ?")->execute([$studentId, $gid]);
    audit((int)current_user()['id'], 'guardian_detached', 'students', $studentId, 'Guardian detached');
    flash_set('success', 'Guardian removed.');
    redirect(url('admin/students/attach-guardian.php?student_id=' . $studentId));
}

$attached  = Database::fetchAll("SELECT guardian_id FROM student_guardians WHERE student_id = ?", [$studentId]);
$attachedIds = array_map('intval', array_column($attached, 'guardian_id'));

$guardians = Database::fetchAll("SELECT * FROM guardians ORDER BY full_name ASC");
$current   = Database::fetchAll(
    "SELECT gd.*, sg.is_primary
     FROM student_guardians sg
     JOIN guardians gd ON gd.id = sg.guardian_id
     WHERE sg.student_id = ?
     ORDER BY sg.is_primary DESC",
    [$studentId]
);

$pageTitle       = 'Guardians for ' . student_full_name($student);
$pageDescription = 'Attach or manage guardians.';
$breadcrumbs     = [
    ['label' => 'Students', 'url' => 'admin/students/index.php'],
    ['label' => student_full_name($student), 'url' => 'admin/students/show.php?id=' . $studentId],
    ['label' => 'Guardians'],
];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/guardians/create.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-plus-lg me-1"></i> New Guardian
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus me-2"></i>Attach Existing Guardian</div>
            <div class="card-body">
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" value="attach">

                    <div class="mb-3">
                        <label class="form-label">Guardian</label>
                        <select name="guardian_id" class="form-select" required>
                            <option value="0">— Select —</option>
                            <?php foreach ($guardians as $g): ?>
                                <?php if (in_array((int)$g['id'], $attachedIds, true)) continue; ?>
                                <option value="<?= (int)$g['id'] ?>"><?= e($g['full_name']) ?> — <?= e($g['phone']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1">
                        <label class="form-check-label" for="is_primary">Set as primary guardian</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Attach</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-people me-2"></i>Current Guardians</div>
            <div class="card-body">
                <?php if (!$current): ?>
                    <?= ui_empty('No guardians attached', 'This student has no guardians yet.', 'bi-people') ?>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($current as $g): ?>
                            <div class="guardian-chip">
                                <div>
                                    <div class="fw-semibold"><?= e($g['full_name']) ?></div>
                                    <div class="role"><?= e(ucfirst(str_replace('_', ' ', $g['relationship']))) ?></div>
                                </div>
                                <div class="ms-auto small text-muted">
                                    <div><i class="bi bi-telephone"></i> <?= e($g['phone']) ?></div>
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    <?php if ((int)$g['is_primary']): ?>
                                        <?= ui_badge('Primary', 'primary') ?>
                                    <?php endif; ?>
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="<?= e(url('admin/students/attach-guardian.php?student_id=' . $studentId . '&detach=' . (int)$g['id'] . '&csrf=' . csrf_token())) ?>"
                                       data-confirm="Remove this guardian from the student?">
                                        <i class="bi bi-x"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>