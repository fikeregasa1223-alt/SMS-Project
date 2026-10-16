<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$errors = [];
$data = ['book_id'=>0,'student_id'=>0,'teacher_id'=>0,'issue_date'=>date('Y-m-d'),'due_date'=>date('Y-m-d', strtotime('+'.LIBRARY_DEFAULT_LOAN_DAYS.' days')),'notes'=>''];

$books = Database::fetchAll("SELECT id, title, isbn, available_copies FROM library_books WHERE status='active' AND available_copies > 0 ORDER BY title ASC");
$students = Database::fetchAll("SELECT id, student_code, first_name, last_name FROM students WHERE status='active' ORDER BY first_name ASC");
$teachers = Database::fetchAll("SELECT id, teacher_code, first_name, last_name FROM teachers ORDER BY first_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    $data['book_id']    = (int) post('book_id', 0);
    $data['student_id'] = (int) post('student_id', 0);
    $data['teacher_id'] = (int) post('teacher_id', 0);
    $data['issue_date'] = clean((string) post('issue_date', ''));
    $data['due_date']   = clean((string) post('due_date', ''));
    $data['notes']      = clean((string) post('notes', ''));

    if ($data['book_id'] <= 0) $errors['book_id'] = 'Book required.';
    if ($data['student_id'] <= 0 && $data['teacher_id'] <= 0) $errors['student_id'] = 'Select a student or teacher.';
    if (!v_date($data['issue_date'])) $errors['issue_date'] = 'Valid issue date required.';
    if (!v_date($data['due_date']))   $errors['due_date'] = 'Valid due date required.';

    if (!$errors) {
        $book = Database::fetch("SELECT available_copies FROM library_books WHERE id = ?", [$data['book_id']]);
        if (!$book || (int)$book['available_copies'] <= 0) $errors['book_id'] = 'Book not available.';
    }

    if (!$errors) {
        Database::begin();
        try {
            $issueId = Database::insert('library_issues', [
                'book_id'    => $data['book_id'],
                'student_id' => $data['student_id'] ?: null,
                'teacher_id' => $data['teacher_id'] ?: null,
                'issued_by'  => (int) current_user()['id'],
                'issue_date' => $data['issue_date'],
                'due_date'   => $data['due_date'],
                'status'     => 'issued',
                'notes'      => $data['notes'] ?: null,
            ]);
            library_mark_issued($data['book_id']);
            audit((int)current_user()['id'], 'book_issued', 'library_issues', $issueId, 'Book issued');
            Database::commit();
            flash_set('success', 'Book issued.');
            redirect(url('admin/library/issues.php'));
        } catch (Throwable $e) {
            Database::rollback();
            $errors['form'] = 'Failed: ' . (SMS_ENV === 'development' ? $e->getMessage() : '');
        }
    }
    if ($errors) $errors['form'] = $errors['form'] ?? 'Please correct the highlighted fields.';
}

$pageTitle = 'Issue Book';
$breadcrumbs = [['label' => 'Library', 'url' => 'admin/library/index.php'], ['label' => 'Issue']];
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" novalidate><?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Book <span class="text-danger">*</span></label>
            <select name="book_id" class="form-select" required>
                <option value="0">— Select —</option>
                <?php foreach ($books as $b): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= $data['book_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                        <?= e($b['title']) ?><?= $b['isbn'] ? ' (' . e($b['isbn']) . ')' : '' ?> — <?= (int)$b['available_copies'] ?> available
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Student</label>
                <select name="student_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= $data['student_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['student_code']) ?> — <?= e($s['first_name'] . ' ' . $s['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Teacher</label>
                <select name="teacher_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $data['teacher_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= e($t['teacher_code']) ?> — <?= e($t['first_name'] . ' ' . $t['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Issue Date</label><input type="date" name="issue_date" class="form-control" value="<?= e($data['issue_date']) ?>" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="<?= e($data['due_date']) ?>" required></div>
        </div>
        <div class="mb-3"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" value="<?= e($data['notes']) ?>"></div>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Issue</button>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/library/issues.php')) ?>">Cancel</a>
        </div>
    </form>
</div></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>