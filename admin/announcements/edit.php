<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_announcements');

$id  = (int) ($_GET['id'] ?? 0);
$row = Database::fetch("SELECT * FROM announcements WHERE id = ?", [$id]);
if (!$row) { flash_set('error', 'Announcement not found.'); redirect(url('admin/announcements/index.php')); }

$errors = [];
$data   = $row;

$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");

// Prepare values for datetime-local inputs
if (empty($data['publish_date'])) $data['publish_date'] = date('Y-m-d\TH:i');
else                              $data['publish_date'] = date('Y-m-d\TH:i', strtotime($data['publish_date']));

if (!empty($data['expiry_date'])) {
    $data['expiry_date'] = date('Y-m-d\TH:i', strtotime($data['expiry_date']));
} else {
    $data['expiry_date'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['title']        = clean((string) post('title', ''));
    $data['content']      = (string) post('content', '');
    $data['priority']     = (string) post('priority', 'normal');
    $data['audience']     = (string) post('audience', 'everyone');
    $data['grade_id']     = (int) post('grade_id', 0);
    $data['section_id']   = (int) post('section_id', 0);
    $data['publish_date'] = clean((string) post('publish_date', ''));
    $data['expiry_date']  = clean((string) post('expiry_date', ''));
    $data['status']       = (string) post('status', 'draft');

    if (!v_required($data['title']))   $errors['title'] = 'Title is required.';
    if (!v_required($data['content'])) $errors['content'] = 'Content is required.';
    if (!v_in_array($data['priority'], announcement_priority_list())) $errors['priority'] = 'Invalid priority.';
    if (!v_in_array($data['audience'], announcement_audience_list())) $errors['audience'] = 'Invalid audience.';
    if (!v_in_array($data['status'],   announcement_status_list()))   $errors['status'] = 'Invalid status.';
    if ($data['audience'] === 'grade'   && $data['grade_id']   <= 0) $errors['grade_id'] = 'Select a grade.';
    if ($data['audience'] === 'section' && $data['section_id'] <= 0) $errors['section_id'] = 'Select a section.';

    if (!$errors) {
        Database::update('announcements', [
            'title'        => $data['title'],
            'content'      => $data['content'],
            'priority'     => $data['priority'],
            'audience'     => $data['audience'],
            'grade_id'     => $data['audience'] === 'grade' || $data['audience'] === 'section' ? ($data['grade_id'] ?: null) : null,
            'section_id'   => $data['audience'] === 'section' ? ($data['section_id'] ?: null) : null,
            'publish_date' => date('Y-m-d H:i:s', strtotime($data['publish_date'])),
            'expiry_date'  => $data['expiry_date'] !== '' ? date('Y-m-d H:i:s', strtotime($data['expiry_date'])) : null,
            'status'       => $data['status'],
        ], 'id = :id', ['id' => $id]);

        audit((int)current_user()['id'], 'announcement_updated', 'announcements', $id, 'Announcement updated');
        flash_set('success', 'Announcement updated.');
        redirect(url('admin/announcements/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'Edit Announcement';
$pageDescription = 'Update an announcement.';
$breadcrumbs     = [['label' => 'Announcements', 'url' => 'admin/announcements/index.php'], ['label' => 'Edit']];
$extraCss        = ['assets/css/announcements.css'];
$extraJs         = ['assets/js/announcements.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div></div>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= e($data['title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea id="annContent" name="content" rows="8" class="form-control" required><?= e($data['content']) ?></textarea>
                    <div class="d-flex justify-content-between">
                        <div class="form-text">Plain text.</div>
                        <div class="form-text" id="annCounter">0 chars</div>
                    </div>
                </div>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <?php foreach (announcement_priority_list() as $pr): ?>
                            <option value="<?= e($pr) ?>" <?= $data['priority'] === $pr ? 'selected' : '' ?>><?= e(ucfirst($pr)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Audience</label>
                    <select name="audience" id="annAudience" class="form-select">
                        <?php foreach (announcement_audience_list() as $au): ?>
                            <option value="<?= e($au) ?>" <?= $data['audience'] === $au ? 'selected' : '' ?>><?= e(announcement_audience_label($au)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="gradeWrap">
                    <label class="form-label">Grade</label>
                    <select name="grade_id" class="form-select">
                        <option value="0">— Select —</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= (int)$g['id'] ?>" <?= (int)($data['grade_id'] ?? 0) === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="sectionWrap">
                    <label class="form-label">Section</label>
                    <select name="section_id" class="form-select">
                        <option value="0">— Select —</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= (int)$sec['id'] ?>" <?= (int)($data['section_id'] ?? 0) === (int)$sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Publish Date</label>
                    <input type="datetime-local" name="publish_date" class="form-control" value="<?= e($data['publish_date']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Expiry Date</label>
                    <input type="datetime-local" name="expiry_date" class="form-control" value="<?= e($data['expiry_date']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (announcement_status_list() as $st): ?>
                            <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div></div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2 me-1"></i> Save</button>
                <a href="<?= e(url('admin/announcements/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>