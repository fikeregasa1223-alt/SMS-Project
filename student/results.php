<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('student');

$user = current_user();
$student = student_for_user((int)$user['id']);
if (!$student) { flash_set('error', 'No student record linked.'); redirect(url('student/dashboard.php')); }

$exams = Database::fetchAll(
    "SELECT DISTINCT ex.id, ex.name, ex.type, ex.start_date, ay.name AS year_name, t.name AS term_name
     FROM marks m
     JOIN examinations ex ON ex.id = m.examination_id
     JOIN academic_years ay ON ay.id = ex.academic_year_id
     JOIN terms t ON t.id = ex.term_id
     WHERE m.student_id = ? AND ex.status IN ('published','completed')
     ORDER BY ex.start_date DESC",
    [(int)$student['id']]
);

$pageTitle       = 'My Results';
$pageDescription = 'Your examination results.';
$breadcrumbs     = [['label' => 'Results']];
$extraCss        = ['assets/css/dashboards.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('student/reports.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-text me-1"></i> Report Cards
        </a>
    </div>
</div>

<?php if (!$exams): ?>
    <div class="table-wrap">
        <?= ui_empty('No results yet', 'Your exam results will appear here once published.', 'bi-trophy') ?>
    </div>
<?php else: ?>
    <?php foreach ($exams as $ex):
        $marks = Database::fetchAll(
            "SELECT m.*, sub.name AS subject_name, sub.code AS subject_code
             FROM marks m
             JOIN subjects sub ON sub.id = m.subject_id
             WHERE m.student_id = ? AND m.examination_id = ?
             ORDER BY sub.name ASC",
            [(int)$student['id'], (int)$ex['id']]
        );
        $total = 0; $max = 0;
        foreach ($marks as $m) { if ($m['score'] !== null) $total += (float)$m['score']; $max += (float)$m['max_mark']; }
        $pct = $max > 0 ? round(($total / $max) * 100, 2) : 0;
        $g   = resolve_grade($pct);
    ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= e($ex['name']) ?></strong>
                    <span class="text-muted small ms-2"><?= e(exam_type_label($ex['type'])) ?> · <?= e($ex['year_name']) ?> · <?= e($ex['term_name']) ?></span>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?= grade_badge($g['letter']) ?>
                    <strong><?= $pct ?>%</strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Subject</th><th>Score</th><th>Max</th><th>%</th><th>Grade</th></tr></thead>
                        <tbody>
                        <?php foreach ($marks as $m): ?>
                            <tr>
                                <td><?= e($m['subject_name']) ?> <span class="text-muted small">(<?= e($m['subject_code']) ?>)</span></td>
                                <td><?= $m['score'] !== null ? e((string)$m['score']) : '<span class="text-danger">Absent</span>' ?></td>
                                <td><?= e((string)$m['max_mark']) ?></td>
                                <td><?= $m['percentage'] !== null ? e((string)$m['percentage']) . '%' : '—' ?></td>
                                <td><?= grade_badge($m['grade_letter']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3">Overall</td>
                                <td><?= $pct ?>%</td>
                                <td><?= grade_badge($g['letter']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>