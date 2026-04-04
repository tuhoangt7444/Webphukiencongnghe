<?php
$old = $old ?? [];
$error = (string)($error ?? '');
$tableReady = (bool)($tableReady ?? true);
$relatedReady = (bool)($relatedReady ?? true);
$products = is_array($products ?? null) ? $products : [];
$selectedRelatedProductIds = array_map('intval', (array)($selectedRelatedProductIds ?? []));
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Thêm bài viết</h1>
    <a href="/admin/posts" class="btn btn-outline-secondary">Quay lại</a>
</div>

<?php if (!$tableReady): ?>
    <div class="alert alert-warning">Chưa tìm thấy bảng <code>posts</code>. Hãy chạy migration trước khi thêm bài viết.</div>
<?php endif; ?>

<?php if ($tableReady && !$relatedReady): ?>
    <div class="alert alert-warning">Chức năng sản phẩm liên quan chưa sẵn sàng do thiếu quyền tạo bảng liên kết trong CSDL.</div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/posts" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Tiêu đề</label>
                <input
                    type="text"
                    name="title"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars((string)($old['title'] ?? '')) ?>"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Slug (tùy chọn)</label>
                <input
                    type="text"
                    name="slug"
                    class="form-control"
                    placeholder="de-trong-se-tu-tao"
                    value="<?= htmlspecialchars((string)($old['slug'] ?? '')) ?>"
                >
            </div>

            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <?php $oldStatus = (string)($old['status'] ?? 'draft'); ?>
                <select name="status" class="form-select" required>
                    <option value="draft" <?= $oldStatus === 'draft' ? 'selected' : '' ?>>Nháp</option>
                    <option value="published" <?= $oldStatus === 'published' ? 'selected' : '' ?>>Đã đăng</option>
                    <option value="hidden" <?= $oldStatus === 'hidden' ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Ngày đăng (tuỳ chọn)</label>
                <input
                    type="datetime-local"
                    name="published_at"
                    class="form-control"
                    value="<?= htmlspecialchars((string)($old['published_at'] ?? '')) ?>"
                >
            </div>

            <div class="col-md-6">
                <label class="form-label">Ảnh bìa (tệp ảnh)</label>
                <input
                    type="file"
                    name="cover_image_file"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp"
                    required
                >
            </div>

            <div class="col-12">
                <label class="form-label">Tóm tắt</label>
                <textarea name="excerpt" class="form-control" rows="3" placeholder="Đoạn mô tả ngắn của bài viết..."><?= htmlspecialchars((string)($old['excerpt'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Nội dung</label>
                <textarea name="content" class="form-control" rows="12" required placeholder="Nhập nội dung bài viết..."><?= htmlspecialchars((string)($old['content'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Sản phẩm liên quan</label>
                <select name="related_product_ids[]" class="form-select" multiple size="16">
                    <?php foreach ($products as $product): ?>
                        <?php $productId = (int)($product['id'] ?? 0); ?>
                        <option value="<?= $productId ?>" <?= in_array($productId, $selectedRelatedProductIds, true) ? 'selected' : '' ?>>
                            #<?= $productId ?> - <?= htmlspecialchars((string)($product['name'] ?? 'Sản phẩm')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Tổng <?= count($products) ?> sản phẩm. Giữ Ctrl (hoặc Cmd) để chọn nhiều sản phẩm, có thể cuộn để xem hết danh sách.</div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit" <?= $tableReady ? '' : 'disabled' ?>>Lưu bài viết</button>
                <a href="/admin/posts" class="btn btn-outline-secondary">Huỷ</a>
            </div>
        </form>
    </div>
</div>
