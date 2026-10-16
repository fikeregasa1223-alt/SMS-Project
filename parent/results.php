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
    "SELECT DISTINCT ex.id, ex.name, ex.type, ex.start_date, ay.name AS year_name, t.name AS term_name
     FROM marks m
     JOIN examinations ex ON ex.id = m.examination_id
     JOIN academic_years ay ON ay.id = ex.academic_year_id
     JOIN terms t ON t.id = ex.term_id
     WHERE m.student_id = ? AND ex.status IN ('published','completed')
     ORDER BY ex.start_date DESC",
    [$studentId]
);

$pageTitle       = 'Results — ' . student_full_name($child);
$pageDescription = 'Examination results for your child.';
$breadcrumbs     = [['label' => 'Results']];
$extraCss        = ['assets/css/dashboards.css'];
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
    <div class="table-wrap"><?= ui_empty('No results yet', 'Results will appear once published.', 'bi-trophy') ?></div>
<?php else: ?>
    <?php foreach ($exams as $ex):
        $marks = Database::fetchAll(
            "SELECT m.*, sub.name AS subject_name FROM marks m
             JOIN subjects sub ON sub.id = m.subject_id
             WHERE m.student_id = ? AND m.examination_id = ? ORDER BY sub.name",
            [$studentId, (int)$ex['id']]
        );
        $tot = 0; $mx = 0;
        foreach ($marks as $m) { if ($m['score'] !== null) $tot += (float)$m['score']; $mx += (float)$m['max_mark']; }
        $pct = $mx > 0 ? round(($tot / $mx) * 100, 2) : 0;
        $g = resolve_grade($pct);
    ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <strong><?= e($ex['name']) ?></strong>
                    <span class="text-muted small ms-2"><?= e($ex['year_name']) ?> · <?= e($ex['term_name']) ?></span>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?= grade_badge($g['letter']) ?>
                    <strong><?= $pct ?>%</strong>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Subject</th><th>Score</th><th>%</th><th>Grade</th></tr></thead>
                    <tbody>
                    <?php foreach ($marks as $m): ?>
                        <tr>
                            <td><?= e($m['subject_name']) ?></td>
                            <td><?= $m['score'] !== null ? e((string)$m['score']) : 'Absent' ?> / <?= e((string)$m['max_mark']) ?></td>
                            <td><?= $m['percentage'] !== null ? e((string)$m['percentage']) . '%' : '—' ?></td>
                            <td><?= grade_badge($m['grade_letter']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>