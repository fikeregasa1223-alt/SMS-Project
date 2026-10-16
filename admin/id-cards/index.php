<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_students');

$gradeId = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);

$grades = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name", [$gradeId])
    : Database::fetchAll("SELECT id, name FROM sections ORDER BY name");

$where = "WHERE s.status='active'";
$params = [];
if ($sectionId > 0) { $where .= " AND e.section_id = :sec"; $params['sec'] = $sectionId; }
if ($gradeId > 0)   { $where .= " AND e.grade_id = :g";     $params['g'] = $gradeId; }

$students = Database::fetchAll(
    "SELECT s.*, g.name AS grade_name, sec.name AS section_name
     FROM students s
     LEFT JOIN enrollments e ON e.student_id = s.id AND e.status='active'
     LEFT JOIN grades g ON g.id = e.grade_id
     LEFT JOIN sections sec ON sec.id = e.section_id
     $where GROUP BY s.id
     ORDER BY g.level, sec.name, s.first_name",
    $params
);

$pageTitle = 'ID Cards';
$breadcrumb = [['label' => 'ID Cards']];
$extraCss = ['assets/css/id-cards.css'];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1></div>
    <div class="page-actions">
        <a class="btn btn-outline-primary" href="<?= e(url('admin/id-cards/print.php?' . http_build_query(['grade_id' => $gradeId, 'section_id' => $sectionId]))) ?>" target="_blank">
            <i class="bi bi-printer me-1"></i> Print All
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-3"><label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><label class="form-label small">Section</label>
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">All sections</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="id-grid">
    <?php foreach ($students as $s):
        $photo = !empty($s['photo']) ? url('uploads/profiles/' . $s['photo']) : null;
        $name  = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
        $init  = mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1));
        $qr    = url('admin/students/show.php?id=' . (int)$s['id']);
    ?>
        <div class="id-card">
            <div class="id-header">
                <div class="id-logo"><?= e(mb_substr(setting('school_name','S'),0,1)) ?></div>
                <div>
                    <div class="id-school"><?= e(setting('school_name', 'SMS')) ?></div>
                    <div class="id-type">Student ID</div>
                </div>
            </div>
            <div class="id-photo">
                <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="">
                <?php else: ?><span><?= e($init) ?></span><?php endif; ?>
            </div>
            <div class="id-name"><?= e($name) ?></div>
            <div class="id-code"><?= e($s['student_code']) ?></div>
            <div class="id-meta">
                <span><?= e($s['grade_name'] ?: '—') ?></span>
                <span><?= e($s['section_name'] ?: '—') ?></span>
            </div>
            <div class="id-qr">
                <img alt="QR" src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($qr) ?>">
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$students): ?>
        <div class="text-muted">No students found.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>