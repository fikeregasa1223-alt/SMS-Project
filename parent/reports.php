<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('parent');

$user = current_user();
$parent = parent_for_user((int)$user['id']);
if (!$parent) { flash_set('error', 'No parent record linked.'); redirect(url('parent/dashboard.php')); }

$children = parent_children((int)$parent['id']);
$childIds = array_map(fn($c) => (int)$c['id'], $children);
$studentId = (int) ($_GET['student_id'] ?? ($childIds[0] ?? 0));
if (!in_array($studentId, $childIds, true)) { flash_set('error', 'Invalid child.'); redirect(url('parent/dashboard.php')); }

$child = Database::fetch("SELECT * FROM students WHERE id = ?", [$studentId]);

$exams = Database::fetchAll(
    "SELECT DISTINCT ex.id, ex.name, ex.start_date, ay.name AS year_name, t.name AS term_name
     FROM marks m
     JOIN examinations ex ON ex.id = m.examination_id
     JOIN academic_years ay ON ay.id = ex.academic_year_id
     JOIN terms t ON t.id = ex.term_id
     WHERE m.student_id = ? AND ex.status IN ('published','completed')
     ORDER BY ex.start_date DESC",
    [$studentId]
);

$pageTitle       = 'Reports — ' . student_full_name($child);
$pageDescription = 'Printable report cards for your child.';
$breadcrumbs     = [['label' => 'Reports']];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <?php foreach ($children as $c): ?>
            <a href="?student_id=<?= (int)$c['id'] ?>" class="btn btn-sm <?= (int)$c['id'] === $studentId ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= e($c['first_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$exams): ?>
    <div class="table-wrap"><?= ui_empty('No reports yet', 'Report cards will appear here once exams are published.', 'bi-file-earmark-text') ?></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($exams as $ex): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="stat-icon icon-primary mb-3"><i class="bi bi-file-earmark-text"></i></div>
                        <h5 class="mb-1"><?= e($ex['name']) ?></h5>
                        <p class="text-muted small mb-3"><?= e($ex['year_name']) ?> · <?= e($ex['term_name']) ?></p>
                        <a href="<?= e(url('admin/examinations/report-card.php?exam_id=' . (int)$ex['id'] . '&student_id=' . $studentId)) ?>"
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