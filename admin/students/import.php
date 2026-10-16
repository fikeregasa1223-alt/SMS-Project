<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('create_students');

$result = null;

if (($_GET['template'] ?? '') === 'students') {
    import_template_download('students');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    if (!empty($_FILES['csv']['tmp_name'])) {
        $tmp = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo((string)$_FILES['csv']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            flash_set('error', 'Only .csv files are accepted.');
        } elseif ((int)$_FILES['csv']['size'] > 5 * 1024 * 1024) {
            flash_set('error', 'File too large (max 5 MB).');
        } else {
            $result = import_students($tmp);
            audit((int)current_user()['id'], 'students_imported', 'students', null, 'Bulk import: ' . $result['imported'] . ' students');
            if ($result['ok']) {
                flash_set('success', $result['imported'] . ' students imported.');
            } else {
                flash_set('error', $result['error'] ?: 'Import failed.');
            }
        }
    }
}

$pageTitle = 'Bulk Import Students';
$breadcrumbs = [['label' => 'Students', 'url' => 'admin/students/index.php'], ['label' => 'Import']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h5>Upload CSV</h5>
        <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv" accept=".csv" class="form-control" required>
                <div class="form-text">Max 5 MB · UTF-8 encoding</div>
            </div>
            <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
            <a class="btn btn-outline-primary" href="?template=students"><i class="bi bi-download me-1"></i> Download Template</a>
        </form>
    </div></div></div>

    <?php if ($result): ?>
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h5>Result</h5>
        <p><strong><?= (int)$result['imported'] ?></strong> imported successfully.</p>
        <?php if (!empty($result['errors'])): ?>
            <p class="text-danger">Errors:</p>
            <ul class="small text-danger">
                <?php foreach ($result['errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div></div></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>