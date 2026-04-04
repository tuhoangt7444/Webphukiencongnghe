<?php
use App\Core\View;

$product = $product ?? [];
$error = (string)($error ?? '');
$old = is_array($old ?? null) ? $old : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Nhập thêm hàng</h4>
        <small class="text-muted">Cập nhật số lượng tồn kho cho sản phẩm</small>
    </div>
    <a href="/admin/inventory" class="btn btn-outline-secondary">Quay lại danh sách</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/admin/inventory/import/<?= (int)($product['id'] ?? 0) ?>" class="row g-3">
            <div class="col-12">
                <label class="form-label">Sản phẩm</label>
                <input type="text" class="form-control" value="<?= View::e((string)($product['name'] ?? '')) ?>" disabled>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Số lượng nhập *</label>
                <input type="number" name="quantity" min="1" required class="form-control" value="<?= (int)($old['quantity'] ?? 1) ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Tồn kho hiện tại</label>
                <input type="text" class="form-control" value="<?= number_format((int)($product['stock'] ?? 0)) ?>" disabled>
            </div>

            <div class="col-12">
                <label class="form-label">Ghi chú</label>
                <textarea name="note" class="form-control" rows="3" placeholder="Nhập ghi chú nhập kho (tùy chọn)"><?= View::e((string)($old['note'] ?? '')) ?></textarea>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-success">Xác nhận nhập kho</button>
                <a href="/admin/inventory" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
