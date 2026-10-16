<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_students');

$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);

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
     $where GROUP BY s.id ORDER BY g.level, sec.name, s.first_name",
    $params
);

$schoolName = setting('school_name', 'SMS');
$logo = setting('school_logo', '');
?><!doctype html><html><head><meta charset="utf-8"><title>ID Cards</title>
<link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
<link href="<?= e(url('assets/css/id-cards.css')) ?>" rel="stylesheet">
<style>body { background:#f5f6fa; padding:20px; }</style>
</head><body>
<div class="id-grid">
<?php foreach ($students as $s):
    $photo = !empty($s['photo']) ? url('uploads/profiles/' . $s['photo']) : null;
    $name  = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
    $init  = mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1));
    $qr    = url('admin/students/show.php?id=' . (int)$s['id']);
?>
<div class="id-card">
    <div class="id-header">
        <div class="id-logo"><?= e(mb_substr($schoolName,0,1)) ?></div>
        <div><div class="id-school"><?= e($schoolName) ?></div><div class="id-type">Student ID</div></div>
    </div>
    <div class="id-photo">
        <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="">
        <?php else: ?><span><?= e($init) ?></span><?php endif; ?>
    </div>
    <div class="id-name"><?= e($name) ?></div>
    <div class="id-code"><?= e($s['student_code']) ?></div>
    <div class="id-meta"><span><?= e($s['grade_name'] ?: '—') ?></span><span><?= e($s['section_name'] ?: '—') ?></span></div>
    <div class="id-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($qr) ?>"></div>
</div>
<?php endforeach; ?>
</div>
<script>window.onload = () => window.print();</script>
</body></html>