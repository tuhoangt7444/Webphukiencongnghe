<?php
use App\Core\View;

$row = $row ?? null;
$orders = $orders ?? [];
$status = (string)($status ?? '');

if (!$row) {
    echo '<div class="alert alert-warning">Không tìm thấy khách hàng.</div>';
    return;
}

$statusLabel = (string)($row['status'] ?? 'active') === 'active' ? 'active' : 'blocked';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Chi tiết khách hàng #<?= (int)$row['id'] ?></h4>
        <small class="text-muted">Thông tin tài khoản và lịch sử đơn hàng</small>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/users/<?= (int)$row['id'] ?>/edit" class="btn btn-outline-primary">Chỉnh sửa</a>
        <a href="/admin/users" class="btn btn-outline-secondary">Quay lại</a>
    </div>
</div>

<?php if ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật thông tin khách hàng.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Thông tin cá nhân</strong></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-6"><strong>Tên:</strong> <?= View::e((string)$row['full_name']) ?></div>
                    <div class="col-12 col-md-6"><strong>Email:</strong> <?= View::e((string)$row['email']) ?></div>
                    <div class="col-12 col-md-6"><strong>Số điện thoại:</strong> <?= View::e((string)$row['phone']) ?></div>
                    <div class="col-12 col-md-6"><strong>Trạng thái:</strong> <span class="badge <?= $statusLabel === 'active' ? 'text-bg-success' : 'text-bg-danger' ?>\"><?= View::e($statusLabel) ?></span></div>
                    <div class="col-12 col-md-6"><strong>Ngày đăng ký:</strong> <?= View::e((string)date('d/m/Y H:i', strtotime((string)$row['created_at']))) ?></div>
                    <div class="col-12 col-md-6"><strong>Loại khách:</strong> <?= View::e((string)($row['customer_type'] ?? 'new')) ?></div>
                    <div class="col-12"><strong>Địa chỉ:</strong> <?= View::e((string)$row['address']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Lịch sử đơn hàng</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Mã đơn hàng</th>
                <th>Ngày đặt</th>
                <th class="text-end">Tổng tiền</th>
                <th>Trạng thái</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">Khách hàng chưa có đơn hàng.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= (int)$order['id'] ?></td>
                        <td><?= View::e((string)date('d/m/Y H:i', strtotime((string)$order['created_at']))) ?></td>
                        <td class="text-end fw-semibold"><?= number_format((int)$order['total']) ?>đ</td>
                        <td><?= View::e((string)$order['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
