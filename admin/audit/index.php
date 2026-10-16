<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_audit_logs');

$action = table_filter('action');
$entity = table_filter('entity');
$userId = (int) ($_GET['user_id'] ?? 0);
$from   = (string) ($_GET['date_from'] ?? '');
$to     = (string) ($_GET['date_to'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage= 30;

$where  = "WHERE 1=1";
$params = [];
if ($action !== '') { $where .= " AND a.action LIKE :act"; $params['act'] = '%' . $action . '%'; }
if ($entity !== '') { $where .= " AND a.entity = :ent";    $params['ent'] = $entity; }
if ($userId > 0)    { $where .= " AND a.user_id = :uid";   $params['uid'] = $userId; }
if ($from)          { $where .= " AND a.created_at >= :df"; $params['df'] = $from . ' 00:00:00'; }
if ($to)            { $where .= " AND a.created_at <= :dt"; $params['dt'] = $to   . ' 23:59:59'; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM audit_logs a $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare(
    "SELECT a.*, u.username, u.full_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     $where
     ORDER BY a.id DESC
     LIMIT {$pg['limit']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$users = Database::fetchAll("SELECT id, username, full_name FROM users ORDER BY username ASC");

$pageTitle       = 'Audit Logs';
$pageDescription = 'Track all system actions.';
$breadcrumbs     = [['label' => 'Administration'], ['label' => 'Audit Logs']];
$extraCss        = ['assets/css/admin-settings.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
</div>

<form class="filter-bar row g-2 align-items-end mb-3" method="get">
    <div class="col-md-2">
        <label class="form-label small">Action</label>
        <input type="text" name="action" value="<?= e($action) ?>" class="form-control" placeholder="e.g. student_created">
    </div>
    <div class="col-md-2">
        <label class="form-label small">Entity</label>
        <input type="text" name="entity" value="<?= e($entity) ?>" class="form-control" placeholder="e.g. students">
    </div>
    <div class="col-md-3">
        <label class="form-label small">User</label>
        <select name="user_id" class="form-select">
            <option value="0">All users</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= $userId === (int)$u['id'] ? 'selected' : '' ?>>
                    <?= e($u['username']) ?> — <?= e($u['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">From</label>
        <input type="date" name="date_from" value="<?= e($from) ?>" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small">To</label>
        <input type="date" name="date_to" value="<?= e($to) ?>" class="form-control">
    </div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No audit entries', 'No actions recorded for this filter.', 'bi-list-columns') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>IP</th>
                        <th>Time</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-muted">#<?= (int)$r['id'] ?></td>
                        <td>
                            <?php if ($r['username']): ?>
                                <div class="fw-semibold"><?= e($r['username']) ?></div>
                                <div class="text-muted small"><?= e($r['full_name']) ?></div>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="audit-action"><?= e($r['action']) ?></span></td>
                        <td class="audit-entity">
                            <?= e($r['entity'] ?: '—') ?>
                            <?php if ($r['entity_id']): ?>#<?= (int)$r['entity_id'] ?><?php endif; ?>
                        </td>
                        <td class="small"><?= e($r['description'] ?: '—') ?></td>
                        <td class="audit-entity"><?= e($r['ip_address'] ?: '—') ?></td>
                        <td class="text-muted small"><?= e(fmt_date($r['created_at'], 'd M Y H:i:s')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(url('admin/audit/show.php?id=' . (int)$r['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], [
                'action' => $action, 'entity' => $entity, 'user_id' => $userId,
                'date_from' => $from, 'date_to' => $to
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>