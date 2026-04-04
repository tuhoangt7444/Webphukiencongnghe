<?php
$reviews = $reviews ?? [];

$statusLabel = static function (string $status): string {
    return match ($status) {
        'visible' => 'Đã hiển thị',
        'hidden' => 'Đang chờ duyệt',
        'spam' => 'Không hợp lệ',
        default => 'Không xác định',
    };
};
?>

<section class="py-5 bg-light" style="min-height:60vh">
    <div class="container px-3 px-lg-4">
        <div class="mx-auto" style="max-width:1100px">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-sm-between gap-2 mb-4">
                <div>
                    <p class="text-uppercase fw-bold mb-1" style="letter-spacing:.16em;font-size:.72rem;color:#0e7490">Đánh giá</p>
                    <h1 class="mt-1 mb-0 h3 fw-bold text-dark">Lịch sử đánh giá</h1>
                </div>
                <a href="/orders/history" class="fw-semibold text-decoration-none">Về lịch sử mua hàng</a>
            </div>

            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:16px">
                <?php if (empty($reviews)): ?>
                    <div class="p-5 text-center text-muted">Bạn chưa gửi đánh giá nào.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light text-uppercase text-muted" style="font-size:.75rem">
                                <tr>
                                    <th class="px-3 py-3">Sản phẩm</th>
                                    <th class="px-3 py-3">Đánh giá</th>
                                    <th class="px-3 py-3">Trạng thái</th>
                                    <th class="px-3 py-3">Thời gian</th>
                                    <th class="px-3 py-3 text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $review): ?>
                                    <?php
                                    $productUrl = trim((string)($review['product_slug'] ?? '')) !== ''
                                        ? '/product/' . rawurlencode((string)$review['product_slug'])
                                        : '/products/' . (int)($review['product_id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="px-3 py-3">
                                            <div class="fw-semibold"><?= htmlspecialchars((string)($review['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted text-truncate" style="max-width:360px"><?= htmlspecialchars((string)($review['comment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td class="px-3 py-3 text-warning fw-semibold"><?= str_repeat('★', max(1, min(5, (int)($review['rating'] ?? 0)))) ?></td>
                                        <td class="px-3 py-3"><span class="badge rounded-pill text-bg-info"><?= htmlspecialchars($statusLabel((string)($review['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="px-3 py-3 small text-muted"><?= htmlspecialchars((string)($review['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-3 py-3 text-end"><a href="<?= htmlspecialchars($productUrl . '?tab=review', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Xem sản phẩm</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>