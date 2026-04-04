<?php
use App\Core\View;

$rows = $rows ?? [];
$filters = $filters ?? [];
$stats = $stats ?? [];
$status = (string)($status ?? '');
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 12, 'total_pages' => 1];

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
];

$buildPageUrl = static function (int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/vouchers?' . http_build_query(array_filter($query, static fn($value) => $value !== ''));
};

$statusLabel = static function (string $value): string {
    return match ($value) {
        'active' => 'Đang hoạt động',
        'disabled' => 'Đã tắt',
        'expired' => 'Hết hạn',
        default => 'Không xác định',
    };
};

$statusClass = static function (string $value): string {
    return match ($value) {
        'active' => 'success',
        'disabled' => 'secondary',
        'expired' => 'danger',
        default => 'dark',
    };
};

$customerTypeLabel = static function (string $value): string {
    return match ($value) {
        'new' => 'Khách mới',
        'low' => 'Chi tiêu thấp',
        'mid' => 'Chi tiêu vừa',
        'vip' => 'Khách VIP',
        default => 'Tất cả khách',
    };
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý phiếu giảm giá</h4>
        <small class="text-muted">Tạo, theo dõi và kiểm soát phiếu giảm giá cho khách hàng</small>
    </div>
    <a href="/admin/vouchers/create" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Tạo phiếu giảm giá
    </a>
</div>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã tạo phiếu giảm giá thành công.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật phiếu giảm giá thành công.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa phiếu giảm giá thành công.</div>
<?php elseif ($status === 'toggled'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái phiếu giảm giá.</div>
<?php elseif ($status === 'in-use'): ?>
    <div class="alert alert-warning">Không thể xóa vì phiếu đã có khách nhận.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy phiếu giảm giá.</div>
<?php elseif ($status === 'expired-lock'): ?>
    <div class="alert alert-warning">Phiếu đã hết hạn, không thể bật lại.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tổng phiếu</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đang hoạt động</div>
                <div class="fs-4 fw-bold text-success"><?= number_format((int)($stats['active_count'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đã tắt</div>
                <div class="fs-4 fw-bold text-secondary"><?= number_format((int)($stats['disabled_count'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Hết hạn</div>
                <div class="fs-4 fw-bold text-danger"><?= number_format((int)($stats['expired_count'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/vouchers" class="row g-2 align-items-end">
            <div class="col-12 col-lg-8">
                <label class="form-label">Tìm kiếm phiếu giảm giá</label>
                <input type="text" name="q" class="form-control" value="<?= View::e((string)($filters['q'] ?? '')) ?>" placeholder="Nhập tên hoặc mã phiếu...">
            </div>
            <div class="col-12 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                <a href="/admin/vouchers" class="btn btn-outline-secondary">Đặt lại</a>
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
                    <th>Tên phiếu</th>
                    <th>Mã phiếu</th>
                    <th>Giảm</th>
                    <th>Thời gian</th>
                    <th>Danh mục áp dụng</th>
                    <th>Loại khách hàng</th>
                    <th>Số lượng còn</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Không có phiếu giảm giá phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $voucherStatus = (string)($row['status'] ?? 'disabled');
                            $canToggle = $voucherStatus !== 'expired';
                        ?>
                        <tr>
                            <td>#<?= (int)$row['id'] ?></td>
                            <td class="fw-semibold"><?= View::e((string)($row['name'] ?? '')) ?></td>
                            <td><code><?= View::e((string)($row['code'] ?? '')) ?></code></td>
                            <td class="text-danger fw-bold">-<?= number_format((int)($row['discount_amount'] ?? 0)) ?>đ</td>
                            <td>
                                <div class="small"><?= View::e(date('d/m/Y', strtotime((string)($row['start_date'] ?? '')))) ?> - <?= View::e(date('d/m/Y', strtotime((string)($row['end_date'] ?? '')))) ?></div>
                            </td>
                            <td><?= View::e((string)($row['apply_category_name'] ?? 'Tất cả danh mục')) ?></td>
                            <td><?= View::e($customerTypeLabel((string)($row['customer_type'] ?? 'all'))) ?></td>
                            <td><?= number_format((int)($row['quantity'] ?? 0)) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $statusClass($voucherStatus) ?>">
                                    <?= View::e($statusLabel($voucherStatus)) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="/admin/vouchers/<?= (int)$row['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Chỉnh sửa</a>

                                    <form method="POST" action="/admin/vouchers/<?= (int)$row['id'] ?>/toggle">
                                        <button class="btn btn-sm btn-outline-warning" type="submit" <?= $canToggle ? '' : 'disabled' ?>>
                                            <?= $voucherStatus === 'active' ? 'Tắt' : 'Bật' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/vouchers/<?= (int)$row['id'] ?>/delete" onsubmit="return confirm('Xóa phiếu giảm giá này?')">
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
