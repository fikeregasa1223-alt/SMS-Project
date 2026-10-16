<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$yearId = (int) ($_GET['year_id'] ?? 0);
if ($yearId === 0) {
    $y = Database::fetch("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $yearId = $y ? (int)$y['id'] : 0;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $studentId = (int) post('student_id', 0);
    $routeId   = (int) post('route_id', 0);
    $stopId    = (int) post('stop_id', 0) ?: null;
    $aid       = (int) post('academic_year_id', 0);

    if ($studentId <= 0 || $routeId <= 0 || $aid <= 0) $errors['form'] = 'Student, route, and year are required.';

    if (!$errors) {
        $existing = Database::fetch("SELECT id FROM transport_assignments WHERE student_id = ? AND academic_year_id = ?", [$studentId, $aid]);
        if ($existing) {
            Database::update('transport_assignments', ['route_id'=>$routeId, 'stop_id'=>$stopId, 'status'=>'active'], 'id = :id', ['id' => (int)$existing['id']]);
        } else {
            Database::insert('transport_assignments', [
                'student_id' => $studentId, 'route_id' => $routeId, 'stop_id' => $stopId, 'academic_year_id' => $aid, 'status' => 'active',
            ]);
        }
        audit((int)current_user()['id'], 'transport_assigned', 'transport_assignments', null, 'Student assigned to route');
        flash_set('success', 'Assignment saved.');
        redirect(url('admin/transport/assign.php?year_id=' . $aid));
    }
}

$years    = Database::fetchAll("SELECT id, name FROM academic_years ORDER BY start_date DESC");
$routes   = Database::fetchAll("SELECT id, code, name FROM transport_routes WHERE status='active' ORDER BY name ASC");
$students = Database::fetchAll("SELECT id, student_code, first_name, last_name FROM students WHERE status='active' ORDER BY first_name ASC");

$assignments = Database::fetchAll(
    "SELECT ta.*, s.student_code, s.first_name, s.last_name, r.code AS route_code, r.name AS route_name, st.name AS stop_name
     FROM transport_assignments ta
     JOIN students s ON s.id = ta.student_id
     JOIN transport_routes r ON r.id = ta.route_id
     LEFT JOIN transport_stops st ON st.id = ta.stop_id
     WHERE ta.academic_year_id = ?
     ORDER BY r.name, s.first_name",
    [$yearId]
);

$pageTitle = 'Transport Assignments';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-4"><label class="form-label small">Academic Year</label>
        <select name="year_id" class="form-select" data-auto-submit>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="row g-3">
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <h5>Assign Student</h5>
        <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
        <form method="post"><?= csrf_field() ?>
            <input type="hidden" name="academic_year_id" value="<?= (int)$yearId ?>">
            <div class="mb-3"><label class="form-label">Student</label>
                <select name="student_id" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['student_code']) ?> — <?= e($s['first_name'] . ' ' . $s['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Route</label>
                <select name="route_id" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= e($r['code']) ?> — <?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Assign</button>
        </form>
    </div></div></div>

    <div class="col-lg-7"><div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Student</th><th>Route</th><th>Stop</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td><?= e($a['first_name'] . ' ' . $a['last_name']) ?><div class="text-muted small"><?= e($a['student_code']) ?></div></td>
                        <td><?= e($a['route_code']) ?> — <?= e($a['route_name']) ?></td>
                        <td><?= e($a['stop_name'] ?: '—') ?></td>
                        <td><?= ui_badge(ucfirst($a['status']), $a['status'] === 'active' ? 'success' : 'secondary') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$assignments): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No assignments yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div></div></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>