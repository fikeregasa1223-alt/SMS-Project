<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_attendance');

$stats = Database::fetch(
    "SELECT
        (SELECT COUNT(*) FROM transport_routes WHERE status='active') AS routes,
        (SELECT COUNT(*) FROM transport_assignments WHERE status='active') AS assignments,
        (SELECT COALESCE(SUM(capacity),0) FROM transport_routes WHERE status='active') AS capacity"
);
$pageTitle = 'Transport';
$breadcrumbs = [['label' => 'Transport']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/transport/route-create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus me-1"></i> New Route</a>
        <a href="<?= e(url('admin/transport/assign.php')) ?>" class="btn btn-outline-primary"><i class="bi bi-people me-1"></i> Assign Students</a>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-sm-4"><?= attendance_stat_card('Active Routes', (int)$stats['routes'], 'bi-signpost-2', 'primary') ?></div>
    <div class="col-sm-4"><?= attendance_stat_card('Students Assigned', (int)$stats['assignments'], 'bi-people', 'success') ?></div>
    <div class="col-sm-4"><?= attendance_stat_card('Total Capacity', (int)$stats['capacity'], 'bi-person-lines-fill', 'info') ?></div>
</div>
<div class="row g-3">
    <div class="col-md-6"><div class="card"><div class="card-body">
        <h5>Quick Links</h5>
        <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="<?= e(url('admin/transport/routes.php')) ?>"><i class="bi bi-signpost-split me-1"></i> All Routes</a>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/transport/assign.php')) ?>"><i class="bi bi-people me-1"></i> Assignments</a>
        </div>
    </div></div></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>