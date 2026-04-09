<?php
use App\Core\View;
$order = $order ?? null;
$items = $items ?? [];
$status = (string)($status ?? '');
if (!$order) { echo '<div class="alert alert-warning">Không tìm thấy đơn hàng.</div>'; return; }

function admin_order_status_label_detail(string $status): string {
    return match ($status) {
        'pending_approval' => 'Chờ xử lý',
        'shipping' => 'Đang giao',
        'done' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        default => $status,
    };
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Chi tiết đơn hàng #<?= (int)$order['id'] ?></h5>
    <a href="/admin/orders" class="btn btn-outline-secondary btn-sm">Quay lại</a>
</div>

<?php if ($status === 'updated'): ?><div class="alert alert-success">Cập nhật trạng thái thành công.</div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6>Thông tin khách hàng</h6>
                <p class="mb-1"><strong>Email:</strong> <?= View::e((string)$order['email']) ?></p>
                <p class="mb-1"><strong>Họ tên:</strong> <?= View::e((string)($order['full_name'] ?? '')) ?></p>
                <p class="mb-1"><strong>Điện thoại:</strong> <?= View::e((string)($order['phone'] ?? '')) ?></p>
                <p class="mb-0"><strong>Địa chỉ:</strong> <?= View::e(trim((string)($order['address_line'] ?? '') . ', ' . (string)($order['ward'] ?? '') . ', ' . (string)($order['district'] ?? '') . ', ' . (string)($order['city'] ?? ''))) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6>Trạng thái đơn hàng</h6>
                <p><span class="badge text-bg-info"><?= View::e(admin_order_status_label_detail((string)$order['status'])) ?></span></p>
                <form method="POST" action="/admin/orders/<?= (int)$order['id'] ?>/status" class="row g-2 align-items-end">
                    <div class="col-12 col-md-8">
                        <label class="form-label">Cập nhật trạng thái</label>
                        <select name="status" class="form-select" required>
                            <option value="pending_approval" <?= (string)$order['status'] === 'pending_approval' ? 'selected' : '' ?>>Chờ xử lý</option>
                            <option value="shipping" <?= (string)$order['status'] === 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                            <option value="done" <?= (string)$order['status'] === 'done' ? 'selected' : '' ?>>Đã hoàn thành</option>
                            <option value="cancelled" <?= (string)$order['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4"><button class="btn btn-primary w-100" type="submit">Cập nhật</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Sản phẩm trong đơn</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Sản phẩm</th><th>SKU</th><th class="text-end">Đơn giá</th><th class="text-end">SL</th><th class="text-end">Thành tiền</th></tr></thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">Không có dữ liệu sản phẩm.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= View::e((string)$item['product_name']) ?> <small class="text-muted"><?= View::e((string)($item['variant_name'] ?? '')) ?></small></td>
                        <td><?= View::e((string)($item['sku'] ?? '')) ?></td>
                        <td class="text-end"><?= number_format((int)($item['unit_price'] ?? 0)) ?>đ</td>
                        <td class="text-end"><?= (int)($item['qty'] ?? 0) ?></td>
                        <td class="text-end fw-bold"><?= number_format((int)($item['line_total'] ?? 0)) ?>đ</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="4" class="text-end">Tổng cộng</th><th class="text-end text-primary"><?= number_format((int)($order['total'] ?? 0)) ?>đ</th></tr>
            </tfoot>
        </table>
    </div>
</div>