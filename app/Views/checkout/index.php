<?php
use App\Core\View;

$items = is_array($items ?? null) ? $items : [];
$profile = is_array($profile ?? null) ? $profile : [];
$summary = is_array($summary ?? null) ? $summary : ['subtotal' => 0, 'shipping_fee' => 0, 'total' => 0];
$userVouchers = is_array($userVouchers ?? null) ? $userVouchers : [];
$voucherLocked = (bool)($voucherLocked ?? false);
$selectedUserVoucherId = max(0, (int)($selectedUserVoucherId ?? 0));
$status = trim((string)($status ?? ''));
$selectedProducts = trim((string)($selectedProducts ?? ''));
$bankOptions = is_array($bankOptions ?? null) ? $bankOptions : [];
$selectedBankCode = strtoupper(trim((string)($selectedBankCode ?? '')));
$discountPreview = max(0, (int)($summary['discount_preview'] ?? 0));
$voucherLabel = trim((string)($summary['voucher_label'] ?? ''));
$totalAfterDiscount = max(0, (int)($summary['total_after_discount'] ?? (int)($summary['total'] ?? 0)));

$fullName = trim((string)($profile['full_name'] ?? ''));
$phone = trim((string)($profile['phone'] ?? ''));
$addressLine = trim((string)($profile['address_line'] ?? ''));
$ward = trim((string)($profile['ward'] ?? ''));
$district = trim((string)($profile['district'] ?? ''));
$city = trim((string)($profile['city'] ?? ''));
$fullAddress = trim((string)($profile['full_address'] ?? ''));
if ($fullAddress === '') {
    $fullAddress = trim(implode(', ', array_filter([$addressLine, $ward, $district, $city], static fn($v) => $v !== '')));
}
?>

<style>
.checkout-page {
    background: linear-gradient(180deg, #f7fbff 0%, #edf5ff 100%);
}

.checkout-card {
    border: 1px solid rgba(148, 163, 184, .25);
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, .06);
}

.checkout-item {
    border-bottom: 1px solid rgba(148, 163, 184, .2);
    padding-bottom: .9rem;
    margin-bottom: .9rem;
}

.checkout-item:last-child {
    border-bottom: 0;
    margin-bottom: 0;
    padding-bottom: 0;
}

.checkout-thumb {
    width: 72px;
    height: 72px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid rgba(148, 163, 184, .3);
}

.checkout-total {
    color: #1d4ed8;
    font-size: 1.3rem;
    font-weight: 800;
}

.voucher-zone {
    border: 1px dashed rgba(59, 130, 246, .45);
    border-radius: 12px;
    padding: .85rem;
    background: rgba(239, 246, 255, .72);
}

.voucher-zone.locked {
    opacity: .55;
    filter: grayscale(.12);
}
</style>

<section class="checkout-page py-4 py-lg-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4">Thanh toán</h1>

        <?php if ($status === 'payment-invalid'): ?>
            <div class="alert alert-warning">Vui lòng chọn phương thức thanh toán.</div>
        <?php elseif ($status === 'no-selection'): ?>
            <div class="alert alert-warning">Vui lòng chọn sản phẩm cần thanh toán từ giỏ hàng.</div>
        <?php elseif ($status === 'out-of-stock'): ?>
            <div class="alert alert-danger">Một số sản phẩm đã hết hàng. Vui lòng kiểm tra lại giỏ hàng.</div>
        <?php elseif ($status === 'order-failed'): ?>
            <div class="alert alert-danger">Không thể đặt hàng lúc này. Vui lòng thử lại sau.</div>
        <?php elseif ($status === 'voucher-invalid'): ?>
            <div class="alert alert-warning">Phiếu giảm giá không hợp lệ hoặc không còn khả dụng.</div>
        <?php elseif ($status === 'voucher-used'): ?>
            <div class="alert alert-warning">Phiếu giảm giá này đã được sử dụng.</div>
        <?php elseif ($status === 'voucher-disabled'): ?>
            <div class="alert alert-warning">Phiếu giảm giá hiện đang bị tắt.</div>
        <?php elseif ($status === 'voucher-not-started'): ?>
            <div class="alert alert-warning">Phiếu giảm giá chưa đến ngày áp dụng.</div>
        <?php elseif ($status === 'voucher-expired'): ?>
            <div class="alert alert-warning">Phiếu giảm giá đã hết hạn.</div>
        <?php elseif ($status === 'voucher-single-only'): ?>
            <div class="alert alert-warning">Phiếu giảm giá chỉ áp dụng khi thanh toán 1 sản phẩm.</div>
        <?php elseif ($status === 'voucher-qty-limit'): ?>
            <div class="alert alert-warning">Phiếu giảm giá chỉ áp dụng khi số lượng sản phẩm không vượt quá 2.</div>
        <?php elseif ($status === 'voucher-profit-exceeded'): ?>
            <div class="alert alert-danger">Phiếu không được áp dụng với sản phẩm này.</div>
        <?php elseif ($status === 'voucher-product-discount-active'): ?>
            <div class="alert alert-warning">Sản phẩm đang có chương trình giảm giá nên không thể áp dụng thêm phiếu giảm giá.</div>
        <?php elseif ($status === 'bank-required'): ?>
            <div class="alert alert-warning">Bạn cần chọn ngân hàng để thanh toán qua VNPAY.</div>
        <?php elseif ($status === 'vnpay-config-missing'): ?>
            <div class="alert alert-danger">Cấu hình VNPAY chưa đầy đủ. Vui lòng kiểm tra lại cấu hình hệ thống.</div>
        <?php elseif ($status === 'vnpay-invalid'): ?>
            <div class="alert alert-warning">Dữ liệu trả về từ VNPAY không hợp lệ.</div>
        <?php elseif ($status === 'vnpay-signature-invalid'): ?>
            <div class="alert alert-danger">Chữ ký giao dịch VNPAY không hợp lệ.</div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="checkout-card bg-white p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h5 fw-bold mb-3">Thông tin giao hàng</h2>
                            <p class="mb-1"><strong>Tên người nhận:</strong> <?= View::e($fullName !== '' ? $fullName : 'Chưa cập nhật') ?></p>
                            <p class="mb-1"><strong>Số điện thoại:</strong> <?= View::e($phone !== '' ? $phone : 'Chưa cập nhật') ?></p>
                            <p class="mb-0"><strong>Địa chỉ giao hàng:</strong> <?= View::e($fullAddress !== '' ? $fullAddress : 'Chưa cập nhật') ?></p>
                        </div>
                        <a href="/account/edit" class="btn btn-outline-primary btn-sm">Cập nhật thông tin</a>
                    </div>
                </div>

                <div class="checkout-card bg-white p-4">
                    <h2 class="h5 fw-bold mb-3">Danh sách sản phẩm trong đơn hàng</h2>
                    <?php foreach ($items as $item): ?>
                        <?php $productLink = '/product/' . urlencode((string)($item['slug'] ?? $item['product_id'])); ?>
                        <div class="checkout-item d-flex justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= View::e((string)($item['image'] ?? '') !== '' ? (string)$item['image'] : '/images/placeholder-product.svg') ?>" alt="<?= View::e((string)($item['name'] ?? 'Sản phẩm')) ?>" class="checkout-thumb">
                                <div>
                                    <a href="<?= View::e($productLink) ?>" class="text-decoration-none fw-semibold text-dark"><?= View::e((string)($item['name'] ?? 'Sản phẩm')) ?></a>
                                    <div class="small text-muted">Giá: <?= number_format((int)($item['price'] ?? 0)) ?>₫</div>
                                    <div class="small text-muted">Số lượng: <?= (int)($item['quantity'] ?? 1) ?></div>
                                </div>
                            </div>
                            <div class="fw-bold text-primary"><?= number_format((int)($item['line_total'] ?? 0)) ?>₫</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="checkout-card bg-white p-4">
                    <h2 class="h5 fw-bold mb-3">Tổng tiền</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tạm tính</span>
                        <strong><?= number_format((int)($summary['subtotal'] ?? 0)) ?>₫</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Phí vận chuyển</span>
                        <strong><?= number_format((int)($summary['shipping_fee'] ?? 0)) ?>₫</strong>
                    </div>
                    <?php if ($discountPreview > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success">Giảm giá<?= $voucherLabel !== '' ? ' (' . View::e($voucherLabel) . ')' : '' ?></span>
                            <strong class="text-success">-<?= number_format($discountPreview) ?>₫</strong>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-semibold">Tổng thanh toán</span>
                        <span class="checkout-total"><?= number_format($totalAfterDiscount) ?>₫</span>
                    </div>

                    <form method="POST" action="/checkout/place" class="mt-3" id="checkoutPlaceForm">
                        <input type="hidden" name="selected_products" value="<?= View::e($selectedProducts) ?>">

                        <div class="voucher-zone mb-3 <?= $voucherLocked ? 'locked' : '' ?>">
                            <label class="form-label fw-semibold mb-2">Áp dụng phiếu giảm giá</label>
                            <select name="user_voucher_id" class="form-select" id="checkoutVoucherSelect" <?= $voucherLocked ? 'disabled' : '' ?>>
                                <option value="0">Không sử dụng phiếu</option>
                                <?php foreach ($userVouchers as $voucher): ?>
                                    <?php $uvId = (int)($voucher['user_voucher_id'] ?? 0); ?>
                                    <option value="<?= $uvId ?>" <?= $selectedUserVoucherId === $uvId ? 'selected' : '' ?>>
                                        <?= View::e((string)($voucher['name'] ?? 'Phiếu giảm giá')) ?> - Giảm <?= number_format((int)($voucher['discount_amount'] ?? 0)) ?>đ (<?= View::e((string)($voucher['code'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($voucherLocked): ?>
                                <div class="small text-muted mt-2">Phần áp dụng phiếu giảm giá đang bị làm mờ vì bạn đang chọn nhiều hơn 2 số lượng sản phẩm hoặc nhiều hơn 1 sản phẩm để thanh toán.</div>
                            <?php elseif (empty($userVouchers)): ?>
                                <div class="small text-muted mt-2">Bạn chưa có phiếu giảm giá khả dụng. Hãy lấy phiếu tại trang chủ.</div>
                            <?php else: ?>
                                <div class="small text-muted mt-2">Phiếu chỉ áp dụng khi thanh toán 1 sản phẩm và giá trị giảm không vượt quá lợi nhuận sản phẩm.</div>
                            <?php endif; ?>
                        </div>

                        <label class="form-label fw-semibold">Phương thức thanh toán</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="payCod" value="cod">
                            <label class="form-check-label" for="payCod">Thanh toán khi nhận hàng (COD)</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="payBank" value="bank_transfer">
                            <label class="form-check-label" for="payBank">Chuyển khoản ngân hàng</label>
                        </div>

                        <div class="mb-3" id="bankCodeWrap" style="display:none;">
                            <label class="form-label fw-semibold" for="bankCodeSelect">Ngân hàng thanh toán</label>
                            <select class="form-select" id="bankCodeSelect" name="bank_code" data-selected-bank="<?= View::e($selectedBankCode) ?>">
                                <option value="">Chọn ngân hàng</option>
                                <?php foreach ($bankOptions as $code => $label): ?>
                                    <option value="<?= View::e((string)$code) ?>" <?= $selectedBankCode === (string)$code ? 'selected' : '' ?>>
                                        <?= View::e((string)$label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="small text-muted mt-1">Bạn sẽ được chuyển sang cổng thanh toán VNPAY Sandbox để hoàn tất giao dịch.</div>
                        </div>

                        <label class="form-label fw-semibold" for="customerNote">Ghi chú đơn hàng</label>
                        <textarea class="form-control mb-3" id="customerNote" name="customer_note" rows="3" placeholder="Nhập ghi chú nếu cần"></textarea>

                        <button type="submit" class="btn btn-primary btn-lg w-100">Đặt hàng</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('checkoutPlaceForm');
    var payCod = document.getElementById('payCod');
    var payBank = document.getElementById('payBank');
    var bankWrap = document.getElementById('bankCodeWrap');
    var bankSelect = document.getElementById('bankCodeSelect');
    if (!form) return;

    if (bankSelect && !bankSelect.value) {
        var preset = bankSelect.getAttribute('data-selected-bank') || '';
        if (preset !== '') {
            bankSelect.value = preset;
        }
    }

    var toggleBankSelect = function () {
        if (!bankWrap || !bankSelect) {
            return;
        }

        var enabled = !!(payBank && payBank.checked);
        bankWrap.style.display = enabled ? 'block' : 'none';
        bankSelect.required = enabled;
    };

    if (payCod) {
        payCod.addEventListener('change', toggleBankSelect);
    }
    if (payBank) {
        payBank.addEventListener('change', toggleBankSelect);
    }
    toggleBankSelect();

    // Form submit validation
    form.addEventListener('submit', function (e) {
        if (payBank && payBank.checked) {
            if (!bankSelect || !bankSelect.value) {
                e.preventDefault();
                alert('Vui lòng chọn ngân hàng để thanh toán');
                return false;
            }
        }
    });

    <?php if (!$voucherLocked): ?>
    var select = document.getElementById('checkoutVoucherSelect');
    if (select) {
        select.addEventListener('change', function () {
            var current = new URL(window.location.href);
            current.searchParams.set('selected_products', '<?= View::e($selectedProducts) ?>');
            current.searchParams.set('user_voucher_id', select.value || '0');
            if (bankSelect && bankSelect.value) {
                current.searchParams.set('bank_code', bankSelect.value);
            }
            current.searchParams.delete('status');
            window.location.href = current.pathname + '?' + current.searchParams.toString();
        });
    }
    <?php endif; ?>
})();
</script>
