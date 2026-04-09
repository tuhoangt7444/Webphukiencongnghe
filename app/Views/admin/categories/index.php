<?php
use App\Core\View;
$rows = $rows ?? [];
$status = (string)($status ?? '');
$filters = $filters ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 10, 'total_pages' => 1];

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
    'sort' => (string)($filters['sort'] ?? 'created_at'),
    'direction' => (string)($filters['direction'] ?? 'desc'),
];

$buildPageUrl = static function(int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/categories?' . http_build_query(array_filter($query, static fn($value) => $value !== ''));
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý danh mục</h4>
        <small class="text-muted">Tổ chức danh mục sản phẩm, biểu tượng và trạng thái hiển thị</small>
    </div>
    <a href="/admin/categories/create" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Thêm danh mục
    </a>
</div>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã thêm danh mục thành công.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật danh mục thành công.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa danh mục thành công.</div>
<?php elseif ($status === 'has-products'): ?>
    <div class="alert alert-warning">Không thể xóa danh mục đang có sản phẩm.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Danh mục không tồn tại.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tổng số danh mục</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int)($pagination['total'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đang hiển thị</div>
                <div class="fs-4 fw-bold text-success"><?= number_format(count(array_filter($rows, static fn($row) => ($row['status'] ?? 'active') === 'active'))) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đang ẩn</div>
                <div class="fs-4 fw-bold text-secondary"><?= number_format(count(array_filter($rows, static fn($row) => ($row['status'] ?? 'active') === 'hidden'))) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/categories" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="q" class="form-control" placeholder="Nhập tên danh mục hoặc slug..." value="<?= View::e((string)($filters['q'] ?? '')) ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label">Sắp xếp theo</label>
                <select name="sort" class="form-select">
                    <option value="created_at" <?= (string)($filters['sort'] ?? 'created_at') === 'created_at' ? 'selected' : '' ?>>Ngày tạo</option>
                    <option value="name" <?= (string)($filters['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Tên danh mục</option>
                    <option value="product_count" <?= (string)($filters['sort'] ?? '') === 'product_count' ? 'selected' : '' ?>>Số lượng sản phẩm</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Thứ tự</label>
                <select name="direction" class="form-select">
                    <option value="desc" <?= (string)($filters['direction'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Giảm dần</option>
                    <option value="asc" <?= (string)($filters['direction'] ?? '') === 'asc' ? 'selected' : '' ?>>Tăng dần</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                <a href="/admin/categories" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <strong>Danh sách danh mục</strong>
            <small class="text-muted d-block">Tìm thấy <?= number_format((int)($pagination['total'] ?? 0)) ?> danh mục</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Biểu tượng</th>
                <th>Tên danh mục</th>
                <th>Mô tả</th>
                <th>Sản phẩm</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">Không có danh mục phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $cat): ?>
                    <tr>
                        <td>#<?= (int)$cat['id'] ?></td>
                        <td>
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border" style="width:42px;height:42px;">
                                <i class="fa-solid <?= View::e((string)($cat['icon'] ?: 'fa-folder-tree')) ?> text-primary"></i>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= View::e((string)$cat['name']) ?></div>
                            <small class="text-muted"><?= View::e((string)$cat['slug']) ?></small>
                        </td>
                        <td>
                            <div class="text-muted small"><?= View::e((string)($cat['description'] !== '' ? $cat['description'] : 'Chưa có mô tả')) ?></div>
                        </td>
                        <td>
                            <span class="badge text-bg-info"><?= number_format((int)$cat['product_count']) ?></span>
                        </td>
                        <td>
                            <?php if (($cat['status'] ?? 'active') === 'active'): ?>
                                <span class="badge text-bg-success">Hiển thị</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e((string)date('d/m/Y', strtotime((string)$cat['created_at']))) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="/admin/categories/<?= (int)$cat['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form method="POST" action="/admin/categories/<?= (int)$cat['id'] ?>/delete" onsubmit="return confirm('Xóa danh mục này?')">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ((int)($pagination['total_pages'] ?? 1) > 1): ?>
    <nav class="mt-3" aria-label="Pagination">
        <ul class="pagination mb-0">
            <?php $current = (int)($pagination['page'] ?? 1); ?>
            <?php $last = (int)($pagination['total_pages'] ?? 1); ?>

            <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $current > 1 ? View::e($buildPageUrl($current - 1)) : '#' ?>">&lt;</a>
            </li>

            <?php for ($i = 1; $i <= $last; $i++): ?>
                <li class="page-item <?= $i === $current ? 'active' : '' ?>">
                    <a class="page-link" href="<?= View::e($buildPageUrl($i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= $current >= $last ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $current < $last ? View::e($buildPageUrl($current + 1)) : '#' ?>">&gt;</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>