<?php
$rows = $rows ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1, 'total' => 0];
$filters = $filters ?? ['q' => '', 'status' => 'active'];
$status = trim((string)($status ?? ''));

$queryBase = [
    'q' => (string)($filters['q'] ?? ''),
    'status' => (string)($filters['status'] ?? 'active'),
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Quản lý nhận ưu đãi</h1>
    <div class="text-muted small">Tổng: <?= (int)($pagination['total'] ?? 0) ?> email</div>
</div>

<?php if ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa email đăng ký.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy email đăng ký.</div>
<?php endif; ?>

<form method="GET" action="/admin/newsletters" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Tìm theo email hoặc nguồn trang..."
                value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>"
            >
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="active" <?= (($filters['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Đang nhận</option>
                <option value="inactive" <?= (($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Ngừng nhận</option>
                <option value="all" <?= (($filters['status'] ?? '') === 'all') ? 'selected' : '' ?>>Tất cả</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Lọc</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 66px;">ID</th>
                        <th>Email</th>
                        <th>Nguồn đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Thời gian đăng ký</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Chưa có email đăng ký nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $isActive = (string)($row['status'] ?? 'active') === 'active'; ?>
                        <tr>
                            <td>#<?= (int)($row['id'] ?? 0) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars((string)($row['email'] ?? '')) ?>">
                                    <?= htmlspecialchars((string)($row['email'] ?? '')) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars((string)($row['source_page'] ?? '/')) ?></td>
                            <td>
                                <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $isActive ? 'Đang nhận' : 'Ngừng nhận' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string)($row['subscribed_at'] ?? '')) ?></td>
                            <td class="text-end">
                                <form method="POST" action="/admin/newsletters/<?= (int)$row['id'] ?>/delete" onsubmit="return confirm('Xóa email này khỏi danh sách?');" style="display:inline;">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (($pagination['total_pages'] ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination mb-0">
        <?php
        $currentPage = (int)$pagination['page'];
        $totalPages = (int)$pagination['total_pages'];
        for ($p = 1; $p <= $totalPages; $p++):
            $query = http_build_query(array_merge($queryBase, ['page' => $p]));
        ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="/admin/newsletters?<?= $query ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
