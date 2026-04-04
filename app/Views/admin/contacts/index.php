<?php
$rows = $rows ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1, 'total' => 0];
$filters = $filters ?? ['q' => '', 'handled' => ''];
$status = trim((string)($status ?? ''));

$queryBase = [
    'q' => (string)($filters['q'] ?? ''),
    'handled' => (string)($filters['handled'] ?? ''),
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Quản lý liên hệ</h1>
    <div class="text-muted small">Tổng: <?= (int)($pagination['total'] ?? 0) ?> liên hệ</div>
</div>

<?php if ($status === 'handled'): ?>
    <div class="alert alert-success">Đã đánh dấu liên hệ là đã xử lý.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa liên hệ.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy liên hệ.</div>
<?php endif; ?>

<form method="GET" action="/admin/contacts" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Tìm theo tên, email, số điện thoại, tiêu đề..."
                value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>"
            >
        </div>
        <div class="col-md-3">
            <select name="handled" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <option value="0" <?= (($filters['handled'] ?? '') === '0') ? 'selected' : '' ?>>Chưa xử lý</option>
                <option value="1" <?= (($filters['handled'] ?? '') === '1') ? 'selected' : '' ?>>Đã xử lý</option>
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
                        <th>Thông tin</th>
                        <th>Tiêu đề</th>
                        <th>Nội dung</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Chưa có liên hệ nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $isHandled = (bool)($row['is_handled'] ?? false); ?>
                        <tr>
                            <td>#<?= (int)($row['id'] ?? 0) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string)($row['name'] ?? '')) ?></div>
                                <div class="small"><a href="mailto:<?= htmlspecialchars((string)($row['email'] ?? '')) ?>"><?= htmlspecialchars((string)($row['email'] ?? '')) ?></a></div>
                                <div class="small text-muted"><?= htmlspecialchars((string)($row['phone'] ?? '')) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string)($row['subject'] ?? '')) ?></td>
                            <td style="max-width: 380px;">
                                <div class="text-break"><?= nl2br(htmlspecialchars((string)($row['message'] ?? ''))) ?></div>
                            </td>
                            <td>
                                <?php if ($isHandled): ?>
                                    <span class="badge text-bg-success">Đã xử lý</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Chưa xử lý</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></div>
                                <?php if (!empty($row['handled_at'])): ?>
                                    <div class="small text-muted">Xử lý: <?= htmlspecialchars((string)$row['handled_at']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$isHandled): ?>
                                        <form method="POST" action="/admin/contacts/<?= (int)$row['id'] ?>/handled" style="display:inline;">
                                            <button class="btn btn-outline-success" type="submit">Đánh dấu đã xử lý</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="/admin/contacts/<?= (int)$row['id'] ?>/delete" onsubmit="return confirm('Xóa liên hệ này?');" style="display:inline;">
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
                <a class="page-link" href="/admin/contacts?<?= $query ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
