<?php
use App\Core\View;

$order = $order ?? null;
$items = $items ?? [];
$statusOptions = $statusOptions ?? [];
$flash = (string)($flash ?? '');

if (!$order) {
    echo '<div class="alert alert-warning">Không tìm thấy đơn hàng.</div>';
    return;
}

$statusLabel = static function(string $status, array $options): string {
    if ($status === 'rejected') {
        return 'Đã hủy';
    }
    return (string)($options[$status] ?? $status);
};

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

$address = trim(implode(', ', array_filter([
    (string)($order['address_line'] ?? ''),
    (string)($order['ward'] ?? ''),
    (string)($order['district'] ?? ''),
    (string)($order['city'] ?? ''),
])));

$promoUsed = '';
if (preg_match('/\[(.*?)\]/u', (string)($order['customer_note'] ?? ''), $m)) {
    $promoUsed = trim((string)($m[1] ?? ''));
}

$campaignDiscountTotal = (int)($order['campaign_discount_total'] ?? 0);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Chi tiết đơn hàng #<?= (int)$order['id'] ?></h4>
        <small class="text-muted">Ngày đặt: <?= View::e((string)date('d/m/Y H:i', strtotime((string)$order['created_at']))) ?></small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">In hóa đơn</button>
        <a href="/admin/orders" class="btn btn-outline-dark">Quay lại</a>
    </div>
</div>

<?php if ($flash === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái đơn hàng.</div>
<?php elseif ($flash === 'cancelled'): ?>
    <div class="alert alert-success">Đơn hàng đã được hủy.</div>
<?php elseif ($flash === 'invalid'): ?>
    <div class="alert alert-warning">Trạng thái không hợp lệ.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><strong>Thông tin khách hàng</strong></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-6"><strong>Tên:</strong> <?= View::e((string)$order['full_name']) ?></div>
                    <div class="col-12 col-md-6"><strong>Số điện thoại:</strong> <?= View::e((string)$order['phone']) ?></div>
                    <div class="col-12 col-md-6"><strong>Email:</strong> <?= View::e((string)$order['email']) ?></div>
                    <div class="col-12 col-md-6"><strong>Thanh toán:</strong> <?= View::e((string)$order['payment_method_name']) ?></div>
                    <div class="col-12"><strong>Địa chỉ:</strong> <?= View::e($address !== '' ? $address : 'Chưa có') ?></div>
                    <?php if ($promoUsed !== ''): ?>
                        <div class="col-12"><strong>Ưu đãi đã dùng:</strong> <span class="text-success fw-semibold"><?= View::e($promoUsed) ?></span></div>
                    <?php endif; ?>
                    <?php if ($campaignDiscountTotal > 0): ?>
                        <div class="col-12"><strong>Giảm theo chiến dịch sản phẩm:</strong> <span class="text-danger fw-semibold">-<?= number_format($campaignDiscountTotal) ?>đ</span></div>
                    <?php endif; ?>
                    <?php if ((string)$order['customer_note'] !== ''): ?>
                        <div class="col-12"><strong>Ghi chú:</strong> <?= View::e((string)$order['customer_note']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><strong>Cập nhật trạng thái</strong></div>
            <div class="card-body">
                <p class="mb-2">Trạng thái hiện tại:</p>
                <span class="badge <?= $statusBadge((string)$order['status']) ?> mb-3">
                    <?= View::e($statusLabel((string)$order['status'], $statusOptions)) ?>
                </span>

                <form method="POST" action="/admin/orders/<?= (int)$order['id'] ?>/status" class="d-flex gap-2 mb-2">
                    <input type="hidden" name="redirect" value="show">
                    <select name="status" class="form-select" required>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= View::e((string)$value) ?>" <?= (string)$order['status'] === (string)$value ? 'selected' : '' ?>>
                                <?= View::e((string)$label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit">Cập nhật</button>
                </form>

                <form method="POST" action="/admin/orders/<?= (int)$order['id'] ?>/cancel">
                    <input type="hidden" name="redirect" value="show">
                    <input type="hidden" name="stock_action" value="restore">
                    <button class="btn btn-outline-danger w-100" type="button" onclick="submitCancelOrder(this.form)">Hủy đơn hàng</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Danh sách sản phẩm trong đơn</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên sản phẩm</th>
                    <th class="text-end">Giá bán</th>
                    <th class="text-end">Số lượng</th>
                    <th class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">Không có sản phẩm trong đơn.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $sellingPrice = (int)($item['selling_price'] ?? 0);
                            $unitPrice = (int)($item['unit_price'] ?? $sellingPrice);
                            $discountRaw = (float)($item['discount_pct'] ?? 0);
                            $campaignDiscountAmount = (int)($item['campaign_discount_amount'] ?? 0);
                            $discountPercent = $discountRaw > 0 && $discountRaw <= 1
                                ? (int)round($discountRaw * 100)
                                : (int)round($discountRaw);
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= View::e((string)$item['product_name']) ?></div>
                                <small class="text-muted"><?= View::e((string)($item['variant_name'] ?? '')) ?></small>
                                <?php if ($discountPercent > 0 && $sellingPrice > $unitPrice): ?>
                                    <div><small class="text-danger">Giảm theo chiến dịch sản phẩm (<?= $discountPercent ?>%) -<?= number_format($campaignDiscountAmount) ?>đ</small></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($discountPercent > 0 && $sellingPrice > $unitPrice): ?>
                                    <div class="text-muted text-decoration-line-through small"><?= number_format($sellingPrice) ?>đ</div>
                                    <div class="fw-semibold text-danger"><?= number_format($unitPrice) ?>đ</div>
                                <?php else: ?>
                                    <?= number_format($sellingPrice) ?>đ
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format((int)($item['qty'] ?? 0)) ?></td>
                            <td class="text-end fw-bold"><?= number_format((int)($item['line_total'] ?? 0)) ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Tạm tính</th>
                    <th class="text-end"><?= number_format((int)($order['subtotal'] ?? 0)) ?>đ</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Phí vận chuyển</th>
                    <th class="text-end"><?= number_format((int)($order['shipping_fee'] ?? 0)) ?>đ</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Giảm theo chiến dịch sản phẩm</th>
                    <th class="text-end text-danger">-<?= number_format($campaignDiscountTotal) ?>đ</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Giảm giá</th>
                    <th class="text-end">-<?= number_format((int)($order['discount_total'] ?? 0)) ?>đ</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end text-primary">Tổng thanh toán</th>
                    <th class="text-end text-primary fs-5"><?= number_format((int)($order['total'] ?? 0)) ?>đ</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
@media print {
    .btn, .topbar, .admin-sidebar { display: none !important; }
    .admin-main { margin-left: 0 !important; }
    body { background: #fff !important; }
}
</style>

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
