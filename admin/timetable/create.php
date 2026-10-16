<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_attendance');

$errors = [];
$data = [
    'academic_year_id' => (int) ($_GET['year_id'] ?? 0),
    'term_id'          => (int) ($_GET['term_id'] ?? 0),
    'grade_id'         => 0,
    'section_id'       => (int) ($_GET['section_id'] ?? 0),
    'subject_id'       => 0,
    'teacher_id'       => 0,
    'period_id'        => 0,
    'day_of_week'      => 1,
    'room'             => '',
];

$years    = Database::fetchAll("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");
$terms    = Database::fetchAll("SELECT id, academic_year_id, name FROM terms ORDER BY sequence ASC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");
$subjects = Database::fetchAll("SELECT id, grade_id, code, name FROM subjects ORDER BY name ASC");
$teachers = Database::fetchAll("SELECT id, teacher_code, first_name, last_name FROM teachers ORDER BY first_name ASC");

if ($data['section_id'] > 0) {
    $sec = Database::fetch("SELECT grade_id FROM sections WHERE id = ?", [$data['section_id']]);
    if ($sec) $data['grade_id'] = (int)$sec['grade_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['academic_year_id','term_id','grade_id','section_id','subject_id','teacher_id','period_id','day_of_week'] as $k) {
        $data[$k] = (int) post($k, 0);
    }
    $data['room'] = clean((string) post('room', ''));

    if ($data['academic_year_id'] <= 0) $errors['academic_year_id'] = 'Year required.';
    if ($data['term_id'] <= 0)          $errors['term_id']          = 'Term required.';
    if ($data['section_id'] <= 0)       $errors['section_id']       = 'Section required.';
    if ($data['subject_id'] <= 0)       $errors['subject_id']       = 'Subject required.';
    if ($data['period_id'] <= 0)        $errors['period_id']        = 'Period required.';
    if ($data['day_of_week'] < 1 || $data['day_of_week'] > 6) $errors['day_of_week'] = 'Invalid day.';

    if (!$errors) {
        $secConflict = timetable_section_conflict($data['section_id'], $data['academic_year_id'], $data['term_id'], $data['period_id'], $data['day_of_week']);
        if ($secConflict) $errors['period_id'] = 'This section already has a class in that slot.';

        if ($data['teacher_id'] > 0) {
            $tConflict = timetable_teacher_conflict($data['teacher_id'], $data['academic_year_id'], $data['term_id'], $data['period_id'], $data['day_of_week']);
            if ($tConflict) $errors['teacher_id'] = 'This teacher is already booked in that slot.';
        }
    }

    if (!$errors) {
        $id = Database::insert('timetable_entries', [
            'academic_year_id' => $data['academic_year_id'],
            'term_id'          => $data['term_id'],
            'grade_id'         => $data['grade_id'],
            'section_id'       => $data['section_id'],
            'subject_id'       => $data['subject_id'],
            'teacher_id'       => $data['teacher_id'] ?: null,
            'period_id'        => $data['period_id'],
            'day_of_week'      => $data['day_of_week'],
            'room'             => $data['room'] ?: null,
        ]);
        audit((int)current_user()['id'], 'timetable_created', 'timetable_entries', $id, 'Timetable entry created');
        flash_set('success', 'Timetable entry created.');
        redirect(url('admin/timetable/index.php?year_id=' . $data['academic_year_id'] . '&term_id=' . $data['term_id'] . '&grade_id=' . $data['grade_id']));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$periods = $data['academic_year_id'] > 0 ? timetable_periods((int)$data['academic_year_id']) : [];

$pageTitle       = 'New Timetable Entry';
$pageDescription = 'Schedule a class.';
$breadcrumbs     = [['label' => 'Timetable', 'url' => 'admin/timetable/index.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" id="yearSel" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id'] ?>" <?= $data['academic_year_id'] === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $data['term_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Section</label>
                <select name="section_id" id="secSel" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>" data-grade="<?= (int)$sec['grade_id'] ?>"
                                <?= $data['section_id'] === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="grade_id" id="gradeHidden" value="<?= (int)$data['grade_id'] ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Subject</label>
                <select name="subject_id" id="subjSel" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" data-grade="<?= (int)$s['grade_id'] ?>"
                                <?= $data['subject_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['code']) ?> — <?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Teacher</label>
                <select name="teacher_id" class="form-select">
                    <option value="0">— Optional —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $data['teacher_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= e($t['teacher_code']) ?> — <?= e($t['first_name'] . ' ' . $t['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['teacher_id'])): ?><div class="text-danger small"><?= e($errors['teacher_id']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Room</label>
                <input type="text" name="room" class="form-control" value="<?= e($data['room']) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Period</label>
                <select name="period_id" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $data['period_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> (<?= e(substr($p['start_time'],0,5)) ?>-<?= e(substr($p['end_time'],0,5)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['period_id'])): ?><div class="text-danger small"><?= e($errors['period_id']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Day</label>
                <select name="day_of_week" class="form-select" required>
                    <?php foreach (timetable_days() as $d => $name): ?>
                        <option value="<?= $d ?>" <?= $data['day_of_week'] === $d ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
            <a href="<?= e(url('admin/timetable/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const secSel  = document.getElementById('secSel');
    const subjSel = document.getElementById('subjSel');
    const gradeHidden = document.getElementById('gradeHidden');

    function filter() {
        const gid = secSel.selectedOptions[0]?.getAttribute('data-grade') || '0';
        gradeHidden.value = gid;

        Array.from(subjSel.options).forEach(o => {
            if (o.value === '0') return;
            o.hidden = gid !== '0' && o.getAttribute('data-grade') !== gid;
        });
    }
    secSel.addEventListener('change', filter);
    filter();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>