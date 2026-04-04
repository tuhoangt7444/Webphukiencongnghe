<?php
use App\Core\View;

$rows = $rows ?? [];
$stats = $stats ?? [];
$lowStock = $lowStock ?? [];
$filters = $filters ?? [];
$status = (string)($status ?? '');
$categories = $categories ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 12, 'total_pages' => 1];

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
    'category_id' => (string)($filters['category_id'] ?? ''),
    'stock_range' => (string)($filters['stock_range'] ?? ''),
];

$buildPageUrl = static function (int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/inventory?' . http_build_query(array_filter($query, static fn($v) => $v !== ''));
};

$statusLabel = static function (int $stock): string {
    if ($stock > 10) return 'Còn hàng';
    if ($stock >= 1) return 'Sắp hết';
    return 'Hết hàng';
};

$statusClass = static function (int $stock): string {
    if ($stock > 10) return 'success';
    if ($stock >= 1) return 'warning';
    return 'danger';
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý tồn kho</h4>
        <small class="text-muted">Theo dõi số lượng sản phẩm và cập nhật nhập/xuất kho</small>
    </div>
    <a href="/admin/inventory/logs" class="btn btn-outline-primary">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Lịch sử kho
    </a>
</div>

<?php if ($status === 'imported'): ?>
    <div class="alert alert-success">Đã nhập thêm hàng thành công.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy sản phẩm.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Tổng sản phẩm</div><div class="fs-4 fw-bold text-primary"><?= number_format((int)($stats['total_products'] ?? 0)) ?></div></div></div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Còn hàng</div><div class="fs-4 fw-bold text-success"><?= number_format((int)($stats['in_stock'] ?? 0)) ?></div></div></div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Sắp hết</div><div class="fs-4 fw-bold text-warning"><?= number_format((int)($stats['low_stock'] ?? 0)) ?></div></div></div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Hết hàng</div><div class="fs-4 fw-bold text-danger"><?= number_format((int)($stats['out_of_stock'] ?? 0)) ?></div></div></div>
    </div>
</div>

<?php if (!empty($lowStock)): ?>
    <div class="alert alert-warning">
        <strong>Sản phẩm sắp hết hàng:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($lowStock as $item): ?>
                <li>
                    <?= View::e((string)$item['name']) ?> - còn <?= (int)$item['stock'] ?> sản phẩm
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/inventory" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label">Tìm theo tên sản phẩm</label>
                <input type="text" name="q" class="form-control" value="<?= View::e((string)($filters['q'] ?? '')) ?>" placeholder="Nhập tên sản phẩm...">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)($filters['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= View::e((string)$cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label">Khoảng tồn kho</label>
                <select name="stock_range" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="out" <?= (string)($filters['stock_range'] ?? '') === 'out' ? 'selected' : '' ?>>Hết hàng (0)</option>
                    <option value="1-5" <?= (string)($filters['stock_range'] ?? '') === '1-5' ? 'selected' : '' ?>>1 đến 5</option>
                    <option value="6-10" <?= (string)($filters['stock_range'] ?? '') === '6-10' ? 'selected' : '' ?>>6 đến 10</option>
                    <option value="11-20" <?= (string)($filters['stock_range'] ?? '') === '11-20' ? 'selected' : '' ?>>11 đến 20</option>
                    <option value="21-50" <?= (string)($filters['stock_range'] ?? '') === '21-50' ? 'selected' : '' ?>>21 đến 50</option>
                    <option value="51+" <?= (string)($filters['stock_range'] ?? '') === '51+' ? 'selected' : '' ?>>51 trở lên (đến hết)</option>
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                <a href="/admin/inventory" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Số lượng tồn kho</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Không có sản phẩm phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $stock = (int)($row['stock'] ?? 0); ?>
                        <tr>
                            <td>#<?= (int)$row['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= View::e((string)$row['name']) ?></div>
                            </td>
                            <td><?= View::e((string)$row['category_name']) ?></td>
                            <td><?= number_format((int)($row['price'] ?? 0)) ?>đ</td>
                            <td><strong><?= number_format($stock) ?></strong></td>
                            <td><span class="badge text-bg-<?= $statusClass($stock) ?>"><?= $statusLabel($stock) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="/admin/inventory/import/<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-success">Nhập thêm hàng</a>
                                    <a href="/admin/inventory/logs?product_id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Xem lịch sử kho</a>
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
