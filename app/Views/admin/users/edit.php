<?php
use App\Core\View;

$row = $row ?? null;
$status = (string)($status ?? '');
$roles = $roles ?? [];

if (!$row) {
    echo '<div class="alert alert-warning">Không tìm thấy khách hàng.</div>';
    return;
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Chỉnh sửa khách hàng #<?= (int)$row['id'] ?></h4>
        <small class="text-muted">Cập nhật thông tin tài khoản khách hàng</small>
    </div>
    <a href="/admin/users/<?= (int)$row['id'] ?>" class="btn btn-outline-secondary">Quay lại</a>
</div>

<?php if ($status === 'invalid'): ?>
    <div class="alert alert-warning">Dữ liệu không hợp lệ. Vui lòng kiểm tra tên và email.</div>
<?php elseif ($status === 'exists'): ?>
    <div class="alert alert-danger">Email đã tồn tại trong hệ thống.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/admin/users/<?= (int)$row['id'] ?>" class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label">Tên khách hàng</label>
                <input type="text" name="full_name" class="form-control" required value="<?= View::e((string)$row['full_name']) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= View::e((string)$row['email']) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control" value="<?= View::e((string)$row['phone']) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Địa chỉ</label>
                <input type="text" name="address" class="form-control" value="<?= View::e((string)$row['address']) ?>">
            </div>
            <div class="col-12">
                <div class="border rounded p-3 bg-light-subtle">
                    <div class="fw-semibold mb-2">Phân quyền quản trị</div>
                    <label class="form-label">Vai trò áp dụng cho tài khoản này</label>
                    <select name="role_id" class="form-select">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int)$role['id'] ?>" <?= (int)($row['role_id'] ?? 0) === (int)$role['id'] ? 'selected' : '' ?>>
                                <?= View::e((string)$role['name']) ?> (<?= View::e((string)$role['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-2">Bạn có thể tạo/chỉnh quyền từng vai trò trong mục Phân quyền.</small>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
                <a href="/admin/users/<?= (int)$row['id'] ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
