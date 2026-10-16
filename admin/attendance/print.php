<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$sectionId = (int) ($_GET['section_id'] ?? 0);
$date      = (string) ($_GET['date'] ?? date('Y-m-d'));

$section = Database::fetch(
    "SELECT sec.*, g.name AS grade_name FROM sections sec JOIN grades g ON g.id = sec.grade_id WHERE sec.id = ?",
    [$sectionId]
);
if (!$section) { flash_set('error', 'Section not found.'); redirect(url('admin/attendance/index.php')); }

$rows = Database::fetchAll(
    "SELECT a.*, s.student_code, s.first_name, s.middle_name, s.last_name
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE a.section_id = ? AND a.date = ?
     ORDER BY s.first_name ASC, s.last_name ASC",
    [$sectionId, $date]
);

$schoolName  = setting('school_name', 'SMS Secondary School');
$schoolPhone = setting('school_phone', '');
$schoolEmail = setting('school_email', '');
$schoolAddr  = setting('school_address', '');
$reportHeader= setting('report_header', 'Attendance Sheet');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance Sheet — <?= e($section['grade_name']) ?> <?= e($section['name']) ?> — <?= e(fmt_date($date)) ?></title>
    <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/attendance.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #fff; }
        .sheet { max-width: 800px; margin: 20px auto; padding: 24px; }
        .sheet-header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; }
        .sheet-header h1 { margin: 0; font-size: 1.4rem; }
        .sheet-header .meta { font-size: .8rem; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; font-size: .85rem; }
        th { background: #f5f6fa; text-align: left; }
        .status { font-weight: 600; }
        .status.present { color: #10b981; }
        .status.absent  { color: #ef4444; }
        .status.late    { color: #f59e0b; }
        .status.excused { color: #06b6d4; }
        .sig { display: flex; justify-content: space-between; margin-top: 40px; font-size: .8rem; }
        .sig div { border-top: 1px solid #333; width: 40%; padding-top: 4px; text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="sheet-header">
        <h1><?= e($schoolName) ?></h1>
        <div class="meta">
            <?= e($schoolAddr) ?>
            <?php if ($schoolPhone): ?> · <?= e($schoolPhone) ?><?php endif; ?>
            <?php if ($schoolEmail): ?> · <?= e($schoolEmail) ?><?php endif; ?>
        </div>
        <h2 style="margin-top:10px;font-size:1.05rem;"><?= e($reportHeader) ?></h2>
    </div>

    <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:8px;">
        <div><strong>Grade:</strong> <?= e($section['grade_name']) ?> — <strong>Section:</strong> <?= e($section['name']) ?></div>
        <div><strong>Date:</strong> <?= e(fmt_date($date, 'D, d M Y')) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th style="width:100px;">Code</th>
                <th>Student Name</th>
                <th style="width:100px;">Status</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($rows as $r):
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= e($r['student_code']) ?></td>
                <td><?= e($name) ?></td>
                <td class="status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="4" style="text-align:center;color:#888;">No attendance recorded for this day.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="sig">
        <div>Teacher Signature</div>
        <div>Head of School</div>
    </div>

    <p class="no-print" style="text-align:center;margin-top:24px;">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
        <a href="javascript:history.back()" class="btn btn-outline-primary">Back</a>
    </p>
</div>
</body>
</html>