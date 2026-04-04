<?php
$orders = $orders ?? [];
$status = (string)($status ?? '');

function order_status_label(string $status): string {
    return match ($status) {
        'pending_approval' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy',
        'shipping' => 'Đang trên đường giao hàng',
        'done' => 'Hoàn tất',
        default => $status,
    };
}

function payment_method_label(string $code): string {
    return match ($code) {
        'cod' => 'Thanh toán khi nhận hàng (COD)',
        'bank' => 'Chuyển khoản ngân hàng',
        default => $code,
    };
}

function review_status_label(?string $status): string {
    return match ((string)$status) {
        'visible' => 'Đã hiển thị',
        'hidden' => 'Đang chờ duyệt',
        'spam' => 'Không hợp lệ',
        default => 'Chưa đánh giá',
    };
}

function order_promo_note(string $note): string {
    if (preg_match('/\[(.*?)\]/u', $note, $m)) {
        return trim((string)($m[1] ?? ''));
    }
    return '';
}

function can_cancel_order(string $orderStatus, string $createdAt): bool {
    if (in_array($orderStatus, ['cancelled', 'done', 'shipping', 'rejected'], true)) {
        return false;
    }

    try {
        $createdDateTime = new DateTime($createdAt);
        $currentDateTime = new DateTime('now');
        $totalSeconds = max(0, $currentDateTime->getTimestamp() - $createdDateTime->getTimestamp());
        return $totalSeconds < 600; // 600 seconds = 10 minutes
    } catch (Exception $e) {
        return false;
    }
}

function remaining_cancel_time(string $createdAt): int {
    try {
        $createdDateTime = new DateTime($createdAt);
        $currentDateTime = new DateTime('now');
        $totalSeconds = max(0, $currentDateTime->getTimestamp() - $createdDateTime->getTimestamp());
        $secondsRemaining = 600 - $totalSeconds; // 600 seconds = 10 minutes
        return max(0, (int)$secondsRemaining);
    } catch (Exception $e) {
        return 0;
    }
}
?>

<style>
.cancel-modal-content {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<section class="py-5 bg-light" style="min-height:60vh">
    <div class="container px-3 px-lg-4">
        <div class="mx-auto" style="max-width:1100px">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-sm-between gap-2 mb-4">
                <div>
                    <p class="text-uppercase fw-bold mb-1" style="letter-spacing:.16em;font-size:.72rem;color:#0e7490">Đơn hàng</p>
                    <h1 class="mt-1 mb-0 h3 fw-bold text-dark">Lịch sử mua hàng</h1>
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="/reviews/history" class="fw-semibold text-decoration-none">Lịch sử đánh giá</a>
                    <a href="/account" class="fw-semibold text-decoration-none">Về thông tin tài khoản</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:16px">
                <?php if ($status === 'placed'): ?>
                    <div class="alert alert-success mx-3 mt-3 mb-0">
                        Đặt hàng thành công. Đơn của bạn đang ở trạng thái chờ admin duyệt. Khi cần, bạn có thể quay lại lịch sử đơn để đánh giá các sản phẩm đã mua.
                    </div>
                <?php elseif ($status === 'payment-success'): ?>
                    <div class="alert alert-success mx-3 mt-3 mb-0">
                        Thanh toán ngân hàng thành công.
                    </div>
                <?php elseif ($status === 'payment-failed'): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0">
                        Thanh toán không thành công. Đơn hàng chưa được ghi nhận đã thanh toán.
                    </div>
                <?php elseif ($status === 'cancelled-success'): ?>
                    <div class="alert alert-success mx-3 mt-3 mb-0">
                        Đơn hàng đã hủy thành công. Chúng tôi sẽ xử lý hoàn tiền trong thời gian sớm nhất.
                    </div>
                <?php elseif ($status === 'cancelled-bank'): ?>
                    <div class="alert alert-success mx-3 mt-3 mb-0">
                        Đơn hàng đã hủy thành công. Vì bạn đã thanh toán bằng chuyển khoản, chúng tôi sẽ <strong>hoàn tiền vào tài khoản của bạn trong thời gian sớm nhất</strong>. Vui lòng kiểm tra lại tài khoản ngân hàng.
                    </div>
                <?php elseif ($status === 'order-timeout'): ?>
                    <div class="alert alert-warning mx-3 mt-3 mb-0">
                        Thời gian hủy đơn đã hết (tối đa 10 phút sau khi đặt). Vui lòng liên hệ với bộ phận hỗ trợ để hủy đơn.
                    </div>
                <?php elseif ($status === 'order-cannot-cancel'): ?>
                    <div class="alert alert-warning mx-3 mt-3 mb-0">
                        Đơn hàng này không thể hủy vì nó đang trong trạng thái xử lý hoặc đã hoàn tất.
                    </div>
                <?php elseif ($status === 'order-not-found'): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0">
                        Không tìm thấy đơn hàng cần hủy.
                    </div>
                <?php elseif ($status === 'order-not-yours'): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0">
                        Bạn không có quyền hủy đơn hàng này.
                    </div>
                <?php elseif ($status === 'order-invalid'): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0">
                        Dữ liệu đơn hàng không hợp lệ. Vui lòng thử lại.
                    </div>
                <?php elseif ($status === 'cancel-failed'): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0">
                        Hủy đơn hàng thất bại. Vui lòng thử lại hoặc liên hệ với bộ phận hỗ trợ.
                    </div>
                <?php endif; ?>

                <?php if (empty($orders)): ?>
                    <div class="p-5 text-center text-muted">Chưa có đơn hàng nào.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light text-uppercase text-muted" style="font-size:.75rem">
                                <tr>
                                    <th class="px-3 py-3">Mã đơn</th>
                                    <th class="px-3 py-3">Trạng thái</th>
                                    <th class="px-3 py-3">Phương thức thanh toán</th>
                                    <th class="px-3 py-3">Thời gian</th>
                                    <th class="px-3 py-3 text-end">Giảm giá</th>
                                    <th class="px-3 py-3 text-end">Tổng tiền</th>
                                    <th class="px-3 py-3 text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    $orderId = (int)$order['id'];
                                    $discountTotal = (int)($order['discount_total'] ?? 0);
                                    $promoNote = order_promo_note((string)($order['customer_note'] ?? ''));
                                    $orderStatus = (string)($order['status'] ?? '');
                                    $createdAt = (string)($order['created_at'] ?? '');
                                    $paymentCode = (string)($order['payment_method_code'] ?? 'cod');
                                    $canCancel = can_cancel_order($orderStatus, $createdAt);
                                    $remainingSeconds = remaining_cancel_time($createdAt);
                                    $remainingMinutes = (int)ceil($remainingSeconds / 60);
                                    ?>
                                    <tr>
                                        <td class="px-3 py-3 fw-semibold">#<?= $orderId ?></td>
                                        <td class="px-3 py-3">
                                            <span class="badge rounded-pill text-bg-info">
                                                <?= htmlspecialchars(order_status_label($orderStatus), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <small class="text-muted"><?= htmlspecialchars(payment_method_label($paymentCode), ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td class="px-3 py-3 small text-muted"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-3 py-3 text-end">
                                            <?php if ($discountTotal > 0): ?>
                                                <div class="fw-semibold text-success">-<?= number_format($discountTotal) ?>đ</div>
                                                <?php if ($promoNote !== ''): ?>
                                                    <div class="small text-muted"><?= htmlspecialchars($promoNote, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">0đ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 text-end fw-bold text-primary"><?= number_format((int)($order['total_amount'] ?? 0)) ?>đ</td>
                                        <td class="px-3 py-3 text-center">
                                            <?php if ($canCancel): ?>
                                                <button class="btn btn-sm btn-danger" data-order-id="<?= $orderId ?>" data-payment-code="<?= htmlspecialchars($paymentCode, ENT_QUOTES, 'UTF-8') ?>" onclick="showCancelModal(this)">
                                                    <i class="fa-solid fa-ban me-1"></i> Hủy (<?= $remainingMinutes ?>p)
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="bg-light-subtle px-3 py-3">
                                            <?php if (empty($order['items'])): ?>
                                                <div class="text-muted small">Chưa có chi tiết sản phẩm cho đơn hàng này.</div>
                                            <?php else: ?>
                                                <div class="d-grid gap-2">
                                                    <?php foreach ($order['items'] as $item): ?>
                                                        <?php
                                                        $productId = (int)($item['product_id'] ?? 0);
                                                        $productSlug = trim((string)($item['product_slug'] ?? ''));
                                                        $unitPrice = (int)($item['unit_price'] ?? 0);
                                                        $originalUnitPrice = (int)($item['original_unit_price'] ?? $unitPrice);
                                                        $discountPct = max(0, min(90, (int)($item['discount_pct'] ?? 0)));
                                                        $productUrl = $productSlug !== ''
                                                            ? '/product/' . rawurlencode($productSlug)
                                                            : '/products/' . $productId;
                                                        $reviewUrl = $productUrl . (str_contains($productUrl, '?') ? '&' : '?') . 'tab=review';
                                                        ?>
                                                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 border rounded-3 bg-white px-3 py-2">
                                                            <div>
                                                                <div class="fw-semibold"><?= htmlspecialchars((string)($item['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8') ?></div>
                                                                <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                                                                    <span>Số lượng: <?= (int)($item['qty'] ?? 0) ?></span>
                                                                    <span>•</span>
                                                                    <span>Đơn giá: <?= number_format($unitPrice) ?>đ</span>
                                                                    <?php if ($discountPct > 0 && $originalUnitPrice > $unitPrice): ?>
                                                                        <span class="text-decoration-line-through"><?= number_format($originalUnitPrice) ?>đ</span>
                                                                        <span class="badge rounded-pill text-bg-danger">-<?= $discountPct ?>%</span>
                                                                    <?php endif; ?>
                                                                    <span>•</span>
                                                                    <span>Thành tiền: <?= number_format((int)($item['line_total'] ?? 0)) ?>đ</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                                <?php if (!empty($item['review_id'])): ?>
                                                                    <span class="badge rounded-pill text-bg-success"><?= htmlspecialchars(review_status_label((string)($item['review_status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                                                    <a href="/reviews/history" class="btn btn-sm btn-outline-primary">Xem đánh giá</a>
                                                                <?php elseif (!empty($item['can_review'])): ?>
                                                                    <a href="<?= htmlspecialchars($reviewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">Đánh giá ngay</a>
                                                                <?php else: ?>
                                                                    <span class="badge rounded-pill text-bg-secondary">Chưa thể đánh giá</span>
                                                                <?php endif; ?>
                                                                <a href="<?= htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Xem sản phẩm</a>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
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

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cancel-modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Xác nhận hủy đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Bạn có chắc chắn muốn hủy đơn hàng này?</p>
                <div id="cancelMessage" class="alert alert-info mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Không, giữ đơn</button>
                <form id="cancelForm" method="POST" action="/orders/cancel" style="display:inline;">
                    <input type="hidden" name="order_id" id="cancelOrderId">
                    <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showCancelModal(button) {
    const orderId = button.getAttribute('data-order-id');
    const paymentCode = button.getAttribute('data-payment-code');
    
    document.getElementById('cancelOrderId').value = orderId;
    const messageEl = document.getElementById('cancelMessage');
    
    if (paymentCode === 'bank') {
        messageEl.innerHTML = '<i class="fa-solid fa-circle-info me-2"></i><strong>Lưu ý:</strong> Bạn đã thanh toán bằng chuyển khoản. Nếu hủy, chúng tôi sẽ hoàn tiền vào tài khoản của bạn trong thời gian sớm nhất.';
        messageEl.style.display = 'block';
    } else {
        messageEl.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
