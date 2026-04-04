<?php
use App\Core\View;

$rows = $rows ?? [];
$stats = $stats ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 12, 'total_pages' => 1];
$filters = $filters ?? [];
$paymentMethods = $paymentMethods ?? [];
$statusOptions = $statusOptions ?? [];
$flash = (string)($flash ?? '');

// Check for message from redirect
$msg = trim((string)($_GET['msg'] ?? ''));
if ($msg === 'updated') {
    $flash = 'updated';
} elseif ($msg === 'cancelled') {
    $flash = 'cancelled';
}


$statusBadge = static function(string $status): string {
    return match ($status) {
        'pending_approval' => 'text-bg-warning',
        'approved' => 'text-bg-info',
        'shipping' => 'text-bg-primary',
        'done' => 'text-bg-success',
        'cancelled', 'rejected' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
};

$statusLabel = static function(string $status, array $options): string {
    if ($status === 'rejected') {
        return 'Đã hủy';
    }
    return (string)($options[$status] ?? $status);
};

$baseQuery = [
    'q' => (string)($filters['q'] ?? ''),
    'status' => (string)($filters['status'] ?? ''),
    'date_from' => (string)($filters['date_from'] ?? ''),
    'date_to' => (string)($filters['date_to'] ?? ''),
    'payment_method' => (string)($filters['payment_method'] ?? ''),
];

$buildPageUrl = static function(int $page) use ($baseQuery): string {
    $query = $baseQuery;
    $query['page'] = (string)$page;
    return '/admin/orders?' . http_build_query(array_filter($query, static fn($value) => $value !== ''));
};

$promoNote = static function(string $note): string {
    if (preg_match('/\[(.*?)\]/u', $note, $m)) {
        return trim((string)($m[1] ?? ''));
    }
    return '';
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý đơn hàng</h4>
        <small class="text-muted">Theo dõi toàn bộ đơn đặt hàng của khách</small>
    </div>
</div>

<?php if ($flash === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái đơn hàng.</div>
<?php elseif ($flash === 'cancelled'): ?>
    <div class="alert alert-success">Đơn hàng đã được hủy.</div>
<?php elseif ($flash === 'invalid'): ?>
    <div class="alert alert-warning">Trạng thái không hợp lệ.</div>
<?php elseif ($flash === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy đơn hàng.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đơn hôm nay</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int)($stats['today_orders'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đơn chờ xác nhận</div>
                <div class="fs-4 fw-bold text-warning"><?= number_format((int)($stats['pending_orders'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đơn đang giao</div>
                <div class="fs-4 fw-bold text-info"><?= number_format((int)($stats['shipping_orders'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đơn hoàn thành</div>
                <div class="fs-4 fw-bold text-success"><?= number_format((int)($stats['done_orders'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/orders" class="row g-2 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="q" class="form-control" placeholder="Tìm mã đơn / tên khách / số điện thoại" value="<?= View::e((string)($filters['q'] ?? '')) ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= View::e((string)$value) ?>" <?= (string)($filters['status'] ?? '') === (string)$value ? 'selected' : '' ?>>
                            <?= View::e((string)$label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Ngày từ</label>
                <input type="date" name="date_from" class="form-control" value="<?= View::e((string)($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Ngày đến</label>
                <input type="date" name="date_to" class="form-control" value="<?= View::e((string)($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label">Thanh toán</label>
                <select name="payment_method" class="form-select">
                    <option value="">Tất cả</option>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= View::e((string)$method['code']) ?>" <?= (string)($filters['payment_method'] ?? '') === (string)$method['code'] ? 'selected' : '' ?>>
                            <?= View::e((string)$method['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">Lọc đơn hàng</button>
                <a href="/admin/orders" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <strong>Danh sách đơn hàng</strong>
            <small class="text-muted d-block">Tìm thấy <?= number_format((int)($pagination['total'] ?? 0)) ?> đơn hàng</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã đơn</th>
                    <th>Tên khách hàng</th>
                    <th>Số điện thoại</th>
                    <th class="text-end">Tổng tiền</th>
                    <th class="text-end">Giảm giá</th>
                    <th>Phương thức thanh toán</th>
                    <th>Trạng thái đơn hàng</th>
                    <th>Ngày đặt hàng</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">Không có đơn hàng phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $order): ?>
                    <?php
                    $discountTotal = (int)($order['discount_total'] ?? 0);
                    $campaignDiscountTotal = (int)($order['campaign_discount_total'] ?? 0);
                    $hasCampaignDiscount = !empty($order['has_campaign_discount']);
                    $orderPromoNote = $promoNote((string)($order['customer_note'] ?? ''));
                    ?>
                    <tr>
                        <td>#<?= (int)$order['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= View::e((string)($order['full_name'] !== '' ? $order['full_name'] : $order['email'])) ?></div>
                            <small class="text-muted"><?= View::e((string)$order['email']) ?></small>
                        </td>
                        <td><?= View::e((string)$order['phone']) ?></td>
                        <td class="text-end fw-bold text-primary"><?= number_format((int)($order['total'] ?? 0)) ?>đ</td>
                        <td class="text-end">
                            <div class="discount-display">
                                <?php if ($discountTotal > 0): ?>
                                    <div class="fw-semibold text-success">-<?= number_format($discountTotal) ?>đ</div>
                                    <?php if (!empty($orderPromoNote)): ?>
                                        <small class="text-muted"><?= View::e($orderPromoNote) ?></small>
                                    <?php endif; ?>

                                <?php elseif ($hasCampaignDiscount && $campaignDiscountTotal > 0): ?>
                                    <div class="fw-semibold text-danger">
                                        -<?= number_format($campaignDiscountTotal) ?>đ
                                        <br>
                                        <small>(Chiến dịch SP)</small>
                                    </div>

                                <?php else: ?>
                                    <span class="text-muted">0đ</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= View::e((string)($order['payment_method_name'] ?? 'COD')) ?></td>
                        <td>
                            <span class="badge <?= $statusBadge((string)$order['status']) ?> mb-1">
                                <?= View::e($statusLabel((string)$order['status'], $statusOptions)) ?>
                            </span>
                            <form method="POST" action="/admin/orders/<?= (int)$order['id'] ?>/status" class="d-flex gap-1 mt-1">
                                <input type="hidden" name="redirect" value="index">
                                <input type="hidden" name="q" value="<?= View::e($filters['q'] ?? '') ?>">
                                <input type="hidden" name="status_filter" value="<?= View::e($filters['status'] ?? '') ?>">
                                <input type="hidden" name="date_from" value="<?= View::e($filters['date_from'] ?? '') ?>">
                                <input type="hidden" name="date_to" value="<?= View::e($filters['date_to'] ?? '') ?>">
                                <input type="hidden" name="payment_method" value="<?= View::e($filters['payment_method'] ?? '') ?>">
                                <input type="hidden" name="page" value="<?= View::e((string)($pagination['page'] ?? '1')) ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach ($statusOptions as $value => $label): ?>
                                        <option value="<?= View::e((string)$value) ?>" <?= (string)$order['status'] === (string)$value ? 'selected' : '' ?>>
                                            <?= View::e((string)$label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Lưu</button>
                            </form>
                        </td>
                        <td><?= View::e((string)date('d/m/Y H:i', strtotime((string)$order['created_at']))) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="/admin/orders/<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-secondary">Xem</a>
                                <form method="POST" action="/admin/orders/<?= (int)$order['id'] ?>/cancel">
                                    <input type="hidden" name="redirect" value="index">
                                    <input type="hidden" name="stock_action" value="restore">
                                    <input type="hidden" name="q" value="<?= View::e($filters['q'] ?? '') ?>">
                                    <input type="hidden" name="date_from" value="<?= View::e($filters['date_from'] ?? '') ?>">
                                    <input type="hidden" name="date_to" value="<?= View::e($filters['date_to'] ?? '') ?>">
                                    <input type="hidden" name="payment_method" value="<?= View::e($filters['payment_method'] ?? '') ?>">
                                    <input type="hidden" name="page" value="<?= View::e((string)($pagination['page'] ?? '1')) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="button" onclick="submitCancelOrder(this.form)">Hủy đơn</button>
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

<!-- Modal for confirming cancel order -->
<div class="modal fade" id="confirmCancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận hủy đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc muốn hủy đơn này?</p>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-danger" onclick="showStockActionModal()">Hủy đơn</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for selecting stock action -->
<div class="modal fade" id="stockActionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xử lý tồn kho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Chọn cách xử lý tồn kho khi hủy đơn:</p>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-warning" onclick="confirmCancelOrder('restore')">Hoàn lại tồn kho</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancelOrder('keep')">Giữ nguyên tồn kho</button>
            </div>
        </div>
    </div>
</div>

<script>
var _cancelOrderForm = null;
var _confirmCancelModal = null;
var _stockActionModal = null;

function submitCancelOrder(form) {
    _cancelOrderForm = form;
    _confirmCancelModal = new bootstrap.Modal(document.getElementById('confirmCancelModal'));
    _confirmCancelModal.show();
}

function showStockActionModal() {
    if (_confirmCancelModal) {
        _confirmCancelModal.hide();
    }
    _stockActionModal = new bootstrap.Modal(document.getElementById('stockActionModal'));
    _stockActionModal.show();
}

function confirmCancelOrder(action) {
    if (!_cancelOrderForm) return;
    
    var stockInput = _cancelOrderForm.querySelector('input[name="stock_action"]');
    if (stockInput) {
        stockInput.value = action;
    }
    
    if (_stockActionModal) {
        _stockActionModal.hide();
    }
    
    _cancelOrderForm.submit();
}
</script>