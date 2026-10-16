<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('teacher');

$user    = current_user();
$teacher = teacher_for_user((int)$user['id']);
if (!$teacher) { flash_set('error', 'No teacher record.'); redirect(url('teacher/dashboard.php')); }

$rows = Database::fetchAll(
    "SELECT ta.*, g.name AS grade_name, sec.name AS section_name, sub.code AS subject_code, sub.name AS subject_name,
            ay.name AS year_name
     FROM teacher_assignments ta
     JOIN grades   g   ON g.id = ta.grade_id
     JOIN sections sec ON sec.id = ta.section_id
     JOIN subjects sub ON sub.id = ta.subject_id
     JOIN academic_years ay ON ay.id = ta.academic_year_id
     WHERE ta.teacher_id = ?
     ORDER BY ay.start_date DESC, g.level ASC, sec.name ASC",
    [(int)$teacher['id']]
);

$pageTitle       = 'My Classes';
$pageDescription = 'All classes and subjects you teach.';
$breadcrumbs     = [['label' => 'My Classes']];
$extraCss        = ['assets/css/dashboards.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No classes', 'No assignments yet.', 'bi-diagram-3') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Year</th><th>Grade</th><th>Section</th><th>Subject</th><th>Class Teacher</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['year_name']) ?></td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><?= e($r['section_name']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['subject_code']) ?></span> <?= e($r['subject_name']) ?></td>
                        <td><?= (int)$r['is_class_teacher'] ? ui_badge('Yes', 'primary') : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>