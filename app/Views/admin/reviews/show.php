<?php
use App\Core\View;

$review = $review ?? [];
$statusMessage = (string)($statusMessage ?? '');

$starText = static function (int $rating): string {
    return str_repeat('★', max(1, min(5, $rating)));
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'visible' => 'Hiển thị',
        'hidden' => 'Ẩn',
        'spam' => 'Spam',
        default => 'Không rõ',
    };
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Chi tiết đánh giá</h4>
        <small class="text-muted">Review #<?= (int)($review['id'] ?? 0) ?></small>
    </div>
    <a href="/admin/reviews" class="btn btn-outline-secondary">Quay lại danh sách</a>
</div>

<?php if ($statusMessage === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái đánh giá.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label text-muted">Khách hàng</label>
                <div class="fw-semibold"><?= View::e((string)($review['customer_name'] ?? '')) ?></div>
                <div class="text-muted small"><?= View::e((string)($review['customer_email'] ?? '')) ?></div>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label text-muted">Sản phẩm</label>
                <div class="fw-semibold"><?= View::e((string)($review['product_name'] ?? '')) ?></div>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label text-muted">Số sao</label>
                <div class="text-warning fs-5"><?= $starText((int)($review['rating'] ?? 0)) ?></div>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label text-muted">Trạng thái</label>
                <div><span class="badge text-bg-secondary"><?= View::e($statusLabel((string)($review['status'] ?? ''))) ?></span></div>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label text-muted">Ngày đánh giá</label>
                <div><?= View::e(date('d/m/Y H:i', strtotime((string)($review['created_at'] ?? 'now')))) ?></div>
            </div>

            <div class="col-12">
                <label class="form-label text-muted">Nội dung đánh giá</label>
                <div class="border rounded p-3 bg-light">
                    <?= nl2br(View::e((string)($review['comment'] ?? ''))) ?>
                </div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <form method="POST" action="/admin/reviews/<?= (int)($review['id'] ?? 0) ?>/status">
                    <input type="hidden" name="status" value="visible">
                    <input type="hidden" name="redirect" value="show">
                    <button class="btn btn-outline-success" type="submit">Hiện đánh giá</button>
                </form>

                <form method="POST" action="/admin/reviews/<?= (int)($review['id'] ?? 0) ?>/status">
                    <input type="hidden" name="status" value="hidden">
                    <input type="hidden" name="redirect" value="show">
                    <button class="btn btn-outline-secondary" type="submit">Ẩn đánh giá</button>
                </form>

                <form method="POST" action="/admin/reviews/<?= (int)($review['id'] ?? 0) ?>/status">
                    <input type="hidden" name="status" value="spam">
                    <input type="hidden" name="redirect" value="show">
                    <button class="btn btn-outline-warning" type="submit">Đánh dấu spam</button>
                </form>

                <form method="POST" action="/admin/reviews/<?= (int)($review['id'] ?? 0) ?>/delete" onsubmit="return confirm('Chỉ xóa khi review là spam/không phù hợp. Tiếp tục?')">
                    <button class="btn btn-outline-danger" type="submit">Xóa đánh giá</button>
                </form>
            </div>
        </div>
    </div>
</div>
