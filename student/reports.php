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
    "SELECT DISTINCT ex.id, ex.name, ex.start_date, ay.name AS year_name, t.name AS term_name
     FROM marks m
     JOIN examinations ex ON ex.id = m.examination_id
     JOIN academic_years ay ON ay.id = ex.academic_year_id
     JOIN terms t ON t.id = ex.term_id
     WHERE m.student_id = ? AND ex.status IN ('published','completed')
     ORDER BY ex.start_date DESC",
    [(int)$student['id']]
);

$pageTitle       = 'My Reports';
$pageDescription = 'Download or print your report cards.';
$breadcrumbs     = [['label' => 'Reports']];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<?php if (!$exams): ?>
    <div class="table-wrap">
        <?= ui_empty('No reports yet', 'Report cards will appear here once exams are published.', 'bi-file-earmark-text') ?>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($exams as $ex): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="stat-icon icon-primary mb-3"><i class="bi bi-file-earmark-text"></i></div>
                        <h5 class="mb-1"><?= e($ex['name']) ?></h5>
                        <p class="text-muted small mb-3"><?= e($ex['year_name']) ?> · <?= e($ex['term_name']) ?></p>
                        <a href="<?= e(url('admin/examinations/report-card.php?exam_id=' . (int)$ex['id'] . '&student_id=' . (int)$student['id'])) ?>"
                           target="_blank" class="btn btn-primary btn-sm">
                            <i class="bi bi-printer me-1"></i> Open Report Card
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>