<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/validation.php';

sms_session_start();
require_login();
require_permission('manage_attendance');

$errors = [];
$data = ['title'=>'','author'=>'','publisher'=>'','isbn'=>'','category_id'=>0,'edition'=>'','year'=>'','total_copies'=>1,'available_copies'=>1,'shelf'=>'','status'=>'active'];
$cats = Database::fetchAll("SELECT id, name FROM library_categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_or_die();
    foreach (['title','author','publisher','isbn','edition','year','shelf'] as $k) $data[$k] = clean((string) post($k, ''));
    $data['category_id']    = (int) post('category_id', 0);
    $data['total_copies']   = (int) post('total_copies', 1);
    $data['available_copies'] = (int) post('total_copies', 1); // start available = total
    $data['status']         = (string) post('status', 'active');

    if (!v_required($data['title'])) $errors['title'] = 'Title required.';
    if ($data['total_copies'] < 1) $errors['total_copies'] = 'At least 1 copy required.';

    if (!$errors) {
        $id = Database::insert('library_books', [
            'title'            => $data['title'],
            'author'           => $data['author'] ?: null,
            'publisher'        => $data['publisher'] ?: null,
            'isbn'             => $data['isbn'] ?: null,
            'category_id'      => $data['category_id'] ?: null,
            'edition'          => $data['edition'] ?: null,
            'year'             => $data['year'] !== '' ? (int)$data['year'] : null,
            'total_copies'     => $data['total_copies'],
            'available_copies' => $data['total_copies'],
            'shelf'            => $data['shelf'] ?: null,
            'status'           => $data['status'],
        ]);
        audit((int)current_user()['id'], 'book_created', 'library_books', $id, 'Book added: ' . $data['title']);
        flash_set('success', 'Book added to library.');
        redirect(url('admin/library/books.php'));
    }
    if ($errors) $errors['form'] = 'Please correct the highlighted fields.';
}

$pageTitle = 'New Book';
$breadcrumbs = [['label' => 'Library', 'url' => 'admin/library/index.php'], ['label' => 'New Book']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header"><div><h1><?= e($pageTitle) ?></h1></div></div>

<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-body">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-8 mb-3"><label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($data['title']) ?>" required>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">ISBN</label><input type="text" name="isbn" class="form-control" value="<?= e($data['isbn']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Author</label><input type="text" name="author" class="form-control" value="<?= e($data['author']) ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Publisher</label><input type="text" name="publisher" class="form-control" value="<?= e($data['publisher']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $data['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Edition</label><input type="text" name="edition" class="form-control" value="<?= e($data['edition']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Year</label><input type="number" name="year" class="form-control" value="<?= e($data['year']) ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Total Copies <span class="text-danger">*</span></label><input type="number" min="1" name="total_copies" class="form-control" value="<?= (int)$data['total_copies'] ?>" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Shelf</label><input type="text" name="shelf" class="form-control" value="<?= e($data['shelf']) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $data['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="archived" <?= $data['status']==='archived'?'selected':'' ?>>Archived</option>
                </select>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Add Book</button>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/library/books.php')) ?>">Cancel</a>
        </div>
    </form>
</div></div></div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>