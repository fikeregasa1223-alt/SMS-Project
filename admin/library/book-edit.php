<?php
// book-edit.php (concise)
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';
sms_session_start();
require_login();
require_permission('manage_attendance');

$id = (int)($_GET['id'] ?? 0);
$data = Database::fetch("SELECT * FROM library_books WHERE id = ?", [$id]);
if (!$data) { flash_set('error', 'Book not found.'); redirect(url('admin/library/books.php')); }

$errors = [];
$cats = Database::fetchAll("SELECT id, name FROM library_categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['title','author','publisher','isbn','edition','year','shelf'] as $k) $data[$k] = clean((string) post($k, ''));
    $data['category_id'] = (int) post('category_id', 0);
    $data['total_copies'] = (int) post('total_copies', 1);
    $data['status'] = (string) post('status', 'active');

    if (!v_required($data['title'])) $errors['title'] = 'Title required.';

    if (!$errors) {
        Database::update('library_books', [
            'title' => $data['title'],
            'author' => $data['author'] ?: null,
            'publisher' => $data['publisher'] ?: null,
            'isbn' => $data['isbn'] ?: null,
            'category_id' => $data['category_id'] ?: null,
            'edition' => $data['edition'] ?: null,
            'year' => $data['year'] !== '' ? (int)$data['year'] : null,
            'total_copies' => $data['total_copies'],
            'shelf' => $data['shelf'] ?: null,
            'status' => $data['status'],
        ], 'id = :id', ['id' => $id]);
        audit((int)current_user()['id'], 'book_updated', 'library_books', $id, 'Book updated: ' . $data['title']);
        flash_set('success', 'Book updated.');
        redirect(url('admin/library/books.php'));
    }
}

$pageTitle = 'Edit Book';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <form method="post" novalidate><?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="<?= e($data['title']) ?>" required></div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Author</label><input type="text" name="author" class="form-control" value="<?= e((string)$data['author']) ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Publisher</label><input type="text" name="publisher" class="form-control" value="<?= e((string)$data['publisher']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Category</label>
                <select name="category_id" class="form-select"><option value="0">—</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$data['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">ISBN</label><input type="text" name="isbn" class="form-control" value="<?= e((string)$data['isbn']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Total Copies</label><input type="number" name="total_copies" class="form-control" value="<?= (int)$data['total_copies'] ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Edition</label><input type="text" name="edition" class="form-control" value="<?= e((string)$data['edition']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Year</label><input type="number" name="year" class="form-control" value="<?= e((string)$data['year']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Shelf</label><input type="text" name="shelf" class="form-control" value="<?= e((string)$data['shelf']) ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" <?= $data['status']==='active'?'selected':'' ?>>Active</option>
                <option value="archived" <?= $data['status']==='archived'?'selected':'' ?>>Archived</option>
            </select>
        </div>
        <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button>
        <a class="btn btn-outline-primary" href="<?= e(url('admin/library/books.php')) ?>">Cancel</a>
    </form>
</div></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>