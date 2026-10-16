<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('teacher');

$user    = current_user();
$teacher = teacher_for_user((int)$user['id']);
if (!$teacher) { flash_set('error', 'No teacher record.'); redirect(url('teacher/dashboard.php')); }

// Assigned exam subjects
$rows = Database::fetchAll(
    "SELECT es.id AS exam_subject_id, es.examination_id,
            ex.name AS exam_name, ex.status AS exam_status,
            sub.name AS subject_name, sub.code AS subject_code,
            sec.name AS section_name, g.name AS grade_name,
            (SELECT COUNT(*) FROM marks m WHERE m.exam_subject_id = es.id AND m.score IS NOT NULL) AS entered,
            (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = es.section_id AND e.academic_year_id = ex.academic_year_id AND e.status='active') AS total_students
     FROM examination_subjects es
     JOIN examinations ex ON ex.id = es.examination_id
     JOIN subjects sub ON sub.id = es.subject_id
     JOIN sections sec ON sec.id = es.section_id
     JOIN grades   g   ON g.id = sec.grade_id
     JOIN teacher_assignments ta
       ON ta.section_id = es.section_id
      AND ta.subject_id = es.subject_id
      AND ta.academic_year_id = ex.academic_year_id
     WHERE ta.teacher_id = ?
     ORDER BY ex.id DESC",
    [(int)$teacher['id']]
);

$pageTitle       = 'Enter Marks';
$pageDescription = 'Exam subjects assigned to you.';
$breadcrumbs     = [['label' => 'Enter Marks']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No exam subjects', 'You have no exam subjects assigned.', 'bi-pencil-square') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Exam</th><th>Grade</th><th>Section</th><th>Subject</th><th>Progress</th><th class="text-end"></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($r['exam_name']) ?></div>
                            <div><?= exam_status_badge($r['exam_status']) ?></div>
                        </td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><?= e($r['section_name']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['subject_code']) ?></span> <?= e($r['subject_name']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= (int)$r['entered'] ?> / <?= (int)$r['total_students'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= e(url('admin/examinations/marks.php?exam_id=' . (int)$r['examination_id'])) ?>">
                                <i class="bi bi-pencil-square"></i> Enter
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>