<?php
use App\Core\View;

$rows = $rows ?? [];
$stats = $stats ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 10, 'total_pages' => 1];
$filters = $filters ?? [];
$categories = $categories ?? [];
$status = (string)($status ?? '');

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
    'category_id' => (string)($filters['category_id'] ?? ''),
    'status' => (string)($filters['status'] ?? ''),
    'min_price' => (string)($filters['min_price'] ?? ''),
    'max_price' => (string)($filters['max_price'] ?? ''),
];

$buildPageUrl = static function(int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/products?' . http_build_query(array_filter($query, static fn($v) => $v !== ''));
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý sản phẩm</h4>
        <small class="text-muted">Quản trị danh mục sản phẩm e-commerce</small>
    </div>
    <a href="/admin/products/create" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i>Thêm sản phẩm
    </a>
</div>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã thêm sản phẩm mới thành công.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật sản phẩm thành công.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa sản phẩm thành công.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tổng số sản phẩm</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int)($stats['total_products'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sản phẩm còn hàng</div>
                <div class="fs-4 fw-bold text-success"><?= number_format((int)($stats['in_stock_products'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sản phẩm hết hàng</div>
                <div class="fs-4 fw-bold text-danger"><?= number_format((int)($stats['out_of_stock_products'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sản phẩm mới trong tháng</div>
                <div class="fs-4 fw-bold text-warning"><?= number_format((int)($stats['new_this_month'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/products" class="row g-2 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="q" class="form-control" placeholder="Tìm tên sản phẩm hoặc mã SKU..." value="<?= View::e((string)($filters['q'] ?? '')) ?>">
            </div>

            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Tất cả</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (string)($filters['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                            <?= View::e((string)$cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Giá từ</label>
                <input type="number" min="0" name="min_price" class="form-control" value="<?= View::e((string)($filters['min_price'] ?? '')) ?>">
            </div>

            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Giá đến</label>
                <input type="number" min="0" name="max_price" class="form-control" value="<?= View::e((string)($filters['max_price'] ?? '')) ?>">
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="in_stock" <?= (string)($filters['status'] ?? '') === 'in_stock' ? 'selected' : '' ?>>Còn hàng</option>
                    <option value="out_of_stock" <?= (string)($filters['status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Hết hàng</option>
                    <option value="hidden" <?= (string)($filters['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                <a href="/admin/products" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <strong>Danh sách sản phẩm</strong>
            <small class="text-muted d-block">Tìm thấy <?= number_format((int)($pagination['total'] ?? 0)) ?> sản phẩm</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ID sản phẩm</th>
                <th>Hình ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá bán</th>
                <th>Giá gốc</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">Không có sản phẩm phù hợp bộ lọc.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $product): ?>
                    <tr>
                        <td>#<?= (int)$product['id'] ?></td>
                        <td>
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= View::e((string)$product['image_url']) ?>" alt="Product" style="width:56px;height:56px;object-fit:cover;border-radius:10px;">
                            <?php else: ?>
                                <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                    <i class="fa-regular fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= View::e((string)$product['name']) ?></div>
                            <small class="text-muted">SKU: <?= View::e((string)($product['sku'] ?? 'N/A')) ?></small><br>
                            <small class="text-muted">Thương hiệu: <?= View::e((string)($product['brand_name'] ?? 'Chưa chọn')) ?></small>
                        </td>
                        <td><?= View::e((string)($product['category_name'] ?? 'Chưa phân loại')) ?></td>
                        <td class="fw-bold text-primary"><?= number_format((int)($product['sale_price'] ?? 0)) ?>đ</td>
                        <td><?= number_format((int)($product['base_price'] ?? 0)) ?>đ</td>
                        <td>
                            <span class="badge <?= (int)($product['stock_total'] ?? 0) > 0 ? 'text-bg-success' : 'text-bg-danger' ?>">
                                <?= number_format((int)($product['stock_total'] ?? 0)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($product['is_active'])): ?>
                                <span class="badge text-bg-success">Hiển thị</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e((string)date('d/m/Y', strtotime((string)$product['created_at']))) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="/products/<?= (int)$product['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Xem</a>
                                <a href="/admin/products/<?= (int)$product['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form action="/admin/products/<?= (int)$product['id'] ?>/delete" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
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