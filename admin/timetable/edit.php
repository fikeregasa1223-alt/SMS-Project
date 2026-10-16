<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_attendance');

$id = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM timetable_entries WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Entry not found.'); redirect(url('admin/timetable/index.php')); }

$errors = [];
$data = $row;

$years    = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");
$terms    = Database::fetchAll("SELECT id, academic_year_id, name FROM terms ORDER BY sequence ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");
$subjects = Database::fetchAll("SELECT id, grade_id, code, name FROM subjects ORDER BY name ASC");
$teachers = Database::fetchAll("SELECT id, teacher_code, first_name, last_name FROM teachers ORDER BY first_name ASC");
$periods  = timetable_periods((int)$data['academic_year_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['academic_year_id','term_id','grade_id','section_id','subject_id','teacher_id','period_id','day_of_week'] as $k) {
        $data[$k] = (int) post($k, 0);
    }
    $data['room'] = clean((string) post('room', ''));

    if (!$errors) {
        $secConflict = timetable_section_conflict($data['section_id'], $data['academic_year_id'], $data['term_id'], $data['period_id'], $data['day_of_week'], $id);
        if ($secConflict) $errors['period_id'] = 'Conflict with another entry.';

        if ($data['teacher_id'] > 0) {
            $tConflict = timetable_teacher_conflict($data['teacher_id'], $data['academic_year_id'], $data['term_id'], $data['period_id'], $data['day_of_week'], $id);
            if ($tConflict) $errors['teacher_id'] = 'Teacher already booked.';
        }
    }

    if (!$errors) {
        Database::update('timetable_entries', [
            'academic_year_id' => $data['academic_year_id'],
            'term_id'          => $data['term_id'],
            'grade_id'         => $data['grade_id'],
            'section_id'       => $data['section_id'],
            'subject_id'       => $data['subject_id'],
            'teacher_id'       => $data['teacher_id'] ?: null,
            'period_id'        => $data['period_id'],
            'day_of_week'      => $data['day_of_week'],
            'room'             => $data['room'] ?: null,
        ], 'id = :id', ['id' => $id]);
        audit((int)current_user()['id'], 'timetable_updated', 'timetable_entries', $id, 'Timetable updated');
        flash_set('success', 'Timetable updated.');
        redirect(url('admin/timetable/index.php'));
    }
}

$pageTitle = 'Edit Timetable Entry';
$breadcrumbs = [['label' => 'Timetable', 'url' => 'admin/timetable/index.php'], ['label' => 'Edit']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Year</label>
                <select name="academic_year_id" class="form-select" required>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= (int)$data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Term</label>
                <select name="term_id" class="form-select" required>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (int)$data['term_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Section</label>
                <select name="section_id" class="form-select" required>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>" <?= (int)$data['section_id'] === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="grade_id" value="<?= (int)$data['grade_id'] ?>">
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Subject</label>
                <select name="subject_id" class="form-select" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)$data['subject_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['code']) ?> — <?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Teacher</label>
                <select name="teacher_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (int)$data['teacher_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['first_name'] . ' ' . $t['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Period</label>
                <select name="period_id" class="form-select" required>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= (int)$data['period_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Day</label>
                <select name="day_of_week" class="form-select" required>
                    <?php foreach (timetable_days() as $d => $name): ?>
                        <option value="<?= $d ?>" <?= (int)$data['day_of_week'] === $d ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3"><label class="form-label">Room</label><input type="text" name="room" class="form-control" value="<?= e((string)$data['room']) ?>"></div>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/timetable/index.php')) ?>">Cancel</a>
            <a class="btn btn-outline-danger ms-auto" data-confirm="Delete this entry?"
               href="<?= e(url('admin/timetable/delete.php?id=' . $id . '&csrf=' . csrf_token())) ?>"><i class="bi bi-trash"></i> Delete</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>