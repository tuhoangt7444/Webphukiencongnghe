<?php use App\Core\View; $r = $row ?? []; ?>
<h1 class="h3 mb-3"><?= View::e($title ?? '') ?> #<?= (int)$r['id'] ?></h1>

<form method="post" action="/admin/products/<?= (int)$r['id'] ?>" class="row g-3">
  <div class="col-12">
    <label class="form-label">Tên sản phẩm</label>
    <input name="name" class="form-control" value="<?= View::e($r['name'] ?? '') ?>" required>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Slug</label>
    <input name="slug" class="form-control" value="<?= View::e($r['slug'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label">Mô tả</label>
    <textarea name="description" class="form-control" rows="4"><?= View::e($r['description'] ?? '') ?></textarea>
  </div>

  <div class="col-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= ((bool)($r['is_active'] ?? false)) ? 'checked' : '' ?>>
      <label class="form-check-label">Đang bán</label>
    </div>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">Cập nhật</button>
    <a class="btn btn-outline-secondary" href="/admin/products">Quay lại</a>
  </div>
</form>

<div class="alert alert-info mt-4">
  <b>Note:</b> MVP hiện mới sửa bảng <code>products</code>.
  Biến thể/giá/tồn kho đang là “variant mặc định” (chưa làm UI sửa variant).
</div>
