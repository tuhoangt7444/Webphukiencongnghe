<?php use App\Core\View; ?>
<?php $categories = $categories ?? []; ?>
<?php $error = $_SESSION['error'] ?? null; unset($_SESSION['error']); ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Sửa sản phẩm #<?= (int)$row['id'] ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= View::e((string)$error) ?></div>
        <?php endif; ?>

        <form action="/admin/products/<?= (int)$row['id'] ?>" method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-12">
                <h6 class="mb-1">Thông tin cơ bản</h6>
                <small class="text-muted">Cập nhật tên, danh mục, thương hiệu, mô tả và hình ảnh</small>
            </div>
            <div class="col-12 col-lg-8">
                <label class="form-label">Tên sản phẩm *</label>
                <input type="text" name="name" required class="form-control" value="<?= View::e((string)$row['name']) ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-control" value="<?= View::e((string)$row['slug']) ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Chọn danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ((string)($row['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                            <?= View::e((string)$cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Thương hiệu</label>
                <input type="text" name="brand_name" class="form-control" placeholder="Ví dụ: Logitech, Razer..." value="<?= View::e((string)($row['brand_name'] ?? '')) ?>">
                <small class="text-muted">Bạn có thể sửa hoặc nhập mới thương hiệu. Hệ thống sẽ tự tạo nếu chưa tồn tại.</small>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">SKU *</label>
                <input type="text" name="sku" required class="form-control" value="<?= View::e((string)$row['sku']) ?>">
            </div>
            <div class="col-6 col-md-3" id="inventory">
                <label class="form-label">Tồn kho</label>
                <input type="number" min="0" name="stock" class="form-control" value="<?= (int)($row['stock'] ?? $row['stock_total'] ?? 0) ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Ảnh chính mới (nếu muốn thay)</label>
                <input type="file" name="main_image" class="form-control" accept="image/*">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Thêm ảnh phụ</label>
                <input type="file" name="gallery_images[]" multiple class="form-control" accept="image/*">
            </div>

            <?php if (!empty($row['images'])): ?>
                <div class="col-12">
                    <label class="form-label">Ảnh hiện tại</label>
                    <small class="d-block text-muted mb-2">Tick vào ảnh muốn xóa rồi bấm "Lưu cập nhật".</small>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($row['images'] as $img): ?>
                            <label class="position-relative d-inline-flex flex-column align-items-center" style="cursor:pointer;">
                                <img src="<?= View::e((string)$img['image_url']) ?>" alt="image" style="width:84px;height:84px;object-fit:cover;border-radius:10px;border:1px solid #ddd;">
                                <span class="mt-1 small text-danger fw-semibold">Xóa</span>
                                <input type="checkbox" name="delete_image_ids[]" value="<?= (int)($img['id'] ?? 0) ?>" class="form-check-input position-absolute" style="top:4px;right:4px;">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12 mt-2">
                <h6 class="mb-1">Giá bán</h6>
                <small class="text-muted">Giá bán tự tính theo tỷ lệ thuế và lợi nhuận hiện tại</small>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label">Giá gốc *</label>
                <input id="cost_price" type="number" min="0" name="cost_price" required class="form-control" value="<?= (int)($row['cost_price'] ?? $row['base_price'] ?? 0) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Thuế nhập khẩu (%)</label>
                <input id="import_tax" type="number" min="0" max="100" step="0.01" name="import_tax_percent" class="form-control" value="<?= (float)($row['import_tax_percent'] ?? 0) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">VAT (%)</label>
                <input id="vat" type="number" min="0" max="100" step="0.01" name="vat_percent" class="form-control" value="<?= (float)($row['vat_percent'] ?? 0) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Lợi nhuận (%)</label>
                <input id="profit" type="number" min="0" max="100" step="0.01" name="profit_percent" class="form-control" value="<?= (float)($row['profit_percent'] ?? 0) ?>">
            </div>

            <!-- Hiển thị giá bán tính toán -->
            <div class="col-12 col-md-6">
                <div class="alert alert-info">
                    <p class="mb-2"><small class="text-muted">Công thức:</small></p>
                    <p class="mb-2"><small>Giá bán = Giá gốc × (1 + Thuế nhập + VAT + Lợi nhuận)</small></p>
                    <p class="mb-0">
                        <strong>Giá bán cuối cùng:</strong><br>
                        <span id="calculated_price" style="font-size: 1.5rem; color: #28a745; font-weight: bold;">0₫</span>
                    </p>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label">Bảo hành (tháng)</label>
                <input type="number" min="0" name="warranty_months" class="form-control" value="<?= (int)($row['warranty_months'] ?? 0) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Trạng thái</label>
                <select name="is_active" class="form-select">
                    <option value="1" <?= !empty($row['is_active']) ? 'selected' : '' ?>>Đang bán</option>
                    <option value="0" <?= empty($row['is_active']) ? 'selected' : '' ?>>Tạm ẩn</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Mô tả ngắn</label>
                <textarea name="short_description" rows="2" class="form-control"><?= View::e((string)($row['short_description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Mô tả chi tiết *</label>
                <textarea name="description" rows="5" class="form-control" required><?= View::e((string)($row['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Điểm nổi bật</label>
                <textarea name="highlights" rows="4" class="form-control"><?= View::e((string)($row['highlights'] ?? '')) ?></textarea>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Thông số kỹ thuật</label>
                <textarea name="technical_specs" rows="6" class="form-control" placeholder="Danh mục # Tai nghe&#10;Tồn kho # 10&#10;Kết nối # Bluetooth 5.3&#10;Dung lượng pin # 40 giờ"><?= View::e((string)($row['technical_specs'] ?? '')) ?></textarea>
                <small class="text-muted">Mỗi thông số trên 1 dòng, dùng dấu # để tách tên thông số và nội dung.</small>
            </div>
            <div class="col-12">
                <label class="form-label">Thông tin vận chuyển</label>
                <textarea name="shipping_info" rows="3" class="form-control"><?= View::e((string)($row['shipping_info'] ?? '')) ?></textarea>
            </div>

            <div class="col-12 d-flex gap-2">
                <a href="/admin/products" class="btn btn-outline-secondary">Quay lại</a>
                <button type="submit" class="btn btn-primary">Lưu cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Real-time price calculation
    const costPriceInput = document.getElementById('cost_price');
    const importTaxInput = document.getElementById('import_tax');
    const vatInput = document.getElementById('vat');
    const profitInput = document.getElementById('profit');
    const calculatedPriceDisplay = document.getElementById('calculated_price');

    function calculatePrice() {
        const costPrice = parseFloat(costPriceInput.value) || 0;
        const importTax = (parseFloat(importTaxInput.value) || 0) / 100;
        const vat = (parseFloat(vatInput.value) || 0) / 100;
        const profit = (parseFloat(profitInput.value) || 0) / 100;

        if (costPrice <= 0) {
            calculatedPriceDisplay.textContent = '0₫';
            calculatedPriceDisplay.style.color = '#999';
            return;
        }

        // Công thức: price = cost_price × (1 + import_tax + vat + profit)
        const finalPrice = Math.round(costPrice * (1 + importTax + vat + profit));
        const formatted = new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND',
            maximumFractionDigits: 0
        }).format(finalPrice);
        
        calculatedPriceDisplay.textContent = formatted;
        calculatedPriceDisplay.style.color = '#28a745';
    }

    // Gọi tính toán khi các input thay đổi
    if (costPriceInput) costPriceInput.addEventListener('input', calculatePrice);
    if (importTaxInput) importTaxInput.addEventListener('input', calculatePrice);
    if (vatInput) vatInput.addEventListener('input', calculatePrice);
    if (profitInput) profitInput.addEventListener('input', calculatePrice);

    // Tính giá lần đầu tiên
    calculatePrice();
});
</script>