<h2 class="mb-4">Danh sách phụ kiện</h2>
<div class="row">
    <?php if (empty($products)): ?>
        <div class="col-12">
            <div class="alert alert-info">Chưa có sản phẩm nào trong hệ thống.</div>
        </div>
    <?php else: ?>
        <?php foreach ($products as $p): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?= \App\Core\View::e($p['name']) ?></h5>
                        <p class="text-danger fw-bold">Giá từ: <?= number_format($p['price_from']) ?>đ</p>
                        <p class="text-muted small">Kho: <?= $p['stock_total'] ?> sản phẩm</p>
                        <a href="/products/<?= $p['id'] ?>" class="btn btn-outline-dark w-100">Chi tiết</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>