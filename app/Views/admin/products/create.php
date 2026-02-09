<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Thêm sản phẩm công nghệ mới</h4>
            </div>
            <div class="card-body">
                <form action="/admin/products" method="POST">
                    
                    <!-- Phần 1: Thông tin cơ bản -->
                    <h5 class="text-secondary border-bottom pb-2">Thông tin cơ bản</h5>
                    <div class="mb-3">
                        <label class="form-label">Tên sản phẩm</label>
                        <input type="text" name="name" class="form-control" placeholder="Ví dụ: Chuột Logitech G502" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slug (đường dẫn)</label>
                            <input type="text" name="slug" class="form-control" placeholder="chuot-logitech-g502">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Hiển thị ngay</option>
                                <option value="0">Tạm ẩn</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả sản phẩm</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- Phần 2: Biến thể mặc định (Để có giá và kho ngay khi tạo) -->
                    <h5 class="text-secondary border-bottom pb-2 mt-4">Giá & Kho hàng (Biến thể mặc định)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mã SKU</label>
                            <input type="text" name="sku" class="form-control" placeholder="LOGI-G502-01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số lượng tồn kho</label>
                            <input type="number" name="stock" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá gốc (Base Price)</label>
                            <input type="number" name="base_price" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá bán (Sale Price)</label>
                            <input type="number" name="sale_price" class="form-control" placeholder="0">
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/admin/products" class="btn btn-secondary">Quay lại</a>
                        <button type="submit" class="btn btn-success px-4">Lưu sản phẩm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>