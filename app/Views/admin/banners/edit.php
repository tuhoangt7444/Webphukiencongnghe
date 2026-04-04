<?php
$row = $row ?? [];
$error = $error ?? '';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Sửa banner</h1>
    <a href="/admin/banners" class="btn btn-outline-secondary">Quay lại</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/banners/<?= (int)$row['id'] ?>/update" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tiêu đề</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars((string)$row['title']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Link điều hướng (tuỳ chọn)</label>
                <input type="text" name="link" class="form-control" value="<?= htmlspecialchars((string)($row['link'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Vị trí</label>
                <select name="position" class="form-select" required>
                    <option value="home_slider" <?= ((string)$row['position'] === 'home_slider') ? 'selected' : '' ?>>Slider trang chủ (1920x600)</option>
                    <option value="category_banner" <?= ((string)$row['position'] === 'category_banner') ? 'selected' : '' ?>>Banner danh mục (1200x300)</option>
                    <option value="promo_banner" <?= ((string)$row['position'] === 'promo_banner') ? 'selected' : '' ?>>Banner quảng cáo trang chủ (1200x300)</option>
                    <option value="sidebar_banner" <?= ((string)$row['position'] === 'sidebar_banner') ? 'selected' : '' ?>>Banner sidebar (400x600)</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select" required>
                    <option value="active" <?= ((string)$row['status'] === 'active') ? 'selected' : '' ?>>Hiển thị</option>
                    <option value="hidden" <?= ((string)$row['status'] === 'hidden') ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Đổi ảnh (tuỳ chọn)</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="col-12">
                <div class="small text-muted mb-2">Ảnh hiện tại:</div>
                <img src="<?= htmlspecialchars((string)$row['image']) ?>" alt="banner" style="max-width: 340px; height: auto; border-radius: 8px; border: 1px solid #e9ecef;">
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Cập nhật</button>
                <a href="/admin/banners" class="btn btn-outline-secondary">Huỷ</a>
            </div>
        </form>
    </div>
</div>
