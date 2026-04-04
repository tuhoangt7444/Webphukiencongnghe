<?php
use App\Core\View;

$old = is_array($old ?? null) ? $old : [];
$error = (string)($error ?? '');
$categories = is_array($categories ?? null) ? $categories : [];
$customerTypeOptions = is_array($customerTypeOptions ?? null) ? $customerTypeOptions : [];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Tạo phiếu giảm giá mới</h4>
        <small class="text-muted">Thiết lập thông tin và thời gian áp dụng phiếu</small>
    </div>
    <a href="/admin/vouchers" class="btn btn-outline-secondary">Quay lại danh sách</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/admin/vouchers" class="row g-3">
            <div class="col-12">
                <label class="form-label">Tên phiếu giảm giá *</label>
                <input type="text" name="name" class="form-control" required value="<?= View::e((string)($old['name'] ?? '')) ?>" placeholder="Ví dụ: Giảm 50.000 cho chuột gaming">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Mã phiếu *</label>
                <input type="text" name="code" class="form-control text-uppercase" required value="<?= View::e((string)($old['code'] ?? '')) ?>" placeholder="Ví dụ: MOUSE50">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Giá trị giảm *</label>
                <input type="number" min="1" name="discount_amount" class="form-control" required value="<?= (int)($old['discount_amount'] ?? 50000) ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Ngày bắt đầu *</label>
                <input type="date" name="start_date" class="form-control" required value="<?= View::e((string)($old['start_date'] ?? date('Y-m-d'))) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Ngày kết thúc *</label>
                <input type="date" name="end_date" class="form-control" required value="<?= View::e((string)($old['end_date'] ?? date('Y-m-d', strtotime('+30 days')))) ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Số lượng phiếu *</label>
                <input type="number" min="0" name="quantity" class="form-control" required value="<?= (int)($old['quantity'] ?? 100) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="active" <?= (string)($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="disabled" <?= (string)($old['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Đã tắt</option>
                </select>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Áp dụng cho danh mục</label>
                <select name="apply_category_id" class="form-select">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php $selected = (int)($old['apply_category_id'] ?? 0) === (int)($cat['id'] ?? 0); ?>
                        <option value="<?= (int)($cat['id'] ?? 0) ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= View::e((string)($cat['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Dành cho loại khách hàng</label>
                <select name="customer_type" class="form-select">
                    <?php $selectedCustomerType = (string)($old['customer_type'] ?? 'all'); ?>
                    <?php foreach ($customerTypeOptions as $key => $label): ?>
                        <option value="<?= View::e((string)$key) ?>" <?= $selectedCustomerType === (string)$key ? 'selected' : '' ?>>
                            <?= View::e((string)$label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Lưu phiếu giảm giá</button>
                <a href="/admin/vouchers" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
