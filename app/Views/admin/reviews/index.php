<?php
use App\Core\View;

$rows = $rows ?? [];
$stats = $stats ?? [];
$avgByProduct = $avgByProduct ?? [];
$filters = $filters ?? [];
$statusMessage = (string)($statusMessage ?? '');
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 15, 'total_pages' => 1];

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
    'rating' => (string)($filters['rating'] ?? ''),
    'status' => (string)($filters['status'] ?? ''),
];

$buildPageUrl = static function (int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/reviews?' . http_build_query(array_filter($query, static fn($v) => $v !== ''));
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'visible' => 'Hiển thị',
        'hidden' => 'Ẩn',
        'spam' => 'Spam',
        default => 'Không rõ',
    };
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'visible' => 'success',
        'hidden' => 'secondary',
        'spam' => 'danger',
        default => 'dark',
    };
};

$starText = static function (int $rating): string {
    return str_repeat('★', max(1, min(5, $rating)));
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý đánh giá sản phẩm</h4>
        <small class="text-muted">Kiểm duyệt đánh giá từ khách hàng</small>
    </div>
</div>

<?php if ($statusMessage === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái đánh giá.</div>
<?php elseif ($statusMessage === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa đánh giá thành công.</div>
<?php elseif ($statusMessage === 'delete-denied'): ?>
    <div class="alert alert-warning">Chỉ được xóa đánh giá đã đánh dấu spam.</div>
<?php elseif ($statusMessage === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy đánh giá.</div>
<?php elseif ($statusMessage === 'invalid'): ?>
    <div class="alert alert-warning">Dữ liệu cập nhật không hợp lệ.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Tổng số đánh giá</div><div class="fs-4 fw-bold text-primary"><?= number_format((int)($stats['total_reviews'] ?? 0)) ?></div></div></div></div>
    <div class="col-12 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Đánh giá 5 sao</div><div class="fs-4 fw-bold text-success"><?= number_format((int)($stats['star_5'] ?? 0)) ?></div></div></div></div>
    <div class="col-12 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Đánh giá 4 sao</div><div class="fs-4 fw-bold text-info"><?= number_format((int)($stats['star_4'] ?? 0)) ?></div></div></div></div>
    <div class="col-12 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Đánh giá thấp (1-2 sao)</div><div class="fs-4 fw-bold text-danger"><?= number_format((int)($stats['low_star'] ?? 0)) ?></div></div></div></div>
</div>

<?php if (!empty($avgByProduct)): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white"><strong>Điểm trung bình theo sản phẩm</strong></div>
        <div class="card-body">
            <div class="row g-2">
                <?php foreach ($avgByProduct as $item): ?>
                    <div class="col-12 col-lg-6">
                        <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                            <span><?= View::e((string)$item['product_name']) ?></span>
                            <span class="fw-semibold text-warning"><?= number_format((float)$item['avg_rating'], 1) ?> sao</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/reviews" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label">Tìm theo sản phẩm hoặc khách hàng</label>
                <input type="text" class="form-control" name="q" value="<?= View::e((string)($filters['q'] ?? '')) ?>" placeholder="Nhập từ khóa...">
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label">Số sao</label>
                <select name="rating" class="form-select">
                    <option value="">Tất cả</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= (int)($filters['rating'] ?? 0) === $i ? 'selected' : '' ?>><?= $i ?> sao</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="visible" <?= (string)($filters['status'] ?? '') === 'visible' ? 'selected' : '' ?>>Visible</option>
                    <option value="hidden" <?= (string)($filters['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                    <option value="spam" <?= (string)($filters['status'] ?? '') === 'spam' ? 'selected' : '' ?>>Spam</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                <a href="/admin/reviews" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Khách hàng</th>
                    <th>Sản phẩm</th>
                    <th>Số sao</th>
                    <th>Nội dung đánh giá</th>
                    <th>Ngày đánh giá</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có đánh giá.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>#<?= (int)$row['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= View::e((string)$row['customer_name']) ?></div>
                                <small class="text-muted"><?= View::e((string)$row['customer_email']) ?></small>
                            </td>
                            <td><?= View::e((string)$row['product_name']) ?></td>
                            <td class="text-warning fw-semibold"><?= $starText((int)$row['rating']) ?></td>
                            <td>
                                <div style="max-width: 280px;" class="text-truncate" title="<?= View::e((string)$row['comment']) ?>">
                                    <?= View::e((string)$row['comment']) ?>
                                </div>
                            </td>
                            <td><?= View::e(date('d/m/Y H:i', strtotime((string)$row['created_at']))) ?></td>
                            <td><span class="badge text-bg-<?= $statusClass((string)$row['status']) ?>"><?= $statusLabel((string)$row['status']) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="/admin/reviews/<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>

                                    <?php if ((string)$row['status'] !== 'hidden'): ?>
                                        <form method="POST" action="/admin/reviews/<?= (int)$row['id'] ?>/status">
                                            <input type="hidden" name="status" value="hidden">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Ẩn đánh giá</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ((string)$row['status'] !== 'visible'): ?>
                                        <form method="POST" action="/admin/reviews/<?= (int)$row['id'] ?>/status">
                                            <input type="hidden" name="status" value="visible">
                                            <button class="btn btn-sm btn-outline-success" type="submit">Hiện đánh giá</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ((string)$row['status'] !== 'spam'): ?>
                                        <form method="POST" action="/admin/reviews/<?= (int)$row['id'] ?>/status">
                                            <input type="hidden" name="status" value="spam">
                                            <button class="btn btn-sm btn-outline-warning" type="submit">Đánh dấu spam</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" action="/admin/reviews/<?= (int)$row['id'] ?>/delete" onsubmit="return confirm('Chỉ xóa khi đây là nội dung spam/không phù hợp. Tiếp tục?')">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Xóa đánh giá</button>
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
