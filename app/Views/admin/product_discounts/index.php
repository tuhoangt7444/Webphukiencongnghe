<?php
use App\Core\View;

$rows = $rows ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 12, 'total_pages' => 1];
$stats = $stats ?? [];
$suggestions = $suggestions ?? [];
$allProducts = $allProducts ?? [];
$status = (string)($status ?? '');

$nowValue = date('Y-m-d\TH:i');
$defaultEnd = date('Y-m-d\TH:i', strtotime('+7 days'));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">Giảm giá sản phẩm</h4>
        <small class="text-muted">Tạo chiến dịch giảm giá theo thời gian, tách riêng khỏi phiếu giảm giá.</small>
    </div>
</div>

<?php if ($status === 'created'): ?>
    <div class="alert alert-success">Đã tạo chiến dịch giảm giá sản phẩm.</div>
<?php elseif ($status === 'toggled'): ?>
    <div class="alert alert-success">Đã cập nhật trạng thái chiến dịch.</div>
<?php elseif ($status === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa chiến dịch giảm giá.</div>
<?php elseif ($status === 'invalid-percent'): ?>
    <div class="alert alert-warning">% giảm giá không hợp lệ (1-90).</div>
<?php elseif ($status === 'invalid-time' || $status === 'invalid-time-range'): ?>
    <div class="alert alert-warning">Thời gian áp dụng không hợp lệ. Vui lòng kiểm tra lại.</div>
<?php elseif ($status === 'over-profit'): ?>
    <div class="alert alert-warning">Số tiền giảm không được lớn hơn tiền lời của sản phẩm.</div>
<?php elseif ($status === 'not-found'): ?>
    <div class="alert alert-warning">Không tìm thấy dữ liệu cần thao tác.</div>
<?php elseif ($status === 'failed'): ?>
    <div class="alert alert-danger">Không thể xử lý chiến dịch giảm giá. Vui lòng thử lại.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tổng chiến dịch</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đang bật</div>
                <div class="fs-4 fw-bold text-success"><?= number_format((int)($stats['active_count'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đang chạy theo thời gian</div>
                <div class="fs-4 fw-bold text-warning"><?= number_format((int)($stats['running_count'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Đã tắt</div>
                <div class="fs-4 fw-bold text-secondary"><?= number_format((int)($stats['disabled_count'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <strong>Thêm chiến dịch giảm giá</strong>
        <div class="small text-muted mt-1">Chọn bất kỳ sản phẩm đang bán để tạo chiến dịch giảm giá theo thời gian.</div>
    </div>
    <div class="card-body">
        <?php
        // Build JSON map: product_id => {name, sale_price, max_discount_percent}
        $productMap = [];
        foreach ($allProducts as $ap) {
            $productMap[(int)$ap['id']] = [
                'name' => (string)$ap['name'],
                'sale_price' => (int)$ap['sale_price'],
                'max_discount_percent' => (int)$ap['max_discount_percent'],
            ];
        }
        ?>
        <script>
            var pdcProductMap = <?= json_encode($productMap, JSON_UNESCAPED_UNICODE) ?>;
            function pdcOnProductChange(sel) {
                var id = parseInt(sel.value, 10);
                var info = pdcProductMap[id] || null;
                var pctInput = document.getElementById('manualDiscountPercent');
                var infoDiv = document.getElementById('manualProductInfo');
                if (!info || info.max_discount_percent <= 0) {
                    pctInput.max = 90;
                    infoDiv.innerHTML = info ? '<span class="text-danger small">Sản phẩm này không còn biên lợi nhuận để giảm thêm.</span>' : '';
                    pctInput.disabled = !info;
                    return;
                }
                pctInput.disabled = false;
                pctInput.max = info.max_discount_percent;
                if (parseInt(pctInput.value, 10) > info.max_discount_percent) {
                    pctInput.value = info.max_discount_percent;
                }
                infoDiv.innerHTML = '<span class="small text-muted">Giá bán: <strong>' +
                    info.sale_price.toLocaleString('vi-VN') + 'đ</strong> &mdash; Giảm tối đa: <strong class="text-danger">' +
                    info.max_discount_percent + '%</strong></span>';
            }
        </script>
        <form method="POST" action="/admin/product-discounts">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Sản phẩm</label>
                    <select name="product_id" id="manualProductSelect" class="form-select" required onchange="pdcOnProductChange(this)">
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php foreach ($allProducts as $ap): ?>
                            <option value="<?= (int)$ap['id'] ?>">
                                <?= View::e((string)$ap['name']) ?> (<?= number_format((int)$ap['sale_price']) ?>đ)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="manualProductInfo" class="mt-1"></div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">% giảm</label>
                    <input type="number" id="manualDiscountPercent" name="discount_percent" min="1" max="90" value="10" class="form-control" required disabled>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">Bắt đầu</label>
                    <input type="datetime-local" name="start_at" class="form-control" value="<?= View::e($nowValue) ?>" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">Kết thúc</label>
                    <input type="datetime-local" name="end_at" class="form-control" value="<?= View::e($defaultEnd) ?>" required>
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Tạo chiến dịch</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <strong>Gợi ý sản phẩm nên giảm giá</strong>
        <div class="small text-muted mt-1">Ưu tiên sản phẩm lâu chưa có đơn. Bạn chọn % giảm và thời gian áp dụng ngay trên từng dòng.</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Sản phẩm</th>
                    <th class="text-end">Giá vốn</th>
                    <th class="text-end">Giá bán</th>
                    <th class="text-end">Mức giảm tối đa</th>
                    <th>Lần bán gần nhất</th>
                    <th class="text-end">Ngày chưa bán</th>
                    <th style="min-width: 360px;">Tạo chiến dịch</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($suggestions)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có sản phẩm phù hợp để gợi ý.</td></tr>
                <?php else: ?>
                    <?php foreach ($suggestions as $item): ?>
                        <?php
                            $maxPercent = (int)($item['max_discount_percent'] ?? 0);
                            $recommendedPercent = (int)($item['recommended_percent'] ?? 1);
                            $lastSoldAt = trim((string)($item['last_sold_at'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= View::e((string)($item['name'] ?? '')) ?></div>
                                <div class="small text-muted">ID: #<?= (int)($item['id'] ?? 0) ?></div>
                            </td>
                            <td class="text-end"><?= number_format((int)($item['base_price'] ?? 0)) ?>đ</td>
                            <td class="text-end text-primary fw-semibold"><?= number_format((int)($item['sale_price'] ?? 0)) ?>đ</td>
                            <td class="text-end text-danger">-<?= number_format((int)($item['max_discount_amount'] ?? 0)) ?>đ (<?= $maxPercent ?>%)</td>
                            <td>
                                <?php if ($lastSoldAt !== ''): ?>
                                    <?= View::e(date('d/m/Y H:i', strtotime($lastSoldAt))) ?>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Chưa từng bán</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format((int)($item['days_unsold'] ?? 0)) ?></td>
                            <td>
                                <?php if ($maxPercent > 0): ?>
                                    <form method="POST" action="/admin/product-discounts" class="row g-2 align-items-end">
                                        <input type="hidden" name="product_id" value="<?= (int)($item['id'] ?? 0) ?>">
                                        <div class="col-12 col-md-3">
                                            <label class="form-label small mb-1">% giảm</label>
                                            <input type="number" name="discount_percent" min="1" max="<?= $maxPercent ?>" value="<?= max(1, min($maxPercent, $recommendedPercent)) ?>" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small mb-1">Bắt đầu</label>
                                            <input type="datetime-local" name="start_at" class="form-control form-control-sm" value="<?= View::e($nowValue) ?>" required>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small mb-1">Kết thúc</label>
                                            <input type="datetime-local" name="end_at" class="form-control form-control-sm" value="<?= View::e($defaultEnd) ?>" required>
                                        </div>
                                        <div class="col-12 col-md-1 d-grid">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Lưu</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted">Sản phẩm này không còn biên lợi nhuận để giảm thêm.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <strong>Danh sách chiến dịch giảm giá</strong>
            <small class="text-muted d-block">Tổng <?= number_format((int)($pagination['total'] ?? 0)) ?> chiến dịch</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Sản phẩm</th>
                    <th class="text-end">% giảm</th>
                    <th>Thời gian áp dụng</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có chiến dịch giảm giá nào.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $isRunning = ((string)($row['status'] ?? '') === 'active')
                            && strtotime((string)($row['start_at'] ?? '')) <= time()
                            && strtotime((string)($row['end_at'] ?? '')) >= time();
                    ?>
                    <tr>
                        <td>#<?= (int)($row['id'] ?? 0) ?></td>
                        <td>
                            <div class="fw-semibold"><?= View::e((string)($row['product_name'] ?? '')) ?></div>
                            <small class="text-muted">Product ID: #<?= (int)($row['product_id'] ?? 0) ?></small>
                        </td>
                        <td class="text-end fw-semibold text-danger">-<?= (int)($row['discount_percent'] ?? 0) ?>%</td>
                        <td>
                            <div><strong>Từ:</strong> <?= View::e(date('d/m/Y H:i', strtotime((string)($row['start_at'] ?? '')))) ?></div>
                            <div><strong>Đến:</strong> <?= View::e(date('d/m/Y H:i', strtotime((string)($row['end_at'] ?? '')))) ?></div>
                        </td>
                        <td>
                            <?php if ((string)($row['status'] ?? '') === 'active'): ?>
                                <?php if ($isRunning): ?>
                                    <span class="badge text-bg-success">Đang chạy</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Đã bật, chờ thời gian</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Đã tắt</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e(date('d/m/Y H:i', strtotime((string)($row['created_at'] ?? '')))) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <form action="/admin/product-discounts/<?= (int)($row['id'] ?? 0) ?>/toggle" method="POST" style="display:inline;">
                                    <button type="submit" class="btn btn-outline-warning">Bật/Tắt</button>
                                </form>
                                <form action="/admin/product-discounts/<?= (int)($row['id'] ?? 0) ?>/delete" method="POST" onsubmit="return confirm('Xóa chiến dịch này?');" style="display:inline;">
                                    <button type="submit" class="btn btn-outline-danger">Xóa</button>
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

<?php if ((int)($pagination['total_pages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination mb-0">
            <?php
            $current = (int)($pagination['page'] ?? 1);
            $last = (int)($pagination['total_pages'] ?? 1);
            for ($p = 1; $p <= $last; $p++):
            ?>
                <li class="page-item <?= $p === $current ? 'active' : '' ?>">
                    <a class="page-link" href="/admin/product-discounts?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
