<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

sms_session_start();
require_login();
require_role('parent');

$user = current_user();
$parent = parent_for_user((int)$user['id']);
if (!$parent) { flash_set('error', 'No parent record linked.'); redirect(url('parent/dashboard.php')); }

$children = parent_children((int)$parent['id']);

$pageTitle       = 'My Children';
$pageDescription = 'Your linked children.';
$breadcrumbs     = [['label' => 'Children']];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<?php if (!$children): ?>
    <div class="table-wrap"><?= ui_empty('No children linked', 'Contact the administrator.', 'bi-people') ?></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($children as $c):
            $photo = !empty($c['photo']) ? url('uploads/profiles/' . $c['photo']) : null;
            $name  = trim(($c['first_name'] ?? '') . ' ' . ($c['middle_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            $init  = mb_strtoupper(mb_substr($c['first_name'] ?? 'S', 0, 1) . mb_substr($c['last_name'] ?? '', 0, 1));
        ?>
            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="avatar avatar-lg mx-auto mb-3">
                            <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="">
                            <?php else: ?><?= e($init) ?><?php endif; ?>
                        </div>
                        <h5 class="mb-1"><?= e($name) ?></h5>
                        <div class="text-muted small mb-2"><?= e($c['student_code']) ?></div>
                        <div class="mb-3"><?= student_status_badge($c['status']) ?></div>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="<?= e(url('parent/attendance.php?student_id=' . (int)$c['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check2-square"></i> Attendance
                            </a>
                            <a href="<?= e(url('parent/results.php?student_id=' . (int)$c['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-trophy"></i> Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>