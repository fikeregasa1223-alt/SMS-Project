<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_announcements');

$errors = [];
$data = [
    'title'        => '',
    'content'      => '',
    'priority'     => 'normal',
    'audience'     => 'everyone',
    'grade_id'     => 0,
    'section_id'   => 0,
    'publish_date' => date('Y-m-d\TH:i'),
    'expiry_date'  => '',
    'status'       => 'draft',
];

$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = Database::fetchAll("SELECT id, grade_id, name FROM sections ORDER BY name ASC");

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

    if (!v_required($data['title']))    $errors['title']   = 'Title is required.';
    if (!v_required($data['content']))  $errors['content'] = 'Content is required.';
    if (!v_in_array($data['priority'], announcement_priority_list())) $errors['priority'] = 'Invalid priority.';
    if (!v_in_array($data['audience'], announcement_audience_list())) $errors['audience'] = 'Invalid audience.';
    if (!v_in_array($data['status'],   announcement_status_list()))   $errors['status']   = 'Invalid status.';
    if ($data['audience'] === 'grade'   && $data['grade_id']   <= 0) $errors['grade_id']   = 'Select a grade.';
    if ($data['audience'] === 'section' && $data['section_id'] <= 0) $errors['section_id'] = 'Select a section.';
    if (!v_date(substr($data['publish_date'], 0, 10))) $errors['publish_date'] = 'Valid publish date required.';

    if (!$errors) {
        $publishAt = date('Y-m-d H:i:s', strtotime($data['publish_date']));
        $expiryAt  = $data['expiry_date'] !== '' ? date('Y-m-d H:i:s', strtotime($data['expiry_date'])) : null;

        // If publish_date <= now and status was draft but user wants publish → status stays as chosen
        $id = Database::insert('announcements', [
            'title'        => $data['title'],
            'content'      => $data['content'],
            'priority'     => $data['priority'],
            'audience'     => $data['audience'],
            'grade_id'     => $data['audience'] === 'grade' || $data['audience'] === 'section' ? ($data['grade_id'] ?: null) : null,
            'section_id'   => $data['audience'] === 'section' ? ($data['section_id'] ?: null) : null,
            'publish_date' => $publishAt,
            'expiry_date'  => $expiryAt,
            'status'       => $data['status'],
            'created_by'   => (int) current_user()['id'],
        ]);

        audit((int)current_user()['id'], 'announcement_created', 'announcements', $id, 'Announcement created: ' . $data['title']);
        flash_set('success', $data['status'] === 'published' ? 'Announcement published.' : 'Announcement saved as draft.');
        redirect(url('admin/announcements/index.php'));
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle       = 'New Announcement';
$pageDescription = 'Compose and publish an announcement.';
$breadcrumbs     = [['label' => 'Announcements', 'url' => 'admin/announcements/index.php'], ['label' => 'New']];
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
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               value="<?= e($data['title']) ?>" placeholder="e.g. Parent-Teacher Meeting" required>
                        <?php if (isset($errors['title'])): ?><div class="invalid-feedback d-block"><?= e($errors['title']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea id="annContent" name="content" rows="8"
                                  class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>"
                                  placeholder="Write the full announcement message…" required><?= e($data['content']) ?></textarea>
                        <div class="d-flex justify-content-between">
                            <div class="form-text">Plain text — line breaks are preserved.</div>
                            <div class="form-text" id="annCounter">0 chars</div>
                        </div>
                        <?php if (isset($errors['content'])): ?><div class="invalid-feedback d-block"><?= e($errors['content']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-sliders me-2"></i>Options</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <?php foreach (announcement_priority_list() as $pr): ?>
                                <option value="<?= e($pr) ?>" <?= $data['priority'] === $pr ? 'selected' : '' ?>>
                                    <?= e(ucfirst($pr)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Audience</label>
                        <select name="audience" id="annAudience" class="form-select">
                            <?php foreach (announcement_audience_list() as $au): ?>
                                <option value="<?= e($au) ?>" <?= $data['audience'] === $au ? 'selected' : '' ?>>
                                    <?= e(announcement_audience_label($au)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="gradeWrap" style="display:none;">
                        <label class="form-label">Grade</label>
                        <select name="grade_id" class="form-select">
                            <option value="0">— Select —</option>
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= (int)$g['id'] ?>" <?= $data['grade_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="sectionWrap" style="display:none;">
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-select">
                            <option value="0">— Select —</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= (int)$sec['id'] ?>" <?= $data['section_id'] === (int)$sec['id'] ? 'selected' : '' ?>>
                                    <?= e($sec['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Publish Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="publish_date" class="form-control"
                               value="<?= e($data['publish_date']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="datetime-local" name="expiry_date" class="form-control"
                               value="<?= e($data['expiry_date']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (announcement_status_list() as $st): ?>
                                <?php if ($st === 'expired') continue; ?>
                                <option value="<?= e($st) ?>" <?= $data['status'] === $st ? 'selected' : '' ?>>
                                    <?= e(ucfirst($st)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-check2 me-1"></i> Save
                </button>
                <a href="<?= e(url('admin/announcements/index.php')) ?>" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>