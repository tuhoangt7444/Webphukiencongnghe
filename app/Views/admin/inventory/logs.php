<?php
use App\Core\View;

$rows = $rows ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 1];

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
    'type' => (string)($filters['type'] ?? ''),
    'product_id' => (string)($filters['product_id'] ?? ''),
];

$buildPageUrl = static function (int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/inventory/logs?' . http_build_query(array_filter($query, static fn($v) => $v !== ''));
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Lịch sử nhập/xuất kho</h4>
        <small class="text-muted">Theo dõi toàn bộ biến động tồn kho</small>
    </div>
    <a href="/admin/inventory" class="btn btn-outline-secondary">Quay lại tồn kho</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/inventory/logs" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label">Tìm theo sản phẩm</label>
                <input type="text" name="q" class="form-control" value="<?= View::e((string)($filters['q'] ?? '')) ?>" placeholder="Nhập tên sản phẩm...">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Loại</label>
                <select name="type" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="import" <?= (string)($filters['type'] ?? '') === 'import' ? 'selected' : '' ?>>Import</option>
                    <option value="export" <?= (string)($filters['type'] ?? '') === 'export' ? 'selected' : '' ?>>Export</option>
                </select>
            </div>
            <div class="col-12 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                <a href="/admin/inventory/logs" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Loại</th>
                    <th>Ghi chú</th>
                    <th>Ngày</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có lịch sử kho.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>#<?= (int)$row['id'] ?></td>
                            <td><?= View::e((string)$row['product_name']) ?></td>
                            <td><?= number_format((int)$row['quantity']) ?></td>
                            <td>
                                <?php if ((string)$row['type'] === 'import'): ?>
                                    <span class="badge text-bg-success">Import</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Export</span>
                                <?php endif; ?>
                            </td>
                            <td><?= View::e((string)($row['note'] ?? '')) ?></td>
                            <td><?= View::e(date('d/m/Y H:i', strtotime((string)$row['created_at']))) ?></td>
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
