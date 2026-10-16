<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$yearId    = (int) ($_GET['academic_year_id'] ?? 0);
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);
$studentQ  = table_filter('student');
$dateFrom  = (string) ($_GET['date_from'] ?? '');
$dateTo    = (string) ($_GET['date_to'] ?? '');
$status    = (string) ($_GET['status'] ?? '');
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 30;

$where  = "WHERE 1=1";
$params = [];
if ($yearId > 0)    { $where .= " AND a.academic_year_id = :ay"; $params['ay'] = $yearId; }
if ($sectionId > 0) { $where .= " AND a.section_id = :sec";       $params['sec'] = $sectionId; }
if ($gradeId > 0)   { $where .= " AND sec.grade_id = :g";         $params['g'] = $gradeId; }
if ($dateFrom)      { $where .= " AND a.date >= :df";             $params['df'] = $dateFrom; }
if ($dateTo)        { $where .= " AND a.date <= :dt";             $params['dt'] = $dateTo; }
if ($status !== '') { $where .= " AND a.status = :st";            $params['st'] = $status; }
if ($studentQ !== '') {
    $where .= " AND (s.first_name LIKE :sq OR s.last_name LIKE :sq OR s.student_code LIKE :sq)";
    $params['sq'] = '%' . $studentQ . '%';
}

$stmt = Database::conn()->prepare(
    "SELECT COUNT(*) FROM attendance a
     JOIN students s ON s.id = a.student_id
     JOIN sections sec ON sec.id = a.section_id
     $where"
);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$sql = "SELECT a.*, s.student_code, s.first_name, s.middle_name, s.last_name,
               g.name AS grade_name, sec.name AS section_name,
               sub.name AS subject_name, ay.name AS year_name, t.name AS term_name
        FROM attendance a
        JOIN students s   ON s.id = a.student_id
        JOIN sections sec ON sec.id = a.section_id
        JOIN grades   g   ON g.id = sec.grade_id
        JOIN academic_years ay ON ay.id = a.academic_year_id
        JOIN terms    t   ON t.id = a.term_id
        LEFT JOIN subjects sub ON sub.id = a.subject_id
        $where
        ORDER BY a.date DESC, a.id DESC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$years    = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");

$pageTitle       = 'Attendance History';
$pageDescription = 'Browse and filter attendance records.';
$breadcrumbs     = [['label' => 'Attendance', 'url' => 'admin/attendance/index.php'], ['label' => 'History']];
$extraCss        = ['assets/css/attendance.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<form class="filter-bar row g-2 align-items-end" method="get">
    <div class="col-md-2">
        <label class="form-label small">Year</label>
        <select name="academic_year_id" class="form-select" data-auto-submit>
            <option value="0">All years</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Grade</label>
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Section</label>
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">All sections</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">From</label>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small">To</label>
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control">
    </div>
    <div class="col-md-1">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select">
            <option value="">All</option>
            <?php foreach (attendance_status_list() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Student</label>
        <input type="text" name="student" value="<?= e($studentQ) ?>" class="form-control" placeholder="Name or code…">
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Filter</button></div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No attendance records', 'Adjust your filters or take attendance.', 'bi-clock-history',
                     url('admin/attendance/take.php'), 'Take Attendance') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th><th>Student</th><th>Grade</th><th>Section</th>
                        <th>Subject</th><th>Term</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $name = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                ?>
                    <tr>
                        <td><?= e(fmt_date($r['date'], 'D, d M Y')) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($name) ?></div>
                            <div class="text-muted small"><?= e($r['student_code']) ?></div>
                        </td>
                        <td><?= e($r['grade_name']) ?></td>
                        <td><?= e($r['section_name']) ?></td>
                        <td><?= e($r['subject_name'] ?: 'Homeroom') ?></td>
                        <td><?= e($r['term_name']) ?></td>
                        <td><?= attendance_status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'View',   'href' => url('admin/attendance/view.php?id=' . (int)$r['id']), 'icon' => 'bi-eye', 'tone' => 'outline-primary'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], [
                'academic_year_id' => $yearId, 'grade_id' => $gradeId, 'section_id' => $sectionId,
                'date_from' => $dateFrom, 'date_to' => $dateTo, 'status' => $status, 'student' => $studentQ,
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>