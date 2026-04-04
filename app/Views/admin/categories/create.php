<?php
use App\Core\View;

$old = $old ?? [];
$error = (string)($error ?? '');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Thêm danh mục</h4>
        <small class="text-muted">Tạo danh mục mới cho hệ thống sản phẩm</small>
    </div>
    <a href="/admin/categories" class="btn btn-outline-secondary">Quay lại danh sách</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/admin/categories" class="row g-3" id="categoryForm">
            <div class="col-12 col-lg-6">
                <label class="form-label">Tên danh mục *</label>
                <input type="text" name="name" id="nameInput" class="form-control" required value="<?= View::e((string)($old['name'] ?? '')) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" id="slugInput" class="form-control" placeholder="tu-dong-neu-bo-trong" value="<?= View::e((string)($old['slug'] ?? '')) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Biểu tượng FontAwesome</label>
                <div class="input-group">
                    <span class="input-group-text"><i id="iconPreview" class="fa-solid <?= View::e((string)($old['icon'] ?? 'fa-folder-tree')) ?>"></i></span>
                    <input type="text" name="icon" id="iconInput" class="form-control" placeholder="fa-folder-tree" value="<?= View::e((string)($old['icon'] ?? 'fa-folder-tree')) ?>">
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="active" <?= (string)($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hiển thị</option>
                    <option value="hidden" <?= (string)($old['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Mô tả ngắn về nhóm sản phẩm này..."><?= View::e((string)($old['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Lưu danh mục</button>
                <a href="/admin/categories" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const nameInput = document.getElementById('nameInput');
    const slugInput = document.getElementById('slugInput');
    const iconInput = document.getElementById('iconInput');
    const iconPreview = document.getElementById('iconPreview');
    let slugTouched = slugInput.value.trim() !== '';

    const slugify = (value) => value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '') || 'danh-muc';

    nameInput?.addEventListener('input', () => {
        if (!slugTouched) {
            slugInput.value = slugify(nameInput.value);
        }
    });

    slugInput?.addEventListener('input', () => {
        slugTouched = slugInput.value.trim() !== '';
    });

    iconInput?.addEventListener('input', () => {
        iconPreview.className = 'fa-solid ' + (iconInput.value.trim() || 'fa-folder-tree');
    });
})();
</script>