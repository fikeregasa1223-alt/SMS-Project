<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_announcements');

expire_old_announcements();

$status   = (string) ($_GET['status'] ?? '');
$priority = (string) ($_GET['priority'] ?? '');
$audience = (string) ($_GET['audience'] ?? '');
$search   = table_filter('q');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 15;

$where  = "WHERE 1=1";
$params = [];
if ($status !== '')   { $where .= " AND a.status = :st";       $params['st'] = $status; }
if ($priority !== '') { $where .= " AND a.priority = :pr";     $params['pr'] = $priority; }
if ($audience !== '') { $where .= " AND a.audience = :au";     $params['au'] = $audience; }
if ($search !== '')   { $where .= " AND a.title LIKE :q";      $params['q']  = '%' . $search . '%'; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM announcements a $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare(
    "SELECT a.*, u.full_name AS author
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     $where
     ORDER BY FIELD(a.priority,'urgent','high','normal','low'), a.id DESC
     LIMIT {$pg['limit']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = Database::fetch(
    "SELECT
        SUM(status='draft') AS draft,
        SUM(status='published') AS published,
        SUM(status='expired') AS expired
     FROM announcements"
) ?: ['draft'=>0,'published'=>0,'expired'=>0];

$pageTitle       = 'Announcements';
$pageDescription = 'Publish and manage school-wide announcements.';
$breadcrumbs     = [['label' => 'Communication'], ['label' => 'Announcements']];
$extraCss        = ['assets/css/announcements.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/announcements/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Announcement
        </a>
    </div>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card stat-card">
            <div class="stat-icon icon-secondary"><i class="bi bi-file-earmark"></i></div>
            <div><div class="stat-value"><?= (int)$counts['draft'] ?></div><div class="stat-label">Drafts</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card stat-card">
            <div class="stat-icon icon-success"><i class="bi bi-megaphone"></i></div>
            <div><div class="stat-value"><?= (int)$counts['published'] ?></div><div class="stat-label">Published</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card stat-card">
            <div class="stat-icon icon-warning"><i class="bi bi-clock-history"></i></div>
            <div><div class="stat-value"><?= (int)$counts['expired'] ?></div><div class="stat-label">Expired</div></div>
        </div>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-3">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search title…">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select" data-auto-submit>
            <option value="">All statuses</option>
            <?php foreach (announcement_status_list() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="priority" class="form-select" data-auto-submit>
            <option value="">All priorities</option>
            <?php foreach (announcement_priority_list() as $pr): ?>
                <option value="<?= e($pr) ?>" <?= $priority === $pr ? 'selected' : '' ?>><?= e(ucfirst($pr)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="audience" class="form-select" data-auto-submit>
            <option value="">All audiences</option>
            <?php foreach (announcement_audience_list() as $au): ?>
                <option value="<?= e($au) ?>" <?= $audience === $au ? 'selected' : '' ?>><?= e(announcement_audience_label($au)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Filter</button></div>
    <div class="col-md-1"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No announcements', 'Create your first announcement.', 'bi-megaphone',
                     url('admin/announcements/create.php'), 'New Announcement') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th><th>Audience</th><th>Priority</th>
                        <th>Publish</th><th>Expiry</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($r['title']) ?></div>
                            <div class="text-muted small">
                                by <?= e($r['author'] ?: 'System') ?> · <?= e(fmt_date($r['created_at'], 'd M Y')) ?>
                            </div>
                        </td>
                        <td><?= announcement_audience_badge($r['audience']) ?></td>
                        <td><?= announcement_priority_badge($r['priority']) ?></td>
                        <td><?= e(fmt_date($r['publish_date'], 'd M Y H:i')) ?></td>
                        <td><?= $r['expiry_date'] ? e(fmt_date($r['expiry_date'], 'd M Y')) : '—' ?></td>
                        <td><?= announcement_status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'View',   'href' => url('admin/announcements/show.php?id='   . (int)$r['id']), 'icon' => 'bi-eye',    'tone' => 'outline-primary'],
                                ['label' => 'Edit',   'href' => url('admin/announcements/edit.php?id='   . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/announcements/delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete this announcement?'],
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
                'q' => $search, 'status' => $status, 'priority' => $priority, 'audience' => $audience
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>