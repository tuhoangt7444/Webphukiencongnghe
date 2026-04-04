<?php
use App\Core\View;

$row = $row ?? [];
$error = (string)($error ?? '');
$categories = is_array($categories ?? null) ? $categories : [];
$customerTypeOptions = is_array($customerTypeOptions ?? null) ? $customerTypeOptions : [];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Chỉnh sửa phiếu giảm giá</h4>
        <small class="text-muted">Cập nhật phiếu #<?= (int)($row['id'] ?? 0) ?></small>
    </div>
    <a href="/admin/vouchers" class="btn btn-outline-secondary">Quay lại danh sách</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/admin/vouchers/<?= (int)($row['id'] ?? 0) ?>" class="row g-3">
            <div class="col-12">
                <label class="form-label">Tên phiếu giảm giá *</label>
                <input type="text" name="name" class="form-control" required value="<?= View::e((string)($row['name'] ?? '')) ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Mã phiếu *</label>
                <input type="text" name="code" class="form-control text-uppercase" required value="<?= View::e((string)($row['code'] ?? '')) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Giá trị giảm *</label>
                <input type="number" min="1" name="discount_amount" class="form-control" required value="<?= (int)($row['discount_amount'] ?? 1) ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Ngày bắt đầu *</label>
                <input type="date" name="start_date" class="form-control" required value="<?= View::e((string)($row['start_date'] ?? date('Y-m-d'))) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Ngày kết thúc *</label>
                <input type="date" name="end_date" class="form-control" required value="<?= View::e((string)($row['end_date'] ?? date('Y-m-d'))) ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Số lượng phiếu *</label>
                <input type="number" min="0" name="quantity" class="form-control" required value="<?= (int)($row['quantity'] ?? 0) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="active" <?= (string)($row['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="disabled" <?= (string)($row['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Đã tắt</option>
                    <option value="expired" <?= (string)($row['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Hết hạn</option>
                </select>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Áp dụng cho danh mục</label>
                <select name="apply_category_id" class="form-select">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php $selected = (int)($row['apply_category_id'] ?? 0) === (int)($cat['id'] ?? 0); ?>
                        <option value="<?= (int)($cat['id'] ?? 0) ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= View::e((string)($cat['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Dành cho loại khách hàng</label>
                <select name="customer_type" class="form-select">
                    <?php $selectedCustomerType = (string)($row['customer_type'] ?? 'all'); ?>
                    <?php foreach ($customerTypeOptions as $key => $label): ?>
                        <option value="<?= View::e((string)$key) ?>" <?= $selectedCustomerType === (string)$key ? 'selected' : '' ?>>
                            <?= View::e((string)$label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                <a href="/admin/vouchers" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
