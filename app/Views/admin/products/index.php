<!-- Header của phần nội dung Dashboard -->
<div class="mb-8 flex justify-between items-end">
    <div>
        <h2 class="text-2xl font-bold tracking-tight">Danh sách sản phẩm</h2>
        <p class="text-slate-500 dark:text-slate-400">Quản lý kho hàng, thông số và giá bán linh kiện công nghệ.</p>
    </div>
    <div class="flex gap-3">
        <!-- Nút xuất báo cáo (Demo) -->
        <a href="/admin/products/create" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-sm">add</span> New Product
        </a>
    </div>
</div>

<!-- Bảng danh sách sản phẩm -->
<div class="bg-white dark:bg-[#161b28] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
        <h3 class="text-lg font-bold">Kho hàng hiện tại</h3>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500">Hiển thị: </span>
            <span class="text-sm font-bold"><?= count($rows) ?> sản phẩm</span>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Sản phẩm</th>
                    <th class="px-6 py-4">Mã SKU</th>
                    <th class="px-6 py-4">Giá bán (Hệ thống)</th>
                    <th class="px-6 py-4">Tồn kho</th>
                    <th class="px-6 py-4">Trạng thái</th>
                    <th class="px-6 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                            <span class="material-symbols-outlined text-4xl mb-2 block">inventory_2</span>
                            Chưa có sản phẩm nào được tạo. 
                            <a href="/admin/products/create" class="text-primary hover:underline">Thêm ngay?</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $product): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                        <!-- Cột sản phẩm -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <!-- Giả lập ảnh sản phẩm bằng Icon hoặc ký tự đầu -->
                                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold border border-primary/20">
                                    <span class="material-symbols-outlined">devices</span>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="text-sm font-bold truncate hover:text-primary cursor-pointer transition-colors">
                                        <?= \App\Core\View::e($product['name']) ?>
                                    </p>
                                    <p class="text-[10px] text-slate-500 font-medium uppercase tracking-tighter">
                                        ID: #<?= $product['id'] ?> | Slug: <?= \App\Core\View::e($product['slug'] ?? 'chua-co-slug') ?>
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- SKU -->
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded text-[11px] font-mono">
                                <?= $product['sku'] ?? 'N/A' ?>
                            </span>
                        </td>

                        <!-- Giá bán -->
                        <td class="px-6 py-4 text-sm font-bold text-emerald-500">
                            <?= number_format($product['sale_price'] ?? 0) ?>₫
                        </td>

                        <!-- Tồn kho -->
                        <td class="px-6 py-4 text-sm font-medium">
                            <?= (int)($product['stock'] ?? 0) ?>
                        </td>

                        <!-- Trạng thái -->
                        <td class="px-6 py-4">
                            <?php if ($product['is_active']): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Đang bán</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-red-500/10 text-red-500 border border-red-500/20">Tạm ẩn</span>
                            <?php endif; ?>
                        </td>

                        <!-- Thao tác -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Nút Sửa -->
                                <a href="/admin/products/<?= $product['id'] ?>/edit" 
                                   class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all" 
                                   title="Chỉnh sửa">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                
                                <!-- Form Xóa (POST để bảo mật) -->
                                <form action="/admin/products/<?= $product['id'] ?>/delete" method="POST" 
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?')">
                                    <button type="submit" 
                                            class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all"
                                            title="Xóa">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                                
                                <button class="p-2 text-slate-400 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-lg">more_horiz</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang (Demo Style) -->
<div class="mt-6 flex items-center justify-between">
    <p class="text-sm text-slate-500">Hiển thị 1 đến <?= count($rows) ?> trên tổng số <?= count($rows) ?> sản phẩm</p>
    <div class="flex gap-2">
        <button class="p-2 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-400 cursor-not-allowed">
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button class="p-2 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
    </div>
</div>