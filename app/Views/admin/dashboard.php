<?php
use App\Core\View;

$stats = $stats ?? [];
$timeStats = $time_stats ?? [];
$chart = $chart ?? ['labels' => [], 'revenue' => [], 'profit' => [], 'tax' => []];
$topProducts = $top_products ?? [];
$recentOrders = $recent_orders ?? [];
$lowStockProducts = $low_stock_products ?? [];

$formatMoney = static fn(int $amount): string => number_format($amount, 0, ',', '.') . 'đ';
$statCards = [
    ['label' => 'Tổng số sản phẩm', 'value' => number_format((int)($stats['total_products'] ?? 0)), 'class' => 'text-primary', 'icon' => 'fa-boxes-stacked'],
    ['label' => 'Tổng số người dùng', 'value' => number_format((int)($stats['total_users'] ?? 0)), 'class' => 'text-success', 'icon' => 'fa-users'],
    ['label' => 'Tổng số đơn hàng', 'value' => number_format((int)($stats['total_orders'] ?? 0)), 'class' => 'text-warning', 'icon' => 'fa-cart-shopping'],
    ['label' => 'Tổng doanh thu', 'value' => $formatMoney((int)($stats['total_revenue'] ?? 0)), 'class' => 'text-danger', 'icon' => 'fa-sack-dollar'],
    ['label' => 'Tổng chi phí nhập hàng', 'value' => $formatMoney((int)($stats['total_cost'] ?? 0)), 'class' => 'text-secondary', 'icon' => 'fa-warehouse'],
    ['label' => 'Tổng thuế đã thu', 'value' => $formatMoney((int)($stats['total_tax'] ?? 0)), 'class' => 'text-info', 'icon' => 'fa-file-invoice-dollar'],
    ['label' => 'Tổng lợi nhuận', 'value' => $formatMoney((int)($stats['total_profit'] ?? 0)), 'class' => 'text-success', 'icon' => 'fa-chart-line'],
];

$timeCards = [
    ['label' => 'Doanh thu hôm nay', 'value' => $formatMoney((int)($timeStats['revenue_today'] ?? 0)), 'class' => 'text-primary'],
    ['label' => 'Doanh thu tháng này', 'value' => $formatMoney((int)($timeStats['revenue_month'] ?? 0)), 'class' => 'text-danger'],
    ['label' => 'Lợi nhuận tháng này', 'value' => $formatMoney((int)($timeStats['profit_month'] ?? 0)), 'class' => 'text-success'],
    ['label' => 'Tổng thuế tháng này', 'value' => $formatMoney((int)($timeStats['total_tax_month'] ?? 0)), 'class' => 'text-info'],
    ['label' => 'Số đơn hàng hôm nay', 'value' => number_format((int)($timeStats['orders_today'] ?? 0)), 'class' => 'text-warning'],
];

$chartJson = json_encode([
    'labels' => $chart['labels'] ?? [],
    'revenue' => $chart['revenue'] ?? [],
    'profit' => $chart['profit'] ?? [],
    'tax' => $chart['tax'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function admin_dashboard_status_label(string $status): string {
    return match ($status) {
        'pending_approval' => 'Chờ xử lý',
        'approved' => 'Đã duyệt',
        'shipping' => 'Đang giao',
        'done' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
        'rejected' => 'Từ chối',
        default => $status,
    };
}

function admin_dashboard_status_badge(string $status): string {
    return match ($status) {
        'done' => 'text-bg-success',
        'shipping' => 'text-bg-primary',
        'approved' => 'text-bg-info',
        'pending_approval' => 'text-bg-warning',
        'cancelled', 'rejected' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
}
?>

<style>
    .stat-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
        overflow: hidden;
    }
    .stat-card .icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(59, 130, 246, .1);
    }
    .panel-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
    }
    .chart-card canvas {
        max-height: 320px;
    }
    .stock-pill {
        min-width: 42px;
        text-align: center;
        border-radius: 999px;
    }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard tài chính</h1>
        <p class="text-muted mb-0">Theo dõi doanh thu, chi phí, thuế, lợi nhuận và hoạt động bán hàng.</p>
    </div>
    <div class="text-muted small">Dữ liệu tài chính tính trên đơn đã duyệt, đang giao hoặc hoàn tất.</div>
</div>

<div class="card panel-card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" action="/admin/reports/export" method="GET" target="_blank">
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Kiểu thời gian</label>
                <select id="rangeType" name="range_type" class="form-select">
                    <option value="day">Theo ngày</option>
                    <option value="month" selected>Theo tháng</option>
                    <option value="year">Theo năm</option>
                    <option value="custom">Khoảng thời gian</option>
                </select>
            </div>
            <div id="fieldDay" class="col-12 col-md-3 d-none">
                <label class="form-label fw-semibold">Ngày</label>
                <input type="date" name="day" class="form-control" value="<?= View::e(date('Y-m-d')) ?>">
            </div>
            <div id="fieldMonth" class="col-12 col-md-3">
                <label class="form-label fw-semibold">Tháng</label>
                <input type="month" name="month" class="form-control" value="<?= View::e(date('Y-m')) ?>">
            </div>
            <div id="fieldYear" class="col-12 col-md-3 d-none">
                <label class="form-label fw-semibold">Năm</label>
                <input type="number" min="2000" max="2100" name="year" class="form-control" value="<?= View::e(date('Y')) ?>">
            </div>
            <div id="fieldStart" class="col-12 col-md-3 d-none">
                <label class="form-label fw-semibold">Từ ngày</label>
                <input type="date" name="start_date" class="form-control" value="<?= View::e(date('Y-m-01')) ?>">
            </div>
            <div id="fieldEnd" class="col-12 col-md-3 d-none">
                <label class="form-label fw-semibold">Đến ngày</label>
                <input type="date" name="end_date" class="form-control" value="<?= View::e(date('Y-m-d')) ?>">
            </div>
            <div class="col-12 col-md-3 col-xl-2 ms-xl-auto">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fa-solid fa-file-excel me-1"></i> Xuất báo cáo Excel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($statCards as $card): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <div class="text-muted small text-uppercase"><?= View::e($card['label']) ?></div>
                            <div class="fs-4 fw-bold <?= View::e($card['class']) ?>"><?= View::e((string)$card['value']) ?></div>
                        </div>
                        <div class="icon-wrap <?= View::e($card['class']) ?>">
                            <i class="fa-solid <?= View::e($card['icon']) ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($timeCards as $card): ?>
        <div class="col-12 col-md-6 col-xl">
            <div class="card panel-card h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase mb-2"><?= View::e($card['label']) ?></div>
                    <div class="fs-5 fw-bold <?= View::e($card['class']) ?>"><?= View::e((string)$card['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-4">
        <div class="card panel-card chart-card h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-1">Doanh thu theo tháng</h2>
                <p class="text-muted small mb-0">Tổng doanh thu từng tháng trong năm hiện tại</p>
            </div>
            <div class="card-body pt-2"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card panel-card chart-card h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-1">Lợi nhuận theo tháng</h2>
                <p class="text-muted small mb-0">Revenue - cost - total tax</p>
            </div>
            <div class="card-body pt-2"><canvas id="profitChart"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card panel-card chart-card h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-1">Thuế theo tháng</h2>
                <p class="text-muted small mb-0">VAT + thuế nhập khẩu</p>
            </div>
            <div class="card-body pt-2"><canvas id="taxChart"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card panel-card h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <div>
                    <h2 class="h5 mb-1">Sản phẩm bán chạy</h2>
                    <p class="text-muted small mb-0">Top 5 sản phẩm có số lượng bán cao nhất</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-center">Đã bán</th>
                        <th class="text-end">Doanh thu</th>
                        <th class="text-end">Lợi nhuận</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có dữ liệu bán hàng.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td class="fw-semibold"><?= View::e((string)($product['product_name'] ?? '')) ?></td>
                                <td class="text-center"><?= number_format((int)($product['quantity_sold'] ?? 0)) ?></td>
                                <td class="text-end"><?= View::e($formatMoney((int)($product['revenue'] ?? 0))) ?></td>
                                <td class="text-end fw-bold text-success"><?= View::e($formatMoney((int)($product['profit'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card panel-card h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <div>
                    <h2 class="h5 mb-1">Cảnh báo tồn kho</h2>
                    <p class="text-muted small mb-0">Sản phẩm có tổng tồn kho nhỏ hơn 5</p>
                </div>
                <span class="badge text-bg-danger"><?= count($lowStockProducts) ?> cảnh báo</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-end">Giá bán</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($lowStockProducts)): ?>
                        <tr><td colspan="3" class="text-center py-4 text-success">Không có sản phẩm nào sắp hết hàng.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <?php
                                $dashboardSalePrice = (int)($product['sale_price'] ?? 0);
                                $dashboardBasePrice = (int)($product['base_price'] ?? $dashboardSalePrice);
                                $dashboardDiscountPercent = (int)($product['discount_percent'] ?? 0);
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= View::e((string)($product['name'] ?? '')) ?></td>
                                <td class="text-center">
                                    <span class="badge stock-pill <?= (int)($product['stock_total'] ?? 0) <= 0 ? 'text-bg-danger' : 'text-bg-warning' ?>">
                                        <?= number_format((int)($product['stock_total'] ?? 0)) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($dashboardDiscountPercent > 0 && $dashboardBasePrice > $dashboardSalePrice): ?>
                                        <div class="text-muted text-decoration-line-through small"><?= View::e($formatMoney($dashboardBasePrice)) ?></div>
                                        <div class="fw-bold text-danger"><?= View::e($formatMoney($dashboardSalePrice)) ?></div>
                                        <div class="small text-danger">-<?= $dashboardDiscountPercent ?>%</div>
                                    <?php else: ?>
                                        <?= View::e($formatMoney($dashboardSalePrice)) ?>
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
</div>

<div class="card panel-card">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-4 px-4">
        <div>
            <h2 class="h5 mb-1">Đơn hàng gần đây</h2>
            <p class="text-muted small mb-0">5 đơn hàng mới nhất trong hệ thống</p>
        </div>
        <a href="/admin/orders" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Khách hàng</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
                <th class="text-end">Tổng tiền</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($recentOrders)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có đơn hàng.</td></tr>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td class="fw-semibold">#<?= (int)($order['id'] ?? 0) ?></td>
                        <td><?= View::e((string)($order['customer_name'] ?? '')) ?></td>
                        <td>
                            <span class="badge <?= View::e(admin_dashboard_status_badge((string)($order['status'] ?? ''))) ?>">
                                <?= View::e(admin_dashboard_status_label((string)($order['status'] ?? ''))) ?>
                            </span>
                        </td>
                        <td><?= View::e((string)($order['created_at'] ?? '')) ?></td>
                        <td class="text-end fw-bold"><?= View::e($formatMoney((int)($order['total'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const rangeTypeEl = document.getElementById('rangeType');
    const fieldDay = document.getElementById('fieldDay');
    const fieldMonth = document.getElementById('fieldMonth');
    const fieldYear = document.getElementById('fieldYear');
    const fieldStart = document.getElementById('fieldStart');
    const fieldEnd = document.getElementById('fieldEnd');

    const toggleRangeFields = () => {
        if (!rangeTypeEl) return;
        const value = rangeTypeEl.value;

        fieldDay?.classList.add('d-none');
        fieldMonth?.classList.add('d-none');
        fieldYear?.classList.add('d-none');
        fieldStart?.classList.add('d-none');
        fieldEnd?.classList.add('d-none');

        if (value === 'day') {
            fieldDay?.classList.remove('d-none');
        } else if (value === 'month') {
            fieldMonth?.classList.remove('d-none');
        } else if (value === 'year') {
            fieldYear?.classList.remove('d-none');
        } else if (value === 'custom') {
            fieldStart?.classList.remove('d-none');
            fieldEnd?.classList.remove('d-none');
        }
    };

    if (rangeTypeEl) {
        rangeTypeEl.addEventListener('change', toggleRangeFields);
        toggleRangeFields();
    }

    const payload = <?= $chartJson ?: '{"labels":[],"revenue":[],"profit":[],"tax":[]}' ?>;
    const moneyFormatter = new Intl.NumberFormat('vi-VN');

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label(context) {
                        return moneyFormatter.format(context.parsed.y || 0) + 'đ';
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback(value) {
                        return moneyFormatter.format(value) + 'đ';
                    }
                }
            }
        }
    };

    const configs = [
        {
            id: 'revenueChart',
            type: 'bar',
            data: payload.revenue,
            color: 'rgba(220, 38, 38, 0.85)',
            border: 'rgba(220, 38, 38, 1)'
        },
        {
            id: 'profitChart',
            type: 'line',
            data: payload.profit,
            color: 'rgba(22, 163, 74, 0.2)',
            border: 'rgba(22, 163, 74, 1)'
        },
        {
            id: 'taxChart',
            type: 'bar',
            data: payload.tax,
            color: 'rgba(14, 165, 233, 0.85)',
            border: 'rgba(14, 165, 233, 1)'
        }
    ];

    configs.forEach((config) => {
        const el = document.getElementById(config.id);
        if (!el) return;

        new Chart(el, {
            type: config.type,
            data: {
                labels: payload.labels,
                datasets: [{
                    data: config.data,
                    backgroundColor: config.color,
                    borderColor: config.border,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: config.type === 'line'
                }]
            },
            options: commonOptions
        });
    });
})();
</script>