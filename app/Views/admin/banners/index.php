<?php
$rows = $rows ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1, 'total' => 0];
$filters = $filters ?? ['q' => '', 'position' => ''];
$status = $status ?? '';

$positionLabels = [
    'home_slider' => 'Slider trang chủ',
];

$queryBase = [
    'q' => $filters['q'] ?? '',
    'position' => $filters['position'] ?? '',
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Quản lý banner</h1>
    <a href="/admin/banners/create" class="btn btn-primary">+ Thêm banner</a>
</div>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã thêm banner thành công.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật banner thành công.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa banner.</div>
<?php elseif ($status === 'toggled'): ?>
    <div class="alert alert-success">Đã đổi trạng thái banner.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy banner.</div>
<?php endif; ?>

<form method="GET" action="/admin/banners" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Tìm theo tiêu đề hoặc link..." value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
            <select name="position" class="form-select">
                <option value="">Tất cả vị trí</option>
                <?php foreach ($positionLabels as $value => $label): ?>
                    <option value="<?= $value ?>" <?= (($filters['position'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-secondary w-100" type="submit">Lọc</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 100px;">Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Vị trí</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Chưa có banner nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars((string)$row['image']) ?>" alt="banner" style="width: 80px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef;">
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string)$row['title']) ?></div>
                                <?php if (!empty($row['link'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 380px;"><?= htmlspecialchars((string)$row['link']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($positionLabels[(string)$row['position']] ?? (string)$row['position']) ?></span>
                            </td>
                            <td>
                                <?php if ((string)$row['status'] === 'active'): ?>
                                    <span class="badge bg-success">Đang hiển thị</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Đang ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)$row['created_at']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/admin/banners/<?= (int)$row['id'] ?>/edit" class="btn btn-outline-primary">Sửa</a>
                                    <form action="/admin/banners/<?= (int)$row['id'] ?>/toggle" method="POST" style="display:inline;">
                                        <button class="btn btn-outline-warning" type="submit">Ẩn/Hiện</button>
                                    </form>
                                    <form action="/admin/banners/<?= (int)$row['id'] ?>/delete" method="POST" onsubmit="return confirm('Xóa banner này?');" style="display:inline;">
                                        <button class="btn btn-outline-danger" type="submit">Xóa</button>
                                    </form>
                                </div>
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
                <a class="page-link" href="/admin/banners?<?= $query ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
