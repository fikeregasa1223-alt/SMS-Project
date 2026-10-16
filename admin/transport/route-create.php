<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$errors = [];
$data = ['code'=>'','name'=>'','description'=>'','driver_name'=>'','driver_phone'=>'','vehicle_no'=>'','capacity'=>40,'fee'=>0,'status'=>'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['code','name','description','driver_name','driver_phone','vehicle_no'] as $k) $data[$k] = clean((string) post($k, ''));
    $data['capacity'] = (int) post('capacity', 40);
    $data['fee'] = (float) post('fee', 0);
    $data['status'] = (string) post('status', 'active');

    if (!v_required($data['code'])) $errors['code'] = 'Code required.';
    if (!v_required($data['name'])) $errors['name'] = 'Name required.';

    if (!$errors) {
        $id = Database::insert('transport_routes', $data);
        audit((int)current_user()['id'], 'transport_route_created', 'transport_routes', $id, 'Route created: ' . $data['code']);
        flash_set('success', 'Route created.');
        redirect(url('admin/transport/routes.php'));
    }
}
$pageTitle = 'New Route';
$breadcrumbs = [['label' => 'Transport', 'url' => 'admin/transport/routes.php'], ['label' => 'New']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <form method="post" novalidate><?= csrf_field() ?>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= e($data['code']) ?>" required></div>
            <div class="col-md-8 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= e($data['name']) ?>" required></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Driver</label><input type="text" name="driver_name" class="form-control" value="<?= e($data['driver_name']) ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Driver Phone</label><input type="text" name="driver_phone" class="form-control" value="<?= e($data['driver_phone']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Vehicle No</label><input type="text" name="vehicle_no" class="form-control" value="<?= e($data['vehicle_no']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="<?= (int)$data['capacity'] ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Monthly Fee</label><input type="number" step="0.01" name="fee" class="form-control" value="<?= e((string)$data['fee']) ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Description</label><input type="text" name="description" class="form-control" value="<?= e($data['description']) ?>"></div>
        <div class="mb-3"><label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" <?= $data['status']==='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $data['status']==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create</button>
        <a class="btn btn-outline-primary" href="<?= e(url('admin/transport/routes.php')) ?>">Cancel</a>
    </form>
</div></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>