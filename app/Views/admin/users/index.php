<?php
use App\Core\View;
$rows = $rows ?? [];
$status = (string)($status ?? '');
$currentUserId = (int)($currentUserId ?? 0);
$stats = $stats ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 12, 'total_pages' => 1];
$filters = $filters ?? [];

$buildPageUrl = static function(int $page) use ($filters): string {
    $query = [
        'q' => (string)($filters['q'] ?? ''),
        'customer_type' => (string)($filters['customer_type'] ?? ''),
        'page' => (string)$page,
    ];
    return '/admin/users?' . http_build_query(array_filter($query, static fn($v) => $v !== ''));
};

$typeLabel = static function(string $type): string {
    return match ($type) {
        'vip' => 'Khách VIP',
        'regular' => 'Khách thường',
        default => 'Khách mới',
    };
};

$typeBadge = static function(string $type): string {
    return match ($type) {
        'vip' => 'text-bg-warning',
        'regular' => 'text-bg-info',
        default => 'text-bg-secondary',
    };
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Quản lý khách hàng</h4>
        <small class="text-muted">Quản lý tài khoản khách, trạng thái và hành vi mua sắm</small>
    </div>
    <a href="/admin/users" class="btn btn-outline-secondary">Làm mới</a>
</div>

<?php if ($status === 'forbidden'): ?>
    <div class="alert alert-warning">Không thể thao tác trên tài khoản hiện tại của bạn.</div>
<?php elseif ($status === 'toggled'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái tài khoản.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật thông tin khách hàng.</div>
<?php elseif ($status === 'blocked'): ?>
    <div class="alert alert-info">Khách đã có đơn hàng, tài khoản được chuyển sang trạng thái bị khóa thay vì xóa.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa tài khoản khách hàng chưa phát sinh đơn.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy khách hàng.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Tổng khách hàng</div><div class="fs-4 fw-bold text-primary\"><?= number_format((int)($stats['total_customers'] ?? 0)) ?></div></div></div></div>
    <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Khách mới hôm nay</div><div class="fs-4 fw-bold text-info\"><?= number_format((int)($stats['new_today'] ?? 0)) ?></div></div></div></div>
    <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Khách đã từng mua hàng</div><div class="fs-4 fw-bold text-success\"><?= number_format((int)($stats['purchased_customers'] ?? 0)) ?></div></div></div></div>
    <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Khách bị khóa</div><div class="fs-4 fw-bold text-danger\"><?= number_format((int)($stats['blocked_customers'] ?? 0)) ?></div></div></div></div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <h5 class="mb-0">Bản phân loại khách hàng theo chi tiêu</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Phân loại</th>
                        <th class="text-end">Số lượng khách</th>
                        <th class="text-end">Tỷ lệ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="badge text-bg-secondary">Khách mới</span>
                            <small class="d-block text-muted">Chưa có lịch sử mua hàng</small>
                        </td>
                        <td class="text-end"><strong><?= number_format((int)($segmentStats['new_segment'] ?? 0)) ?></strong></td>
                        <td class="text-end">
                            <?php
                                $total = (int)($stats['total_customers'] ?? 1);
                                $newCount = (int)($segmentStats['new_segment'] ?? 0);
                                $pct = $total > 0 ? round($newCount / $total * 100, 1) : 0;
                            ?>
                            <span class="text-muted"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="badge text-bg-info">Chi tiêu thấp</span>
                            <small class="d-block text-muted">&le; 10 triệu đ</small>
                        </td>
                        <td class="text-end"><strong><?= number_format((int)($segmentStats['low_spend'] ?? 0)) ?></strong></td>
                        <td class="text-end">
                            <?php
                                $lowCount = (int)($segmentStats['low_spend'] ?? 0);
                                $pct = $total > 0 ? round($lowCount / $total * 100, 1) : 0;
                            ?>
                            <span class="text-muted"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="badge text-bg-warning">Chi tiêu vừa</span>
                            <small class="d-block text-muted">(10 - 20 triệu đ)</small>
                        </td>
                        <td class="text-end"><strong><?= number_format((int)($segmentStats['mid_spend'] ?? 0)) ?></strong></td>
                        <td class="text-end">
                            <?php
                                $midCount = (int)($segmentStats['mid_spend'] ?? 0);
                                $pct = $total > 0 ? round($midCount / $total * 100, 1) : 0;
                            ?>
                            <span class="text-muted"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="badge text-bg-primary">Khách VIP</span>
                            <small class="d-block text-muted">&gt; 20 triệu đ</small>
                        </td>
                        <td class="text-end"><strong><?= number_format((int)($segmentStats['vip_segment'] ?? 0)) ?></strong></td>
                        <td class="text-end">
                            <?php
                                $vipCount = (int)($segmentStats['vip_segment'] ?? 0);
                                $pct = $total > 0 ? round($vipCount / $total * 100, 1) : 0;
                            ?>
                            <span class="text-muted"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td>Tổng doanh thu</td>
                        <td class="text-end text-success"><?= number_format((int)($segmentStats['total_revenue'] ?? 0)) ?>đ</td>
                        <td class="text-end">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/users" class="row g-2 align-items-end">
            <div class="col-12 col-lg-6">
                <label class="form-label">Tìm kiếm khách hàng</label>
                <input type="text" name="q" class="form-control" placeholder="Tìm tên / email / số điện thoại" value="<?= View::e((string)($filters['q'] ?? '')) ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Loại khách</label>
                <select name="customer_type" class="form-select">
                    <?php $selectedType = (string)($filters['customer_type'] ?? ''); ?>
                    <option value="" <?= $selectedType === '' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="privileged" <?= $selectedType === 'privileged' ? 'selected' : '' ?>>Tài khoản có quyền</option>
                    <option value="vip" <?= $selectedType === 'vip' ? 'selected' : '' ?>>Khách VIP</option>
                    <option value="low" <?= $selectedType === 'low' ? 'selected' : '' ?>>Chi tiêu thấp (≤ 10 triệu)</option>
                    <option value="mid" <?= $selectedType === 'mid' ? 'selected' : '' ?>>Chi tiêu vừa (10 - 20 triệu)</option>
                    <option value="new" <?= $selectedType === 'new' ? 'selected' : '' ?>>Khách mới</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                <a href="/admin/users" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <strong>Danh sách khách hàng</strong>
        <small class="text-muted d-block">Tìm thấy <?= number_format((int)($pagination['total'] ?? 0)) ?> khách hàng</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Tên khách hàng</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Quyền</th>
                <th>Địa chỉ</th>
                <th>Ngày đăng ký</th>
                <th>Phân loại</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">Chưa có dữ liệu người dùng.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $user): ?>
                    <?php $isSelf = (int)$user['id'] === $currentUserId; ?>
                    <?php $active = (string)($user['status'] ?? 'active') === 'active'; ?>
                    <?php $roleCode = (string)($user['role_code'] ?? ''); ?>
                    <?php $roleName = (string)($user['role_name'] ?? ''); ?>
                    <tr>
                        <td>#<?= (int)$user['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= View::e((string)$user['full_name']) ?></div>
                            <small class="text-muted">Đã chi: <?= number_format((int)($user['total_spent'] ?? 0)) ?>đ</small>
                        </td>
                        <td><?= View::e((string)$user['email']) ?></td>
                        <td><?= View::e((string)$user['phone']) ?></td>
                        <td>
                            <?php if ($roleCode === 'admin'): ?>
                                <span class="badge text-bg-danger">Admin</span>
                            <?php elseif ($roleCode === 'customer'): ?>
                                <span class="badge text-bg-secondary">Khách hàng</span>
                            <?php else: ?>
                                <span class="badge text-bg-info"><?= View::e($roleName !== '' ? $roleName : strtoupper($roleCode)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= View::e((string)$user['address']) ?></small></td>
                        <td><?= View::e((string)date('d/m/Y', strtotime((string)$user['created_at']))) ?></td>
                        <td><span class="badge <?= $typeBadge((string)($user['customer_type'] ?? 'new')) ?>"><?= View::e($typeLabel((string)($user['customer_type'] ?? 'new'))) ?></span></td>
                        <td><span class="badge <?= $active ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $active ? 'active' : 'blocked' ?></span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="/admin/users/<?= (int)$user['id'] ?>" class="btn btn-sm btn-outline-secondary">Xem</a>
                                <a href="/admin/users/<?= (int)$user['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Chỉnh sửa</a>
                                <?php if (!$isSelf): ?>
                                    <form method="POST" action="/admin/users/<?= (int)$user['id'] ?>/toggle" class="d-inline">
                                        <button class="btn btn-sm btn-outline-warning" type="submit"><?= $active ? 'Khóa' : 'Mở khóa' ?></button>
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

<?php if ((int)($pagination['total_pages'] ?? 1) > 1): ?>
    <nav class="mt-3" aria-label="Pagination">
        <ul class="pagination mb-0">
            <?php $current = (int)($pagination['page'] ?? 1); ?>
            <?php $last = (int)($pagination['total_pages'] ?? 1); ?>

            <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $current > 1 ? View::e($buildPageUrl($current - 1)) : '#' ?>">&lt;</a>
            </li>

            <?php for ($i = 1; $i <= $last; $i++): ?>
                <li class="page-item <?= $i === $current ? 'active' : '' ?>">
                    <a class="page-link" href="<?= View::e($buildPageUrl($i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= $current >= $last ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $current < $last ? View::e($buildPageUrl($current + 1)) : '#' ?>">&gt;</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
