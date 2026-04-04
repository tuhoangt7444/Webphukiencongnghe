<?php
use App\Core\View;

$roles = $roles ?? [];
$status = (string)($status ?? '');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Phân quyền quản trị</h4>
        <small class="text-muted">Tạo vai trò mới và gán quyền truy cập từng phần quản lý</small>
    </div>
</div>

<?php if ($status === 'invalid'): ?>
    <div class="alert alert-warning">Tên vai trò không hợp lệ.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa vai trò.</div>
<?php elseif ($status === 'delete-failed'): ?>
    <div class="alert alert-danger">Không thể xóa vai trò này.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy vai trò.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="POST" action="/admin/roles" class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label">Tên vai trò mới</label>
                <input type="text" name="name" class="form-control" placeholder="Ví dụ: Nhân viên kho" required>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <button type="submit" class="btn btn-primary">Tạo vai trò</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Danh sách vai trò</strong></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Quyền</th>
                    <th>Người dùng</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roles)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có vai trò.</td></tr>
                <?php else: ?>
                    <?php foreach ($roles as $role): ?>
                        <?php $canDelete = (string)($role['code'] ?? '') !== 'admin'; ?>
                        <tr>
                            <td>#<?= (int)$role['id'] ?></td>
                            <td><code><?= View::e((string)$role['code']) ?></code></td>
                            <td class="fw-semibold"><?= View::e((string)$role['name']) ?></td>
                            <td><?= number_format((int)($role['permission_count'] ?? 0)) ?></td>
                            <td><?= number_format((int)($role['user_count'] ?? 0)) ?></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="/admin/roles/<?= (int)$role['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Phân quyền</a>
                                    <?php if ($canDelete): ?>
                                        <form method="POST" action="/admin/roles/<?= (int)$role['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Xóa vai trò này?');">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
