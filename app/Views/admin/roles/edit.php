<?php
use App\Core\View;

$role = $role ?? null;
$catalog = $catalog ?? [];
$selectedPermissions = $selectedPermissions ?? [];
$status = (string)($status ?? '');

if (!$role) {
    echo '<div class="alert alert-warning">Không tìm thấy vai trò.</div>';
    return;
}

$selectedMap = array_fill_keys(array_map('strval', $selectedPermissions), true);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Chỉnh sửa vai trò #<?= (int)$role['id'] ?></h4>
        <small class="text-muted">Chọn các phần quản lý được hiển thị và truy cập</small>
    </div>
    <a href="/admin/roles" class="btn btn-outline-secondary">Quay lại</a>
</div>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã tạo vai trò, hãy chọn quyền truy cập.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật phân quyền.</div>
<?php elseif ($status === 'invalid'): ?>
    <div class="alert alert-warning">Dữ liệu không hợp lệ.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/admin/roles/<?= (int)$role['id'] ?>" class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label">Tên vai trò</label>
                <input type="text" name="name" class="form-control" required value="<?= View::e((string)$role['name']) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Mã vai trò</label>
                <input type="text" class="form-control" disabled value="<?= View::e((string)$role['code']) ?>">
            </div>

            <div class="col-12">
                <div class="border rounded p-3">
                    <div class="fw-semibold mb-2">Các quyền truy cập</div>
                    <div class="row g-2">
                        <?php foreach ($catalog as $code => $label): ?>
                            <?php $checked = isset($selectedMap[(string)$code]); ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <label class="form-check border rounded p-2 h-100 d-flex align-items-start gap-2">
                                    <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="<?= View::e((string)$code) ?>" <?= $checked ? 'checked' : '' ?>>
                                    <span>
                                        <span class="d-block fw-semibold"><?= View::e((string)$label) ?></span>
                                        <small class="text-muted"><?= View::e((string)$code) ?></small>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Lưu phân quyền</button>
                <a href="/admin/roles" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
