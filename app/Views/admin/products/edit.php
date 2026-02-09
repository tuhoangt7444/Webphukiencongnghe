<div class="max-w-4xl mx-auto bg-white dark:bg-[#161b28] rounded-2xl shadow-lg overflow-hidden">

    <!-- HEADER -->
    <div class="px-6 py-4 bg-gradient-to-r from-yellow-400 to-yellow-500 text-slate-900">
        <h4 class="text-lg font-bold">
            ✏️ Sửa sản phẩm:
            <span class="font-semibold">
                <?= \App\Core\View::e($row['name']) ?>
            </span>
        </h4>
    </div>

    <!-- BODY -->
    <div class="p-6">
        <form action="/admin/products/<?= $row['id'] ?>" method="POST" class="space-y-6">

            <!-- TÊN SẢN PHẨM -->
            <div>
                <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-200">
                    Tên sản phẩm
                </label>
                <input type="text" name="name" required
                       value="<?= \App\Core\View::e($row['name']) ?>"
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-[#101622]
                              px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <!-- SLUG + TRẠNG THÁI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-slate-600 dark:text-slate-300">
                        Slug
                    </label>
                    <input type="text" name="slug"
                           value="<?= \App\Core\View::e($row['slug']) ?>"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-[#101622]
                                  px-4 py-2 focus:ring-primary focus:ring-2">
                </div>

                <div>
                    <label class="block mb-1 text-slate-600 dark:text-slate-300">
                        Trạng thái
                    </label>
                    <select name="is_active"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                   bg-white dark:bg-[#101622]
                                   px-4 py-2 focus:ring-primary focus:ring-2">
                        <option value="1" <?= $row['is_active'] ? 'selected' : '' ?>>Hiển thị</option>
                        <option value="0" <?= !$row['is_active'] ? 'selected' : '' ?>>Ẩn</option>
                    </select>
                </div>
            </div>

            <!-- SKU + STOCK -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-200">
                        Mã SKU
                    </label>
                    <input type="text" name="sku"
                           value="<?= \App\Core\View::e($row['sku']) ?>"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-[#101622]
                                  px-4 py-2 focus:ring-primary focus:ring-2">
                </div>

                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-200">
                        Số lượng kho
                    </label>
                    <input type="number" name="stock"
                           value="<?= $row['stock'] ?>"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-[#101622]
                                  px-4 py-2 focus:ring-primary focus:ring-2">
                </div>
            </div>

            <!-- GIÁ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-200">
                        Giá nhập / Giá vốn
                    </label>
                    <input type="number" name="base_price" required
                           value="<?= $row['base_price'] ?>"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-[#101622]
                                  px-4 py-2 focus:ring-primary focus:ring-2">
                    <p class="mt-1 text-sm text-sky-500">
                        Giá bán hiện tại:
                        <strong><?= number_format($row['sale_price']) ?>đ</strong>
                    </p>
                </div>

                <div>
                    <label class="block mb-1 text-slate-600 dark:text-slate-300">
                        Giá bán
                    </label>
                    <input type="text" readonly
                           value="Sẽ tự động cập nhật khi lưu"
                           class="w-full rounded-lg bg-slate-100 dark:bg-slate-800
                                  text-slate-500 px-4 py-2 cursor-not-allowed">
                </div>
            </div>

            <!-- ACTION -->
            <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="history.back()"
                        class="px-5 py-2 rounded-lg bg-slate-200 dark:bg-slate-700
                               text-slate-700 dark:text-slate-200 hover:opacity-90">
                    ← Quay lại
                </button>

                <button type="submit"
                        class="px-6 py-2 rounded-lg bg-green-500 text-white font-semibold
                               hover:bg-green-600 transition">
                    💾 Cập nhật
                </button>
            </div>

        </form>
    </div>
</div>