<?php
use App\Core\View;

$rawItems = $cartItems ?? ($items ?? []);
$items = is_array($rawItems) ? array_values($rawItems) : [];

$normalizedItems = [];
$totalQty = 0;
$subtotal = 0;

foreach ($items as $item) {
    $quantity = (int)($item['quantity'] ?? ($item['qty'] ?? 1));
    $price = (int)($item['price'] ?? 0);
    $originalPrice = (int)($item['original_price'] ?? $price);
    $discountPct = (int)($item['discount_percent'] ?? 0);
    $stock = (int)($item['stock'] ?? ($item['stock_total'] ?? 0));
    $lineTotal = $price * max(1, $quantity);

    $normalizedItems[] = [
        'product_id' => (int)($item['product_id'] ?? 0),
        'name' => (string)($item['name'] ?? 'Sản phẩm'),
        'slug' => (string)($item['slug'] ?? ''),
        'image' => trim((string)($item['image'] ?? '')),
        'price' => $price,
        'original_price' => $originalPrice,
        'discount_percent' => $discountPct,
        'quantity' => max(1, $quantity),
        'stock' => max(0, $stock),
        'line_total' => $lineTotal,
    ];

    $totalQty += max(1, $quantity);
    $subtotal += $lineTotal;
}

$status = (string)($status ?? '');
if (($totalAmount ?? null) !== null && (int)$totalAmount > 0) {
    $subtotal = (int)$totalAmount;
}
?>

<style>
.cart-page {
    background: linear-gradient(180deg, #f7fbff 0%, #eef5ff 100%);
}

.cart-card {
    border: 1px solid rgba(148, 163, 184, .24);
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, .06);
}

.cart-row {
    transition: box-shadow .24s ease, transform .24s ease;
}

.cart-row:hover {
    box-shadow: 0 10px 20px rgba(30, 64, 175, .14);
    transform: translateY(-2px);
}

.cart-thumb {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid rgba(148, 163, 184, .3);
}

.qty-control {
    max-width: 142px;
}

.summary-price {
    font-size: 1.32rem;
    font-weight: 800;
    color: #1d4ed8;
}

.form-check-input.cart-select-item,
.form-check-input#cartSelectAll {
    width: 1.1rem;
    height: 1.1rem;
}

@media (max-width: 991.98px) {
    .cart-actions .btn {
        width: 100%;
    }
}
</style>

<section class="cart-page py-4 py-lg-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4">Giỏ hàng của bạn</h1>

        <?php if ($status === 'added'): ?>
            <div class="alert alert-success">Sản phẩm đã được thêm vào giỏ hàng.</div>
        <?php elseif ($status === 'updated'): ?>
            <div class="alert alert-info">Giỏ hàng đã được cập nhật.</div>
        <?php elseif ($status === 'removed'): ?>
            <div class="alert alert-warning">Sản phẩm đã được xóa khỏi giỏ hàng.</div>
        <?php elseif ($status === 'no-selection'): ?>
            <div class="alert alert-warning">Vui lòng chọn ít nhất một sản phẩm để thanh toán.</div>
        <?php endif; ?>

        <?php if (empty($normalizedItems)): ?>
            <div class="cart-card bg-white p-5 text-center">
                <h2 class="h5 fw-semibold mb-2">Giỏ hàng của bạn đang trống</h2>
                <p class="text-muted mb-4">Hãy chọn thêm sản phẩm để tiếp tục mua sắm.</p>
                <a href="/products" class="btn btn-primary px-4">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="cart-card bg-white p-3 p-md-4">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:56px;">
                                            <input id="cartSelectAll" class="form-check-input" type="checkbox" checked>
                                        </th>
                                        <th>Hình ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th class="text-end">Giá</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-end">Thành tiền</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($normalizedItems as $item): ?>
                                        <?php
                                            $productLink = '/product/' . urlencode($item['slug'] !== '' ? $item['slug'] : (string)$item['product_id']);
                                            $maxStock = max(1, $item['stock']);
                                        ?>
                                        <tr class="cart-row">
                                            <td class="text-center">
                                                <input
                                                    class="form-check-input cart-select-item"
                                                    type="checkbox"
                                                    value="<?= $item['product_id'] ?>"
                                                    data-product-id="<?= $item['product_id'] ?>"
                                                    data-qty="<?= $item['quantity'] ?>"
                                                    data-line-total="<?= $item['line_total'] ?>"
                                                    checked
                                                >
                                            </td>
                                            <td>
                                                <img class="cart-thumb" src="<?= View::e($item['image'] !== '' ? $item['image'] : '/images/placeholder-product.svg') ?>" alt="<?= View::e($item['name']) ?>">
                                            </td>
                                            <td>
                                                <a href="<?= View::e($productLink) ?>" class="text-decoration-none fw-semibold text-dark">
                                                    <?= View::e($item['name']) ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($item['discount_percent'] > 0 && $item['original_price'] > $item['price']): ?>
                                                    <div class="text-muted text-decoration-line-through small" style="white-space:nowrap;"><?= number_format($item['original_price']) ?>₫</div>
                                                    <div class="fw-bold text-danger" style="white-space:nowrap;"><?= number_format($item['price']) ?>₫</div>
                                                    <span class="badge text-bg-danger">-<?= $item['discount_percent'] ?>%</span>
                                                <?php else: ?>
                                                    <div class="fw-semibold" style="white-space:nowrap;"><?= number_format($item['price']) ?>₫</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="/cart/update" class="js-update-form m-0">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <div class="input-group input-group-sm qty-control mx-auto">
                                                        <button class="btn btn-outline-secondary js-qty-minus" type="button">-</button>
                                                        <input type="number" class="form-control text-center js-qty-input" name="qty" min="1" max="<?= $maxStock ?>" value="<?= $item['quantity'] ?>">
                                                        <button class="btn btn-outline-secondary js-qty-plus" type="button">+</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="text-end fw-bold text-primary"><?= number_format($item['line_total']) ?>₫</td>
                                            <td class="text-center">
                                                <form method="POST" action="/cart/remove" class="m-0">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa sản phẩm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="cart-card bg-white p-4 position-sticky" style="top: 96px;">
                        <h2 class="h5 fw-bold mb-3">Tổng tiền giỏ hàng</h2>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Sản phẩm đã chọn</span>
                            <strong id="selectedQtyText"><?= $totalQty ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tạm tính</span>
                            <strong id="selectedSubtotalText"><?= number_format($subtotal) ?>₫</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-semibold">Tổng thanh toán</span>
                            <span class="summary-price" id="selectedTotalText"><?= number_format($subtotal) ?>₫</span>
                        </div>

                        <div class="cart-actions d-grid gap-2">
                            <a href="/products" class="btn btn-outline-primary">Tiếp tục mua sắm</a>
                            <form method="GET" action="/checkout" class="m-0" id="checkoutSelectedForm">
                                <input type="hidden" name="selected_products" id="selectedProductsInput" value="">
                                <button type="submit" class="btn btn-primary w-100">Thanh toán</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(() => {
    const forms = Array.from(document.querySelectorAll('.js-update-form'));
    const selectAll = document.getElementById('cartSelectAll');
    const itemCheckboxes = Array.from(document.querySelectorAll('.cart-select-item'));
    const selectedQtyText = document.getElementById('selectedQtyText');
    const selectedSubtotalText = document.getElementById('selectedSubtotalText');
    const selectedTotalText = document.getElementById('selectedTotalText');
    const selectedProductsInput = document.getElementById('selectedProductsInput');
    const checkoutSelectedForm = document.getElementById('checkoutSelectedForm');

    const formatMoney = (num) => {
        return Number(num || 0).toLocaleString('vi-VN') + '₫';
    };

    const syncSelectionSummary = () => {
        let qty = 0;
        let total = 0;
        const selectedIds = [];

        itemCheckboxes.forEach((cb) => {
            if (!cb.checked) {
                return;
            }

            const itemQty = Number(cb.dataset.qty || 0);
            const lineTotal = Number(cb.dataset.lineTotal || 0);
            const productId = String(cb.dataset.productId || '').trim();

            qty += itemQty;
            total += lineTotal;
            if (productId !== '') {
                selectedIds.push(productId);
            }
        });

        if (selectedQtyText) {
            selectedQtyText.textContent = String(qty);
        }
        if (selectedSubtotalText) {
            selectedSubtotalText.textContent = formatMoney(total);
        }
        if (selectedTotalText) {
            selectedTotalText.textContent = formatMoney(total);
        }
        if (selectedProductsInput) {
            selectedProductsInput.value = selectedIds.join(',');
        }

        if (selectAll) {
            const allChecked = itemCheckboxes.length > 0 && itemCheckboxes.every((cb) => cb.checked);
            selectAll.checked = allChecked;
        }
    };

    forms.forEach((form) => {
        const input = form.querySelector('.js-qty-input');
        const minus = form.querySelector('.js-qty-minus');
        const plus = form.querySelector('.js-qty-plus');

        if (!input || !minus || !plus) {
            return;
        }

        const min = Number(input.min || 1);
        const max = Number(input.max || 9999);

        minus.addEventListener('click', () => {
            const current = Number(input.value || min);
            input.value = String(Math.max(min, current - 1));
        });

        plus.addEventListener('click', () => {
            const current = Number(input.value || min);
            input.value = String(Math.min(max, current + 1));
        });
    });

    const updateBtn = document.getElementById('btnUpdateCart');
    if (updateBtn) {
        updateBtn.addEventListener('click', () => {
            if (forms.length === 0) {
                return;
            }
            forms[0].submit();
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            itemCheckboxes.forEach((cb) => {
                cb.checked = selectAll.checked;
            });
            syncSelectionSummary();
        });
    }

    itemCheckboxes.forEach((cb) => {
        cb.addEventListener('change', syncSelectionSummary);
    });

    if (checkoutSelectedForm) {
        checkoutSelectedForm.addEventListener('submit', (event) => {
            syncSelectionSummary();
            if (!selectedProductsInput || selectedProductsInput.value.trim() === '') {
                event.preventDefault();
                alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán.');
            }
        });
    }

    syncSelectionSummary();
})();
</script>
