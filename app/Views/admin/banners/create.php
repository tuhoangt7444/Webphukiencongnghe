<?php
$old = $old ?? [];
$error = $error ?? '';
$dimensions = $dimensions ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Thêm banner</h1>
    <a href="/admin/banners" class="btn btn-outline-secondary">Quay lại</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/banners" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tiêu đề</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars((string)($old['title'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Link điều hướng (tuỳ chọn)</label>
                <input type="text" name="link" class="form-control" placeholder="https://... hoặc /product" value="<?= htmlspecialchars((string)($old['link'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Vị trí</label>
                <select name="position" class="form-select" required>
                    <option value="home_slider" <?= (($old['position'] ?? '') === 'home_slider') ? 'selected' : '' ?>>Slider trang chủ (1920x600)</option>
                    <option value="category_banner" <?= (($old['position'] ?? '') === 'category_banner') ? 'selected' : '' ?>>Banner danh mục (1200x300)</option>
                    <option value="promo_banner" <?= (($old['position'] ?? '') === 'promo_banner') ? 'selected' : '' ?>>Banner quảng cáo trang chủ (1200x300)</option>
                    <option value="sidebar_banner" <?= (($old['position'] ?? '') === 'sidebar_banner') ? 'selected' : '' ?>>Banner sidebar (400x600)</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select" required>
                    <option value="active" <?= (($old['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Hiển thị</option>
                    <option value="hidden" <?= (($old['status'] ?? '') === 'hidden') ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Ảnh banner</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
            </div>

            <div class="col-12">
                <div class="alert alert-info mb-0">
                    Ảnh sẽ được tự động resize và nén sang WebP theo vị trí banner để tối ưu tốc độ tải trang.
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Lưu banner</button>
                <a href="/admin/banners" class="btn btn-outline-secondary">Huỷ</a>
            </div>
        </form>
    </div>
</div>
