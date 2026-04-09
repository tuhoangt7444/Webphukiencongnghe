<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;

class CheckoutController extends Controller
{
    private const VNPAY_BANKS = [
        'NCB' => 'NCB',
        'VCB' => 'Vietcombank',
        'BIDV' => 'BIDV',
        'VIB' => 'VIB',
        'ACB' => 'ACB',
        'TCB' => 'Techcombank',
        'MBBANK' => 'MB Bank',
        'VPBANK' => 'VPBank',
        'TPBANK' => 'TPBank',
        'SACOMBANK' => 'Sacombank',
    ];

    public function index(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $next = '/checkout';
            $selectedRaw = trim((string)$this->request->input('selected_products', ''));
            if ($selectedRaw !== '') {
                $next .= '?selected_products=' . rawurlencode($selectedRaw);
            }
            $this->response->redirect('/login?status=buy-login-required&next=' . rawurlencode($next));
            return;
        }

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            $this->response->redirect('/cart?status=empty');
            return;
        }

        $selectedIds = $this->parseSelectedProductIds($this->request->input('selected_products', ''));
        $checkoutCart = $this->filterCartBySelectedProducts($cart, $selectedIds);
        if (empty($checkoutCart)) {
            $this->response->redirect('/cart?status=no-selection');
            return;
        }

        $profile = User::getCustomerProfileByUserId((int)$_SESSION['user_id']);
        $status = trim((string)$this->request->input('status', ''));

        $items = $this->normalizeCartItems($checkoutCart);
        $summary = $this->calculateSummary($items);
        $totalQuantity = 0;
        foreach ($items as $item) {
            $totalQuantity += (int)($item['quantity'] ?? 1);
        }

        $voucherLocked = count($items) !== 1 || $totalQuantity > 2;
        $userVouchers = Voucher::claimedByUser((int)$_SESSION['user_id']);
        $selectedUserVoucherId = max(0, (int)$this->request->input('user_voucher_id', 0));
        $selectedBankCode = strtoupper(trim((string)$this->request->input('bank_code', '')));
        if ($selectedBankCode !== '' && !isset(self::VNPAY_BANKS[$selectedBankCode])) {
            $selectedBankCode = '';
        }
        $voucherPreviewDiscount = 0;
        $voucherPreviewLabel = '';

        if (!$voucherLocked && $selectedUserVoucherId > 0) {
            $validation = Voucher::validateUserVoucherForCheckout((int)$_SESSION['user_id'], $selectedUserVoucherId, $checkoutCart);
            if (($validation['valid'] ?? false) === true) {
                $voucherPreviewDiscount = max(0, (int)($validation['discount_total'] ?? 0));
                $voucherData = $validation['voucher'] ?? [];
                if (is_array($voucherData)) {
                    $voucherPreviewLabel = trim((string)($voucherData['code'] ?? ''));
                }
            } else {
                $status = match ((string)($validation['error'] ?? '')) {
                    'voucher:not-found' => 'voucher-invalid',
                    'voucher:used' => 'voucher-used',
                    'voucher:disabled' => 'voucher-disabled',
                    'voucher:not-started' => 'voucher-not-started',
                    'voucher:expired' => 'voucher-expired',
                    'voucher:single-product-only' => 'voucher-single-only',
                    'voucher:quantity-limit' => 'voucher-qty-limit',
                    'voucher:profit-exceeded' => 'voucher-profit-exceeded',
                    'voucher:product-discount-active' => 'voucher-product-discount-active',
                    default => $status,
                };
            }
        }

        $summary['discount_preview'] = $voucherPreviewDiscount;
        $summary['voucher_label'] = $voucherPreviewLabel;
        $summary['total_after_discount'] = max(0, (int)($summary['total'] ?? 0) - $voucherPreviewDiscount);

        $this->view('checkout/index', [
            'title' => 'Thanh toán',
            'items' => $items,
            'summary' => $summary,
            'userVouchers' => $userVouchers,
            'voucherLocked' => $voucherLocked,
            'selectedUserVoucherId' => $selectedUserVoucherId,
            'profile' => $profile,
            'status' => $status,
            'selectedProducts' => implode(',', array_keys($checkoutCart)),
            'bankOptions' => self::VNPAY_BANKS,
            'selectedBankCode' => $selectedBankCode,
        ]);
    }

    public function place(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=buy-login-required&next=/checkout');
            return;
        }

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            $this->response->redirect('/cart?status=empty');
            return;
        }

        $selectedRaw = $this->request->input('selected_products', '');
        $selectedIds = $this->parseSelectedProductIds($selectedRaw);
        $checkoutCart = $this->filterCartBySelectedProducts($cart, $selectedIds);
        if (empty($checkoutCart)) {
            $this->response->redirect('/checkout?status=no-selection');
            return;
        }

        $profile = User::getCustomerProfileByUserId((int)$_SESSION['user_id']);

        $phone = trim((string)($profile['phone'] ?? ''));
        if ($phone === '') {
            $this->response->redirect('/account/edit?status=phone-required');
            return;
        }

        $addressLine = trim((string)($profile['address_line'] ?? ''));
        $district = trim((string)($profile['district'] ?? ''));
        $city = trim((string)($profile['city'] ?? ''));
        $fullAddress = trim((string)($profile['full_address'] ?? ''));

        if ($fullAddress === '') {
            $fullAddress = $this->composeFullAddress($profile);
        }

        if ($addressLine === '' || $district === '' || $city === '') {
            $this->response->redirect('/account/edit?status=address-required');
            return;
        }

        $paymentMethod = trim((string)$this->request->input('payment_method', ''));
        if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
            $this->response->redirect('/checkout?status=payment-invalid&selected_products=' . rawurlencode(implode(',', array_keys($checkoutCart))));
            return;
        }

        $bankCode = strtoupper(trim((string)$this->request->input('bank_code', '')));
        if ($paymentMethod === 'bank_transfer') {
            if ($bankCode === '' || !isset(self::VNPAY_BANKS[$bankCode])) {
                $this->response->redirect('/checkout?status=bank-required&selected_products=' . rawurlencode(implode(',', array_keys($checkoutCart))));
                return;
            }
        }

        $paymentLabel = match ($paymentMethod) {
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            default => 'Chuyển khoản ngân hàng',
        };

        $customerInfo = [
            'full_name' => trim((string)($profile['full_name'] ?? '')),
            'phone' => $phone,
            'address_line' => $addressLine,
            'ward' => trim((string)($profile['ward'] ?? '')),
            'district' => $district,
            'city' => $city,
            'full_address' => $fullAddress,
        ];

        if (!$this->isValidCustomerInfo($customerInfo)) {
            $this->response->redirect('/account/edit?status=profile-required');
            return;
        }

        $customerNote = trim((string)$this->request->input('customer_note', ''));
        $customerNote = $customerNote !== ''
            ? ('[' . $paymentLabel . '] ' . $customerNote)
            : ('Phương thức thanh toán: ' . $paymentLabel);

        $selectedUserVoucherId = max(0, (int)$this->request->input('user_voucher_id', 0));

        try {
            $orderId = Order::createFromCart((int)$_SESSION['user_id'], $checkoutCart, $customerInfo, $customerNote, $selectedUserVoucherId > 0 ? $selectedUserVoucherId : null);

            $orderAmount = $this->sumCheckoutTotal($checkoutCart);
            if ($paymentMethod === 'bank_transfer') {
                $freshOrder = Order::findOrderByIdAndUser($orderId, (int)$_SESSION['user_id']);
                $orderAmount = max(0, (int)($freshOrder['total'] ?? $orderAmount));
            }

            $methodCode = $paymentMethod === 'bank_transfer' ? 'bank' : 'cod';
            Order::ensurePayment($orderId, $methodCode, $orderAmount);

            foreach (array_keys($checkoutCart) as $productId) {
                unset($cart[(string)$productId]);
            }

            if (empty($cart)) {
                unset($_SESSION['cart']);
            } else {
                $_SESSION['cart'] = $cart;
            }

            if ($paymentMethod === 'bank_transfer') {
                $paymentUrl = $this->buildVnpayPaymentUrl($orderId, $orderAmount, $bankCode);
                if ($paymentUrl === null) {
                    $this->response->redirect('/checkout?status=vnpay-config-missing');
                    return;
                }

                $this->response->redirect($paymentUrl);
                return;
            }

            $this->response->redirect('/checkout/success');
        } catch (\Throwable $e) {
            $status = match ((string)$e->getMessage()) {
                'stock:insufficient' => 'out-of-stock',
                'voucher:not-found' => 'voucher-invalid',
                'voucher:used' => 'voucher-used',
                'voucher:disabled' => 'voucher-disabled',
                'voucher:not-started' => 'voucher-not-started',
                'voucher:expired' => 'voucher-expired',
                'voucher:single-product-only' => 'voucher-single-only',
                'voucher:quantity-limit' => 'voucher-qty-limit',
                'voucher:profit-exceeded' => 'voucher-profit-exceeded',
                'voucher:product-discount-active' => 'voucher-product-discount-active',
                default => 'order-failed',
            };
            $query = [
                'status' => $status,
                'selected_products' => implode(',', array_keys($checkoutCart)),
            ];
            if ($selectedUserVoucherId > 0) {
                $query['user_voucher_id'] = (string)$selectedUserVoucherId;
            }
            $this->response->redirect('/checkout?' . http_build_query($query));
        }
    }

    public function success(): void
    {
        $this->view('checkout/success', [
            'title' => 'Đặt hàng thành công',
        ]);
    }

    public function vnpayReturn(): void
    {
        $this->ensureSession();

        $vnpData = $this->collectVnpayParams();
        $secureHash = (string)($vnpData['vnp_SecureHash'] ?? '');
        $txnRef = trim((string)($vnpData['vnp_TxnRef'] ?? ''));
        $responseCode = trim((string)($vnpData['vnp_ResponseCode'] ?? ''));
        $transactionStatus = trim((string)($vnpData['vnp_TransactionStatus'] ?? ''));

        $orderId = (int)$txnRef;
        if ($orderId <= 0 || !isset($_SESSION['user_id'])) {
            $this->response->redirect('/checkout?status=vnpay-invalid');
            return;
        }

        if (!$this->isValidVnpaySignature($vnpData, $secureHash)) {
            $this->response->redirect('/checkout?status=vnpay-signature-invalid');
            return;
        }

        $order = Order::findOrderByIdAndUser($orderId, (int)$_SESSION['user_id']);
        if ($order === null) {
            $this->response->redirect('/checkout?status=vnpay-invalid');
            return;
        }

        if ($this->isVnpayPaymentSuccessful($responseCode, $transactionStatus)) {
            Order::markPaymentStatus($orderId, 'bank', 'paid');
            Order::adminUpdateStatus($orderId, 'approved', 0);
            $this->response->redirect('/orders/history?status=payment-success');
            return;
        }

        Order::cancelFailedBankTransfer($orderId);
        $this->response->redirect('/orders/history?status=payment-failed');
    }

    public function vnpayIpn(): void
    {
        $vnpData = $this->collectVnpayParams();
        $secureHash = (string)($vnpData['vnp_SecureHash'] ?? '');

        if (!$this->isValidVnpaySignature($vnpData, $secureHash)) {
            $this->response->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ], 200);
            return;
        }

        $orderId = (int)($vnpData['vnp_TxnRef'] ?? 0);
        if ($orderId <= 0) {
            $this->response->json([
                'RspCode' => '01',
                'Message' => 'Order not found',
            ], 200);
            return;
        }

        $responseCode = trim((string)($vnpData['vnp_ResponseCode'] ?? ''));
        $transactionStatus = trim((string)($vnpData['vnp_TransactionStatus'] ?? ''));

        if ($this->isVnpayPaymentSuccessful($responseCode, $transactionStatus)) {
            Order::markPaymentStatus($orderId, 'bank', 'paid');
            Order::adminUpdateStatus($orderId, 'approved', 0);
            $this->response->json([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ], 200);
            return;
        }

        Order::cancelFailedBankTransfer($orderId);
        $this->response->json([
            'RspCode' => '00',
            'Message' => 'Confirm Success',
        ], 200);
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function parseSelectedProductIds($raw): array
    {
        $values = [];

        if (is_array($raw)) {
            $values = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $values = explode(',', $raw);
        }

        $ids = [];
        foreach ($values as $value) {
            $id = (int)$value;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return array_keys($ids);
    }

    private function filterCartBySelectedProducts(array $cart, array $selectedIds): array
    {
        if (empty($selectedIds)) {
            return $cart;
        }

        $selectedMap = array_fill_keys(array_map('strval', $selectedIds), true);
        $filtered = [];

        foreach ($cart as $key => $item) {
            $productId = (string)(int)($item['product_id'] ?? $key);
            if (isset($selectedMap[$productId])) {
                $filtered[$productId] = $item;
            }
        }

        return $filtered;
    }

    private function normalizeCartItems(array $cart): array
    {
        $items = [];
        foreach ($cart as $key => $item) {
            $price = (int)($item['price'] ?? 0);
            $qty = max(1, (int)($item['qty'] ?? 1));
            $items[] = [
                'product_id' => (int)($item['product_id'] ?? $key),
                'name' => (string)($item['name'] ?? 'Sản phẩm'),
                'slug' => (string)($item['slug'] ?? ''),
                'image' => (string)($item['image'] ?? ''),
                'price' => $price,
                'quantity' => $qty,
                'line_total' => $price * $qty,
            ];
        }

        return $items;
    }

    private function calculateSummary(array $items): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (int)($item['line_total'] ?? 0);
        }

        $shippingFee = 0;

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $subtotal + $shippingFee,
        ];
    }

    private function composeFullAddress(array $profile): string
    {
        $parts = array_filter([
            (string)($profile['address_line'] ?? ''),
            (string)($profile['ward'] ?? ''),
            (string)($profile['district'] ?? ''),
            (string)($profile['city'] ?? ''),
        ], static fn($v) => trim($v) !== '');

        return implode(', ', $parts);
    }

    private function isValidCustomerInfo(array $info): bool
    {
        foreach (['full_name', 'phone', 'address_line', 'district', 'city'] as $required) {
            if (($info[$required] ?? '') === '') {
                return false;
            }
        }

        $digits = preg_replace('/\D+/', '', (string)$info['phone']);
        return strlen((string)$digits) >= 9;
    }

    private function sumCheckoutTotal(array $checkoutCart): int
    {
        $total = 0;
        foreach ($checkoutCart as $item) {
            $price = max(0, (int)($item['price'] ?? 0));
            $qty = max(1, (int)($item['qty'] ?? 1));
            $total += $price * $qty;
        }

        return $total;
    }

    private function collectVnpayParams(): array
    {
        $data = [];
        foreach ($_GET as $key => $value) {
            if (str_starts_with((string)$key, 'vnp_')) {
                $data[(string)$key] = (string)$value;
            }
        }

        return $data;
    }

    private function isValidVnpaySignature(array $vnpData, string $secureHash): bool
    {
        $hashSecret = trim((string)getenv('VNPAY_HASH_SECRET'));
        if ($hashSecret === '') {
            $hashSecret = 'HFGWYNU2BDRGW181HW753G1ZOJEZ6L9R';
        }

        if ($secureHash === '' || $hashSecret === '') {
            return false;
        }

        unset($vnpData['vnp_SecureHash'], $vnpData['vnp_SecureHashType']);
        $hashData = $this->buildVnpayHashData($vnpData);
        $calculated = hash_hmac('sha512', $hashData, $hashSecret);

        return hash_equals(strtolower($calculated), strtolower($secureHash));
    }

    private function buildVnpayPaymentUrl(int $orderId, int $amount, string $bankCode): ?string
    {
        $tmnCode = trim((string)getenv('VNPAY_TMN_CODE'));
        if ($tmnCode === '') {
            $tmnCode = 'RFEQATGL';
        }

        $hashSecret = trim((string)getenv('VNPAY_HASH_SECRET'));
        if ($hashSecret === '') {
            $hashSecret = 'HFGWYNU2BDRGW181HW753G1ZOJEZ6L9R';
        }

        $vnpUrl = trim((string)getenv('VNPAY_URL'));
        if ($vnpUrl === '') {
            $vnpUrl = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        }

        if ($tmnCode === '' || $hashSecret === '' || $vnpUrl === '') {
            return null;
        }

        $baseUrl = $this->resolveBaseUrl();
        $returnUrl = $baseUrl . '/checkout/vnpay-return';

        $payload = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => max(0, $amount) * 100,
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang #' . $orderId,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => (string)$orderId,
            'vnp_BankCode' => $bankCode,
        ];

        $hashData = $this->buildVnpayHashData($payload);
        $payload['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $hashSecret);

        return $vnpUrl . '?' . http_build_query($payload, '', '&', PHP_QUERY_RFC1738);
    }

    private function buildVnpayHashData(array $payload): string
    {
        ksort($payload);
        $segments = [];

        foreach ($payload as $key => $value) {
            $segments[] = urlencode((string)$key) . '=' . urlencode((string)$value);
        }

        return implode('&', $segments);
    }

    private function isVnpayPaymentSuccessful(string $responseCode, string $transactionStatus): bool
    {
        # Mark paid only when both fields confirm success.
        return $responseCode === '00' && $transactionStatus === '00';
    }

    private function resolveBaseUrl(): string
    {
        $appUrl = trim((string)getenv('APP_URL'));
        if ($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }
}
