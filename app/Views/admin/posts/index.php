<?php
$rows = $rows ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1, 'total' => 0];
$filters = $filters ?? ['q' => '', 'status' => ''];
$status = trim((string)($status ?? ''));
$tableReady = (bool)($tableReady ?? true);

$statusLabels = [
    'draft' => 'Nháp',
    'published' => 'Đã đăng',
    'hidden' => 'Đã ẩn',
];

$queryBase = [
    'q' => (string)($filters['q'] ?? ''),
    'status' => (string)($filters['status'] ?? ''),
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Quản lý bài viết</h1>
    <a href="/admin/posts/create" class="btn btn-primary">+ Thêm bài viết</a>
</div>

<?php if (!$tableReady): ?>
    <div class="alert alert-warning">Chưa tìm thấy bảng <code>posts</code>. Hãy chạy migration trong <code>database/migrations/20260313_create_posts_table.sql</code>.</div>
<?php endif; ?>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã thêm bài viết thành công.</div>
<?php elseif ($status === 'updated'): ?>
    <div class="alert alert-success">Đã cập nhật bài viết thành công.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa bài viết.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy bài viết.</div>
<?php elseif ($status === 'toggled'): ?>
    <div class="alert alert-success">Đã đổi trạng thái ẩn/hiện bài viết.</div>
<?php endif; ?>

<form method="GET" action="/admin/posts" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Tìm theo tiêu đề, slug hoặc tóm tắt..."
                value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>"
            >
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <option value="draft" <?= (($filters['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Nháp</option>
                <option value="published" <?= (($filters['status'] ?? '') === 'published') ? 'selected' : '' ?>>Đã đăng</option>
                <option value="hidden" <?= (($filters['status'] ?? '') === 'hidden') ? 'selected' : '' ?>>Đã ẩn</option>
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
                        <th style="width: 96px;">Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Slug</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng</th>
                        <th>Cập nhật</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Chưa có bài viết nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $postStatus = (string)($row['status'] ?? 'draft');
                        $badgeClass = match ($postStatus) {
                            'published' => 'text-bg-success',
                            'hidden' => 'text-bg-secondary',
                            default => 'text-bg-warning',
                        };
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['cover_image'])): ?>
                                    <img src="<?= htmlspecialchars((string)$row['cover_image']) ?>" alt="cover" style="width: 78px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef;">
                                <?php else: ?>
                                    <div class="small text-muted">Chưa có ảnh</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string)($row['title'] ?? '')) ?></div>
                                <?php if (!empty($row['excerpt'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 420px;">
                                        <?= htmlspecialchars((string)$row['excerpt']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars((string)($row['slug'] ?? '')) ?></code></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($statusLabels[$postStatus] ?? $postStatus) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($row['published_at']) ? htmlspecialchars((string)$row['published_at']) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td><?= htmlspecialchars((string)($row['updated_at'] ?? $row['created_at'] ?? '')) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/admin/posts/<?= (int)$row['id'] ?>/edit" class="btn btn-outline-primary">Sửa</a>
                                    <form method="POST" action="/admin/posts/<?= (int)$row['id'] ?>/toggle" style="display:inline;">
                                        <button class="btn btn-outline-warning" type="submit">
                                            <?= $postStatus === 'hidden' ? 'Hiện' : 'Ẩn' ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/posts/<?= (int)$row['id'] ?>/delete" onsubmit="return confirm('Xóa bài viết này?');" style="display:inline;">
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
                <a class="page-link" href="/admin/posts?<?= $query ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
