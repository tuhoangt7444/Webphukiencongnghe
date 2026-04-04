<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;

class OrderController extends Controller
{
    public function place(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required&next=/cart');
            return;
        }

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            $this->response->redirect('/cart?status=empty');
            return;
        }

        $selectedProductIds = $this->parseSelectedProductIds($this->request->input('selected_products', ''));
        $checkoutCart = $this->filterCartBySelectedProducts($cart, $selectedProductIds);

        if (empty($checkoutCart)) {
            $this->response->redirect('/cart?status=no-selection');
            return;
        }

        $savedProfile = User::getCustomerProfileByUserId((int)$_SESSION['user_id']);

        $customerInfo = [
            'full_name' => trim((string)$this->request->input('full_name', (string)($savedProfile['full_name'] ?? ''))),
            'phone' => trim((string)$this->request->input('phone', (string)($savedProfile['phone'] ?? ''))),
            'address_line' => trim((string)$this->request->input('address_line', (string)($savedProfile['address_line'] ?? ''))),
            'ward' => trim((string)$this->request->input('ward', (string)($savedProfile['ward'] ?? ''))),
            'district' => trim((string)$this->request->input('district', (string)($savedProfile['district'] ?? ''))),
            'city' => trim((string)$this->request->input('city', (string)($savedProfile['city'] ?? ''))),
            'full_address' => trim((string)$this->request->input('full_address', (string)($savedProfile['full_address'] ?? ''))),
        ];

        if ($customerInfo['full_address'] === '') {
            $customerInfo['full_address'] = $this->composeFullAddress($customerInfo);
        }

        $_SESSION['order_form'] = $customerInfo;

        if (!$this->isValidCustomerInfo($customerInfo)) {
            $this->response->redirect('/cart?status=profile-required');
            return;
        }

        $paymentMethod = trim((string)$this->request->input('payment_method', ''));
        if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
            $this->response->redirect('/cart?status=payment-invalid');
            return;
        }

        $paymentLabel = $paymentMethod === 'cod'
            ? 'Thanh toan khi nhan hang (COD)'
            : 'Chuyen khoan ngan hang';

        $customerNote = trim((string)$this->request->input('customer_note', ''));
        $customerNote = $customerNote !== ''
            ? ('[' . $paymentLabel . '] ' . $customerNote)
            : ('Phuong thuc thanh toan: ' . $paymentLabel);

        try {
            User::upsertCustomerProfile((int)$_SESSION['user_id'], $customerInfo);
            Order::createFromCart((int)$_SESSION['user_id'], $checkoutCart, $customerInfo, $customerNote);

            foreach ($selectedProductIds as $productId) {
                unset($cart[(string)$productId]);
            }

            if (empty($cart)) {
                unset($_SESSION['cart']);
            } else {
                $_SESSION['cart'] = $cart;
            }

            unset($_SESSION['order_form']);
            $this->response->redirect('/orders/history?status=placed');
        } catch (\Throwable $e) {
            $status = match ((string)$e->getMessage()) {
                'stock:insufficient' => 'out-of-stock',
                default => 'order-failed',
            };

            $this->response->redirect('/cart?status=' . $status);
        }
    }

    private function isValidCustomerInfo(array $info): bool
    {
        foreach (['full_name', 'phone', 'address_line', 'ward', 'district', 'city'] as $required) {
            if (($info[$required] ?? '') === '') {
                return false;
            }
        }

        $digits = preg_replace('/\D+/', '', (string)$info['phone']);
        return strlen((string)$digits) >= 9;
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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

    private function filterCartBySelectedProducts(array $cart, array $selectedProductIds): array
    {
        if (empty($selectedProductIds)) {
            return $cart;
        }

        $selectedMap = array_fill_keys(array_map('strval', $selectedProductIds), true);
        $filtered = [];

        foreach ($cart as $key => $item) {
            $productId = (string)(int)($item['product_id'] ?? $key);
            if (isset($selectedMap[$productId])) {
                $filtered[$productId] = $item;
            }
        }

        return $filtered;
    }
}
