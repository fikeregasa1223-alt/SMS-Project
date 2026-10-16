<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_marks');

$id = (int) ($_GET['id'] ?? 0);
$exam = Database::fetch(
    "SELECT e.*, ay.name AS year_name, t.name AS term_name, g.name AS grade_name
     FROM examinations e
     JOIN academic_years ay ON ay.id = e.academic_year_id
     JOIN terms t ON t.id = e.term_id
     JOIN grades g ON g.id = e.grade_id
     WHERE e.id = ?",
    [$id]
);
if (!$exam) { flash_set('error', 'Examination not found.'); redirect(url('admin/examinations/index.php')); }

$subjects = exam_subjects($id);
$summary  = exam_summary($id);

// Per-subject performance
$perSubject = Database::fetchAll(
    "SELECT sub.name AS subject_name, sub.code AS subject_code,
            COUNT(m.id) AS total,
            AVG(m.percentage) AS avg_pct,
            MAX(m.percentage) AS max_pct,
            MIN(m.percentage) AS min_pct
     FROM examination_subjects es
     JOIN subjects sub ON sub.id = es.subject_id
     LEFT JOIN marks m ON m.exam_subject_id = es.id AND m.percentage IS NOT NULL
     WHERE es.examination_id = ?
     GROUP BY es.id, sub.name, sub.code
     ORDER BY sub.name ASC",
    [$id]
);

$pageTitle       = $exam['name'];
$pageDescription = 'Examination overview.';
$breadcrumbs     = [['label' => 'Examinations', 'url' => 'admin/examinations/index.php'], ['label' => $exam['name']]];
$extraCss        = ['assets/css/examinations.css', 'assets/css/dashboard.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($exam['name']) ?></h1>
        <p class="page-desc">
            <?= e($exam['grade_name']) ?> · <?= e($exam['year_name']) ?> · <?= e($exam['term_name']) ?> ·
            <?= e(exam_type_label($exam['type'])) ?>
        </p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/examinations/marks.php?exam_id=' . $id)) ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i> Enter Marks
        </a>
        <a href="<?= e(url('admin/examinations/results.php?exam_id=' . $id)) ?>" class="btn btn-outline-primary">
            <i class="bi bi-trophy me-1"></i> View Results
        </a>
        <a href="<?= e(url('admin/examinations/edit.php?id=' . $id)) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-primary"><i class="bi bi-journal-check"></i></div>
            <div>
                <div class="stat-value"><?= (int)$summary['total'] ?></div>
                <div class="stat-label">Marks Recorded</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-success"><i class="bi bi-check-circle"></i></div>
            <div>
                <div class="stat-value"><?= (int)$summary['passed'] ?></div>
                <div class="stat-label">Passed (<?= (int)$summary['pass_rate'] ?>%)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-danger"><i class="bi bi-x-circle"></i></div>
            <div>
                <div class="stat-value"><?= (int)$summary['failed'] ?></div>
                <div class="stat-label">Failed</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon icon-info"><i class="bi bi-graph-up"></i></div>
            <div>
                <div class="stat-value"><?= e((string)$summary['avg_pct']) ?>%</div>
                <div class="stat-label">Average</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Subjects progress -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-bookmark me-2"></i>Subjects</span>
                <a href="<?= e(url('admin/examinations/subjects.php?id=' . $id)) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-gear me-1"></i> Manage Subjects
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!$subjects): ?>
                    <?= ui_empty('No subjects', 'Add subjects to start entering marks.', 'bi-journal',
                                 url('admin/examinations/subjects.php?id=' . $id), 'Add Subjects') ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Subject</th><th>Section</th><th>Max</th><th>Progress</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($subjects as $s):
                                $prog = marks_progress((int)$s['id']);
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($s['subject_name']) ?></div>
                                        <div class="text-muted small"><?= e($s['subject_code']) ?></div>
                                    </td>
                                    <td><?= e($s['section_name']) ?></td>
                                    <td><?= e((string)$s['max_mark']) ?></td>
                                    <td><?= marks_progress_badge($prog) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= e(url('admin/examinations/marks.php?exam_id=' . $id . '&subject_id=' . (int)$s['subject_id'] . '&section_id=' . (int)$s['section_id'])) ?>">
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
        </div>
    </div>

    <!-- Per-subject performance -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Performance by Subject</div>
            <div class="card-body p-0">
                <?php if (!$perSubject): ?>
                    <?= ui_empty('No marks yet', 'Performance will appear once marks are entered.', 'bi-bar-chart') ?>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($perSubject as $p):
                            $ap = round((float)($p['avg_pct'] ?? 0), 1);
                            $cls = $ap < 60 ? 'danger' : ($ap < 75 ? 'warn' : '');
                        ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold"><?= e($p['subject_name']) ?></span>
                                    <span class="text-muted small"><?= (int)$p['total'] ?> marks</span>
                                </div>
                                <strong><?= $ap ?>%</strong>
                                <div class="pct-bar <?= $cls ?>"><span style="width: <?= $ap ?>%"></span></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>