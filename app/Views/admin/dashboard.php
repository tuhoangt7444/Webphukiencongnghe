<!-- app/Views/admin/dashboard.php -->
<div class="mb-8">
    <h2 class="text-2xl font-bold tracking-tight">Overview Dashboard</h2>
    <p class="text-slate-500 dark:text-slate-400">Chào mừng trở lại! Dưới đây là tình hình kinh doanh của TechGear hôm nay.</p>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Doanh thu -->
    <div class="bg-white dark:bg-[#161b28] p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <span class="text-emerald-500 text-xs font-bold flex items-center bg-emerald-500/10 px-2 py-1 rounded-full">+12.5%</span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Tổng doanh thu</p>
        <h3 class="text-2xl font-bold mt-1"><?= number_format($stats['revenue']) ?>₫</h3>
    </div>

    <!-- Đơn hàng -->
    <div class="bg-white dark:bg-[#161b28] p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                <span class="material-symbols-outlined">shopping_bag</span>
            </div>
            <span class="text-blue-500 text-xs font-bold flex items-center bg-blue-500/10 px-2 py-1 rounded-full">Đơn mới</span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Tổng đơn hàng</p>
        <h3 class="text-2xl font-bold mt-1"><?= $stats['orders'] ?></h3>
    </div>

    <!-- Khách hàng -->
    <div class="bg-white dark:bg-[#161b28] p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                <span class="material-symbols-outlined">person_add</span>
            </div>
            <span class="text-emerald-500 text-xs font-bold flex items-center bg-emerald-500/10 px-2 py-1 rounded-full">Thành viên</span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Khách hàng</p>
        <h3 class="text-2xl font-bold mt-1"><?= $stats['customers'] ?></h3>
    </div>

    <!-- Cảnh báo kho -->
    <div class="bg-white dark:bg-[#161b28] p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm border-l-4 border-l-amber-500">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-amber-500/10 rounded-lg text-amber-500">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <span class="text-amber-500 text-xs font-bold">Cần nhập kho</span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Sản phẩm sắp hết</p>
        <h3 class="text-2xl font-bold mt-1 text-amber-500"><?= $stats['low_stock'] ?></h3>
    </div>
</div>

<!-- Bảng đơn hàng gần đây -->
<div class="mt-8 bg-white dark:bg-[#161b28] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
        <h3 class="text-lg font-bold">Đơn hàng gần đây</h3>
        <a href="/admin/orders" class="text-primary text-sm font-bold hover:underline">Xem tất cả đơn hàng</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Mã Đơn</th>
                    <th class="px-6 py-4">Khách hàng</th>
                    <th class="px-6 py-4">Ngày đặt</th>
                    <th class="px-6 py-4">Trạng thái</th>
                    <th class="px-6 py-4">Tổng tiền</th>
                    <th class="px-6 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($recent_orders)): ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center text-slate-500">Chưa có đơn hàng nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                        <td class="px-6 py-4 text-sm font-bold">#ORD-<?= $order['id'] ?></td>
                        <td class="px-6 py-4 text-sm"><?= \App\Core\View::e($order['email']) ?></td>
                        <td class="px-6 py-4 text-sm text-slate-500"><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                <?= $order['status'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-bold"><?= number_format($order['total']) ?>₫</td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-slate-400 hover:text-primary transition-colors">
                                <span class="material-symbols-outlined">more_horiz</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>