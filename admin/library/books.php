<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('view_attendance');

$search = table_filter('q');
$catId  = (int) ($_GET['category_id'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where  = "WHERE b.status = 'active'";
$params = [];
if ($search !== '') { $where .= " AND (b.title LIKE :q OR b.author LIKE :q OR b.isbn LIKE :q)"; $params['q'] = '%' . $search . '%'; }
if ($catId > 0)     { $where .= " AND b.category_id = :c"; $params['c'] = $catId; }

$stmt = Database::conn()->prepare("SELECT COUNT(*) FROM library_books b $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = Database::conn()->prepare(
    "SELECT b.*, c.name AS category_name
     FROM library_books b
     LEFT JOIN library_categories c ON c.id = b.category_id
     $where
     ORDER BY b.id DESC
     LIMIT {$pg['limit']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$categories = Database::fetchAll("SELECT id, name FROM library_categories ORDER BY name ASC");

$pageTitle = 'Library Books';
$pageDescription = 'All books in the library.';
$breadcrumbs = [['label' => 'Library', 'url' => 'admin/library/index.php'], ['label' => 'Books']];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1><?= e($pageTitle) ?></h1><p class="page-desc"><?= e($pageDescription) ?></p></div>
    <div class="page-actions">
        <a href="<?= e(url('admin/library/index.php')) ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Library Home</a>
        <a href="<?= e(url('admin/library/book-create.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Book</a>
    </div>
</div>

<form class="filter-bar row g-2 align-items-center" method="get">
    <div class="col-md-4"><input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Title, author or ISBN…"></div>
    <div class="col-md-3">
        <select name="category_id" class="form-select" data-auto-submit>
            <option value="0">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $catId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Search</button></div>
    <div class="col-md-2"><a href="?" class="btn btn-ghost w-100">Reset</a></div>
</form>

<div class="table-wrap">
    <?php if (!$rows): ?>
        <?= ui_empty('No books', 'Add your first book.', 'bi-book', url('admin/library/book-create.php'), 'New Book') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>ISBN</th><th class="text-center">Available</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e($r['title']) ?></div><div class="text-muted small"><?= e($r['publisher'] ?: '') ?><?= $r['year'] ? ' · ' . (int)$r['year'] : '' ?></div></td>
                        <td><?= e($r['author'] ?: '—') ?></td>
                        <td><?= e($r['category_name'] ?: '—') ?></td>
                        <td class="text-muted small"><?= e($r['isbn'] ?: '—') ?></td>
                        <td class="text-center">
                            <span class="badge <?= (int)$r['available_copies'] > 0 ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' ?>">
                                <?= (int)$r['available_copies'] ?> / <?= (int)$r['total_copies'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?= ui_row_actions([
                                ['label' => 'Edit', 'href' => url('admin/library/book-edit.php?id=' . (int)$r['id']), 'icon' => 'bi-pencil', 'tone' => 'outline-primary'],
                                ['label' => 'Delete', 'href' => url('admin/library/book-delete.php?id=' . (int)$r['id']), 'icon' => 'bi-trash', 'tone' => 'outline-danger',
                                 'confirm' => 'Delete this book and all its issue records?'],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($rows) ?> of <?= number_format($total) ?></span>
            <?= render_pagination($total, $perPage, $pg['page'], ['q' => $search, 'category_id' => $catId]) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>