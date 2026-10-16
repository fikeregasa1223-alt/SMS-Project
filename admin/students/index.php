<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_students');

$search    = table_filter('q');
$gradeId   = (int) ($_GET['grade_id'] ?? 0);
$sectionId = (int) ($_GET['section_id'] ?? 0);
$status    = (string) ($_GET['status'] ?? '');
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 20;

// Current enrollment join
$where  = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where .= " AND (s.first_name LIKE :q OR s.last_name LIKE :q OR s.student_code LIKE :q
                     OR s.admission_no LIKE :q OR s.email LIKE :q)";
    $params['q'] = '%' . $search . '%';
}
if ($status !== '') {
    $where .= " AND s.status = :st";
    $params['st'] = $status;
}
if ($gradeId > 0) {
    $where .= " AND e.grade_id = :g";
    $params['g'] = $gradeId;
}
if ($sectionId > 0) {
    $where .= " AND e.section_id = :sec";
    $params['sec'] = $sectionId;
}

// Count
$sqlCount = "SELECT COUNT(DISTINCT s.id)
             FROM students s
             LEFT JOIN enrollments e ON e.student_id = s.id AND e.status='active'
             $where";
$stmt = Database::conn()->prepare($sqlCount);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pg = paginate($total, $perPage, $page);

// Rows
$sql = "SELECT s.*,
               e.grade_id, e.section_id,
               g.name  AS grade_name,
               sec.name AS section_name
        FROM students s
        LEFT JOIN enrollments e ON e.student_id = s.id AND e.status='active'
        LEFT JOIN grades   g   ON g.id   = e.grade_id
        LEFT JOIN sections sec ON sec.id = e.section_id
        $where
        GROUP BY s.id
        ORDER BY s.id DESC
        LIMIT {$pg['limit']} OFFSET {$pg['offset']}";
$stmt = Database::conn()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Filter dropdowns
$grades   = Database::fetchAll("SELECT id, name FROM grades ORDER BY level ASC");
$sections = $gradeId > 0
    ? Database::fetchAll("SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name ASC", [$gradeId])
    : Database::fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

$pageTitle       = 'Students';
$pageDescription = 'Manage student records and enrollment.';
$breadcrumbs     = [['label' => 'Students']];
$extraCss        = ['assets/css/students.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="page-desc"><?= e($pageDescription) ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/students/create.php')) ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Student
        </a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control"
               placeholder="Search by name, code, admission no, email…">
    </div>
    <div class="col-md-2">
        <select name="grade_id" class="form-select" data-auto-submit>
            <option value="0">All grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $gradeId === (int)$g['id'] ? 'selected' : '' ?>>
                    <?= e($g['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="section_id" class="form-select" data-auto-submit>
            <option value="0">All sections</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= (int)$sec['id'] ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : '' ?>>
                    <?= e($sec['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select" data-auto-submit>
            <option value="">All statuses</option>
            <?php foreach (student_status_list() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-1">
        <button class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i></button>
        <a href="?" class="btn btn-ghost"><i class="bi bi-x-circle"></i></a>
    </div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty(
            'No students found',
            'There are no students matching your current filters.',
            'bi-people',
            url('admin/students/create.php'),
            'Add Student'
        ) ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Code</th>
                        <th>Admission No</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $s):
                    $photo = student_photo_url($s);
                    $name  = student_full_name($s);
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm">
                                    <?php if ($photo): ?>
                                        <img src="<?= e($photo) ?>" alt="">
                                    <?php else: ?>
                                        <?= e(mb_strtoupper(mb_substr($s['first_name'] ?? 'S', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1))) ?>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <div class="fw-semibold"><?= e($name) ?></div>
                                    <div class="text-muted small"><?= e($s['email'] ?: '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e($s['student_code']) ?></span></td>
                        <td><span class="text-muted small"><?= e($s['admission_no']) ?></span></td>
                        <td><?= e($s['grade_name'] ?: '—') ?></td>
                        <td><?= e($s['section_name'] ?: '—') ?></td>
                        <td><?= student_status_badge($s['status']) ?></td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'View',   'href' => url('admin/students/show.php?id='   . (int)$s['id']), 'icon' => 'bi-eye',    'tone' => 'outline-primary'],
                                ['label' => 'Edit',   'href' => url('admin/students/edit.php?id='   . (int)$s['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/students/delete.php?id=' . (int)$s['id']), 'icon' => 'bi-trash',  'tone' => 'outline-danger',
                                 'confirm' => 'Delete student "' . $name . '"? This cannot be undone.'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?> students</span>
            <?= render_pagination($total, $perPage, $pg['page'], [
                'q' => $search, 'grade_id' => $gradeId, 'section_id' => $sectionId, 'status' => $status
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php
$extraJs = ['assets/js/students.js'];
include __DIR__ . '/../../includes/footer.php';
?>