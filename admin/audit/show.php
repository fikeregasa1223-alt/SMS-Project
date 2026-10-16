<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_audit_logs');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch(
    "SELECT a.*, u.username, u.full_name, u.email
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.id = ?",
    [$id]
);
if (!$row) { flash_set('error', 'Audit entry not found.'); redirect(url('admin/audit/index.php')); }

$pageTitle       = 'Audit Entry #' . (int)$row['id'];
$pageDescription = 'Details of a system action.';
$breadcrumbs     = [['label' => 'Audit Logs', 'url' => 'admin/audit/index.php'], ['label' => 'View']];
$extraCss        = ['assets/css/admin-settings.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/audit/index.php')) ?>" class="btn btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Action</h6>
            <div class="info-row"><span class="label">Action</span><span class="value"><span class="audit-action"><?= e($row['action']) ?></span></span></div>
            <div class="info-row"><span class="label">Entity</span><span class="value audit-entity"><?= e($row['entity'] ?: '—') ?><?= $row['entity_id'] ? ' #' . (int)$row['entity_id'] : '' ?></span></div>
            <div class="info-row"><span class="label">Timestamp</span><span class="value"><?= e(fmt_date($row['created_at'], 'd M Y H:i:s')) ?></span></div>
            <div class="info-row"><span class="label">Description</span><span class="value"><?= e($row['description'] ?: '—') ?></span></div>
        </div></div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">User</h6>
            <?php if ($row['username']): ?>
                <div class="info-row"><span class="label">Username</span><span class="value"><?= e($row['username']) ?></span></div>
                <div class="info-row"><span class="label">Full Name</span><span class="value"><?= e($row['full_name']) ?></span></div>
                <div class="info-row"><span class="label">Email</span><span class="value"><?= e($row['email']) ?></span></div>
            <?php else: ?>
                <p class="text-muted mb-0 small">System action — no user context.</p>
            <?php endif; ?>
        </div></div>

        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Request</h6>
            <div class="info-row"><span class="label">IP Address</span><span class="value"><?= e($row['ip_address'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">User Agent</span><span class="value small"><?= e($row['user_agent'] ?: '—') ?></span></div>
        </div></div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>