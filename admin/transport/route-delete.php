<?php
// route-delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
sms_session_start();
require_login();
require_permission('manage_attendance');
$id = (int)($_GET['id'] ?? 0);
Database::conn()->prepare("DELETE FROM transport_routes WHERE id = ?")->execute([$id]);
audit((int)current_user()['id'], 'transport_route_deleted', 'transport_routes', $id, 'Route deleted');
flash_set('success', 'Route deleted.');
redirect(url('admin/transport/routes.php'));