<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_announcements');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch(
    "SELECT a.*, u.full_name AS author, g.name AS grade_name, sec.name AS section_name
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     LEFT JOIN grades g ON g.id = a.grade_id
     LEFT JOIN sections sec ON sec.id = a.section_id
     WHERE a.id = ?",
    [$id]
);
if (!$row) { flash_set('error', 'Announcement not found.'); redirect(url('admin/announcements/index.php')); }

$pageTitle       = 'Announcement';
$pageDescription = $row['title'];
$breadcrumbs     = [
    ['label' => 'Announcements', 'url' => 'admin/announcements/index.php'],
    ['label' => 'View'],
];
$extraCss        = ['assets/css/announcements.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/announcements/edit.php?id=' . $id)) ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="<?= e(url('admin/announcements/delete.php?id=' . $id)) ?>" class="btn btn-outline-danger"
           data-confirm="Delete this announcement?">
            <i class="bi bi-trash me-1"></i> Delete
        </a>
    </div>
</div>

<div class="ann-detail">
    <div class="ann-badges mb-3">
        <?= announcement_priority_badge($row['priority']) ?>
        <?= announcement_status_badge($row['status']) ?>
        <?= announcement_audience_badge($row['audience']) ?>
        <?php if ($row['grade_name']): ?>
            <span class="badge bg-light text-dark border"><?= e($row['grade_name']) ?></span>
        <?php endif; ?>
        <?php if ($row['section_name']): ?>
            <span class="badge bg-light text-dark border"><?= e($row['section_name']) ?></span>
        <?php endif; ?>
    </div>

    <h1><?= e($row['title']) ?></h1>
    <div class="text-muted small">
        by <?= e($row['author'] ?: 'System') ?>
        · Published <?= e(fmt_date($row['publish_date'], 'd M Y H:i')) ?>
        <?php if ($row['expiry_date']): ?>
            · Expires <?= e(fmt_date($row['expiry_date'], 'd M Y H:i')) ?>
        <?php endif; ?>
    </div>

    <div class="content"><?= e($row['content']) ?></div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>