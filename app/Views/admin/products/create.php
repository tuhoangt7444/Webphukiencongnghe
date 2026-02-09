<?php
$old = $_SESSION['old'] ?? [];
$error = $_SESSION['error'] ?? null;
unset($_SESSION['old'], $_SESSION['error']);
?>

<div class="max-w-5xl mx-auto">

    <!-- ALERT -->
    <?php if ($error): ?>
        <div class="mb-6 rounded-xl bg-red-100 border border-red-300 text-red-700 px-5 py-4 flex items-center gap-3">
            <span class="material-symbols-outlined">warning</span>
            <div>
                <strong>Lỗi:</strong> <?= $error ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-[#161b28] rounded-2xl shadow-lg overflow-hidden">

        <!-- HEADER -->
        <div class="px-6 py-4 bg-gradient-to-r from-primary to-blue-600 text-white">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <span class="material-symbols-outlined">add_circle</span>
                Thêm sản phẩm công nghệ mới
            </h2>
        </div>

        <!-- BODY -->
        <div class="p-6">
            <form action="/admin/products" method="POST" class="space-y-6">

                <!-- TÊN -->
                <div>
                    <label class="block mb-1 font-semibold">Tên sản phẩm *</label>
                    <input type="text" name="name" id="product_name" required
                        value="<?= \App\Core\View::e($old['name'] ?? '') ?>"
                        placeholder="Ví dụ: Bàn phím cơ AKKO 3068"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                               bg-white dark:bg-[#101622]
                               px-4 py-3 focus:ring-2 focus:ring-primary">
                </div>

                <!-- SLUG + TRẠNG THÁI -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block mb-1 font-semibold">Slug</label>
                        <input type="text" name="slug" id="product_slug"
                            value="<?= \App\Core\View::e($old['slug'] ?? '') ?>"
                            placeholder="ban-phim-co-akko-3068"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                   bg-white dark:bg-[#101622]
                                   px-4 py-2 focus:ring-primary focus:ring-2">
                        <p class="text-sm text-slate-400 mt-1">Để trống nếu muốn hệ thống tự tạo</p>
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Trạng thái</label>
                        <select name="is_active"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                   bg-white dark:bg-[#101622]
                                   px-4 py-2 focus:ring-primary focus:ring-2">
                            <option value="1" <?= ($old['is_active'] ?? '') == '1' ? 'selected' : '' ?>>Đang bán</option>
                            <option value="0" <?= ($old['is_active'] ?? '') == '0' ? 'selected' : '' ?>>Tạm ẩn</option>
                        </select>
                    </div>
                </div>

                <!-- MÔ TẢ -->
                <div>
                    <label class="block mb-1 font-semibold">Mô tả chi tiết</label>
                    <textarea name="description" rows="4"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                               bg-white dark:bg-[#101622]
                               px-4 py-2 focus:ring-primary focus:ring-2"
                        placeholder="Thông số kỹ thuật, đặc điểm nổi bật..."><?= \App\Core\View::e($old['description'] ?? '') ?></textarea>
                </div>

                <!-- KHỐI GIÁ & KHO -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-5 bg-slate-50 dark:bg-[#101622]">
                    <h3 class="mb-4 font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">inventory</span>
                        Giá & Kho hàng
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block mb-1 font-semibold">SKU</label>
                            <input type="text" name="sku"
                                value="<?= \App\Core\View::e($old['sku'] ?? '') ?>"
                                placeholder="AKKO-3068-01"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                       bg-white dark:bg-[#101622]
                                       px-4 py-2">
                        </div>

                        <div>
                            <label class="block mb-1 font-semibold">Tồn kho</label>
                            <input type="number" name="stock" min="0"
                                value="<?= $old['stock'] ?? 0 ?>"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                       bg-white dark:bg-[#101622]
                                       px-4 py-2">
                        </div>

                        <div>
                            <label class="block mb-1 font-semibold text-green-600">Giá nhập (VNĐ)</label>
                            <div class="flex">
                                <input type="number" name="base_price" required
                                    value="<?= $old['base_price'] ?? '' ?>"
                                    placeholder="100000"
                                    class="flex-1 rounded-l-lg border border-slate-300 dark:border-slate-700
                                           bg-white dark:bg-[#101622]
                                           px-4 py-2">
                                <span class="rounded-r-lg bg-slate-200 dark:bg-slate-700 px-4 flex items-center">₫</span>
                            </div>
                            <p class="text-sm text-slate-400 mt-1">Hệ thống tự tính giá bán</p>
                        </div>

                        <div>
                            <label class="block mb-1 font-semibold">Giá bán dự kiến</label>
                            <input type="text" disabled
                                value="Hệ thống tự tính..."
                                class="w-full rounded-lg bg-slate-100 dark:bg-slate-800
                                       text-slate-500 px-4 py-2">
                        </div>

                    </div>
                </div>

                <!-- ACTION -->
                <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4 border-t dark:border-slate-700">
                    <a href="/admin/products"
                       class="inline-flex items-center gap-2 px-5 py-2 rounded-lg
                              bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-white">
                        ← Quay lại
                    </a>

                    <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2 rounded-lg
                               bg-primary text-white font-semibold hover:opacity-90">
                        💾 Lưu sản phẩm
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- SCRIPT TẠO SLUG -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('product_name');
    const slugInput = document.getElementById('product_slug');

    nameInput.addEventListener('input', function() {
        let slug = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[đĐ]/g, 'd')
            .replace(/[^0-9a-z\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');

        slugInput.value = slug;
    });
});
</script>
