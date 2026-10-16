<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('teacher');

$user    = current_user();
$teacher = teacher_for_user((int)$user['id']);
if (!$teacher) { flash_set('error', 'No teacher record.'); redirect(url('teacher/dashboard.php')); }

$sectionId = (int) ($_GET['section_id'] ?? 0);

// Teacher's sections
$sections = Database::fetchAll(
    "SELECT DISTINCT ta.section_id, sec.name AS section_name, g.name AS grade_name
     FROM teacher_assignments ta
     JOIN sections sec ON sec.id = ta.section_id
     JOIN grades   g   ON g.id = ta.grade_id
     WHERE ta.teacher_id = ?
     ORDER BY g.level ASC, sec.name ASC",
    [(int)$teacher['id']]
);

$students = [];
if ($sectionId > 0) {
    $students = Database::fetchAll(
        "SELECT DISTINCT s.*
         FROM students s
         JOIN enrollments e ON e.student_id = s.id AND e.status='active'
         JOIN teacher_assignments ta ON ta.section_id = e.section_id AND ta.academic_year_id = e.academic_year_id
         WHERE ta.teacher_id = ? AND e.section_id = ?
         ORDER BY s.first_name ASC, s.last_name ASC",
        [(int)$teacher['id'], $sectionId]
    );
}

$pageTitle       = 'My Students';
$pageDescription = 'Students in your classes.';
$breadcrumbs     = [['label' => 'My Students']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<form class="filter-bar row g-2 align-items-center mb-3" method="get">
    <div class="col-md-4">
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">Select a class…</option>
            <?php foreach ($sections as $s): ?>
                <option value="<?= (int)$s['section_id'] ?>" <?= $sectionId === (int)$s['section_id'] ? 'selected' : '' ?>>
                    <?= e($s['grade_name']) ?> — <?= e($s['section_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!$students): ?>
    <div class="table-wrap">
        <?= ui_empty('Choose a class', 'Select one of your classes to see students.', 'bi-people') ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Student</th><th>Code</th><th>Gender</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($students as $s):
                    $name = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                ?>
                    <tr>
                        <td class="fw-semibold"><?= e($name) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($s['student_code']) ?></span></td>
                        <td><?= e(ucfirst($s['gender'])) ?></td>
                        <td class="text-end">
                            <a href="<?= e(url('admin/students/show.php?id=' . (int)$s['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>