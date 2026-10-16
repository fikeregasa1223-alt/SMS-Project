<?php
// route-edit.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$id = (int)($_GET['id'] ?? 0);
$data = Database::fetch("SELECT * FROM transport_routes WHERE id = ?", [$id]);
if (!$data) { flash_set('error', 'Route not found.'); redirect(url('admin/transport/routes.php')); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['code','name','description','driver_name','driver_phone','vehicle_no'] as $k) $data[$k] = clean((string) post($k, ''));
    $data['capacity'] = (int) post('capacity', 40);
    $data['fee'] = (float) post('fee', 0);
    $data['status'] = (string) post('status', 'active');
    if (!$errors) {
        Database::update('transport_routes', $data, 'id = :id', ['id' => $id]);
        audit((int)current_user()['id'], 'transport_route_updated', 'transport_routes', $id, 'Route updated');
        flash_set('success', 'Route updated.');
        redirect(url('admin/transport/routes.php'));
    }
}
$pageTitle = 'Edit Route';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <form method="post" novalidate><?= csrf_field() ?>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= e($data['code']) ?>"></div>
            <div class="col-md-8 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= e($data['name']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Driver</label><input type="text" name="driver_name" class="form-control" value="<?= e((string)$data['driver_name']) ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="driver_phone" class="form-control" value="<?= e((string)$data['driver_phone']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Vehicle No</label><input type="text" name="vehicle_no" class="form-control" value="<?= e((string)$data['vehicle_no']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="<?= (int)$data['capacity'] ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Fee</label><input type="number" step="0.01" name="fee" class="form-control" value="<?= e((string)$data['fee']) ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" <?= $data['status']==='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $data['status']==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button>
        <a class="btn btn-outline-primary" href="<?= e(url('admin/transport/routes.php')) ?>">Cancel</a>
    </form>
</div></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>