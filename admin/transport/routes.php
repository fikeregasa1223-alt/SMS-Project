<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('view_attendance');

$routes = Database::fetchAll(
    "SELECT r.*, (SELECT COUNT(*) FROM transport_assignments ta WHERE ta.route_id = r.id AND ta.status='active') AS students
     FROM transport_routes r
     ORDER BY r.id DESC"
);

$pageTitle = 'Transport Routes';
$breadcrumbs = [['label' => 'Transport', 'url' => 'admin/transport/index.php'], ['label' => 'Routes']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/transport/route-create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Route</a>
    </div>
</div>
<div class="table-wrap">
    <?php if (!$routes): ?>
        <?= ui_empty('No routes', 'Add a transport route.', 'bi-truck', url('admin/transport/route-create.php'), 'New Route') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Code</th><th>Route</th><th>Driver</th><th>Vehicle</th><th>Capacity</th><th>Students</th><th>Fee</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($routes as $r): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= e($r['code']) ?></span></td>
                        <td><div class="fw-semibold"><?= e($r['name']) ?></div></td>
                        <td><?= e($r['driver_name'] ?: '—') ?><?= $r['driver_phone'] ? '<div class="text-muted small">' . e($r['driver_phone']) . '</div>' : '' ?></td>
                        <td><?= e($r['vehicle_no'] ?: '—') ?></td>
                        <td><?= (int)$r['capacity'] ?></td>
                        <td><?= (int)$r['students'] ?></td>
                        <td><?= e(number_format((float)$r['fee'], 2)) ?></td>
                        <td><?= transport_route_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit', 'href' => url('admin/transport/route-edit.php?id=' . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/transport/route-delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete this route?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>