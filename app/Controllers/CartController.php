<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\User;

class CartController extends Controller
{
    public function index(): void
    {
        $this->ensureSession();

        $items = $this->syncCartWithLatestInventory($_SESSION['cart'] ?? []);
        $_SESSION['cart'] = $items;
        $totalQty = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $qty = (int)($item['qty'] ?? 0);
            $price = (int)($item['price'] ?? 0);
            $totalQty += $qty;
            $totalAmount += $qty * $price;
        }

        $orderForm = $_SESSION['order_form'] ?? null;
        if (!is_array($orderForm) && isset($_SESSION['user_id'])) {
            try {
                $orderForm = User::getCustomerProfileByUserId((int)$_SESSION['user_id']);
                $_SESSION['order_form'] = $orderForm;
            } catch (\Throwable $e) {
                $orderForm = [
                    'full_name' => '',
                    'phone' => '',
                    'address_line' => '',
                    'ward' => '',
                    'district' => '',
                    'city' => '',
                ];
            }
        }

        $this->view('cart/index', [
            'title' => 'Giỏ hàng',
            'items' => $items,
            'totalQty' => $totalQty,
            'totalAmount' => $totalAmount,
            'status' => (string)$this->request->input('status', ''),
            'orderForm' => $orderForm,
        ]);
    }

    public function add(): void
    {
        $this->ensureSession();

        $isAjax = $this->isAjaxRequest();

        if (!$this->isPurchaseAuthenticated()) {
            $next = $this->resolveNextPath();
            if ($isAjax) {
                $this->response->json([
                    'success' => false,
                    'requiresLogin' => true,
                    'message' => 'Bạn cần đăng nhập để mua hàng.',
                    'loginUrl' => '/login?status=buy-login-required&next=' . rawurlencode($next),
                    'cartCount' => $this->getCartCount(),
                ], 401);
                return;
            }
            $this->response->redirect('/login?status=buy-login-required&next=' . rawurlencode($next));
            return;
        }

        $productId = (int)$this->request->input('product_id', 0);
        $qty = max(1, (int)$this->request->input('qty', 1));
        $buyNow = (int)$this->request->input('buy_now', 0) === 1;

        $product = Product::findForCart($productId);
        if (!$product) {
            if ($isAjax) {
                $this->response->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại.',
                    'cartCount' => $this->getCartCount(),
                ], 404);
                return;
            }
            $this->response->redirect('/cart?status=not-found');
            return;
        }

        $cart = $_SESSION['cart'] ?? [];
        $key = (string)$productId;

        $currentQty = (int)($cart[$key]['qty'] ?? 0);
        $maxStock = max(0, (int)$product['stock_total']);
        $nextQty = $currentQty + $qty;

        if ($maxStock > 0 && $nextQty > $maxStock) {
            $nextQty = $maxStock;
        }

        if ($nextQty <= 0) {
            if ($isAjax) {
                $this->response->json([
                    'success' => false,
                    'message' => 'Sản phẩm đã hết hàng.',
                    'cartCount' => $this->getCartCount(),
                ], 409);
                return;
            }
            $this->response->redirect('/cart?status=out-of-stock');
            return;
        }

        $cart[$key] = [
            'product_id' => (int)$product['id'],
            'name' => (string)$product['name'],
            'slug' => (string)($product['slug'] ?? ''),
            'image' => trim((string)($product['image'] ?? '')),
            'price' => (int)$product['price'],
            'original_price' => (int)($product['original_price'] ?? $product['price']),
            'discount_percent' => (int)($product['discount_percent'] ?? 0),
            'qty' => $nextQty,
            'stock_total' => $maxStock,
        ];

        $_SESSION['cart'] = $cart;

        if ($isAjax) {
            $this->response->json([
                'success' => true,
                'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
                'cartCount' => $this->getCartCount(),
                'productId' => (int)$product['id'],
                'qty' => $nextQty,
            ]);
            return;
        }

        if ($buyNow) {
            $this->response->redirect('/cart?status=buy-now');
            return;
        }

        $this->response->redirect('/cart?status=added');
    }

    public function update(): void
    {
        $this->ensureSession();

        $productId = (string)(int)$this->request->input('product_id', 0);
        $qty = (int)$this->request->input('qty', 1);

        $cart = $_SESSION['cart'] ?? [];
        if (!isset($cart[$productId])) {
            $this->response->redirect('/cart');
            return;
        }

        if ($qty <= 0) {
            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;
            $this->response->redirect('/cart?status=removed');
            return;
        }

        $freshProduct = Product::findForCart((int)$productId);
        $maxStock = max(0, (int)($freshProduct['stock_total'] ?? ($cart[$productId]['stock_total'] ?? 0)));
        if ($freshProduct) {
            $cart[$productId]['price'] = (int)$freshProduct['price'];
            $cart[$productId]['original_price'] = (int)($freshProduct['original_price'] ?? $freshProduct['price']);
            $cart[$productId]['discount_percent'] = (int)($freshProduct['discount_percent'] ?? 0);
            $cart[$productId]['stock_total'] = $maxStock;
            $cart[$productId]['image'] = trim((string)($freshProduct['image'] ?? ($cart[$productId]['image'] ?? '')));
        }

        if ($maxStock > 0 && $qty > $maxStock) {
            $qty = $maxStock;
        }

        $cart[$productId]['qty'] = $qty;
        $_SESSION['cart'] = $cart;
        $this->response->redirect('/cart?status=updated');
    }

    public function remove(): void
    {
        $this->ensureSession();

        $productId = (string)(int)$this->request->input('product_id', 0);
        $cart = $_SESSION['cart'] ?? [];

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;
        }

        $this->response->redirect('/cart?status=removed');
    }

    public function clear(): void
    {
        $this->ensureSession();
        unset($_SESSION['cart']);
        $this->response->redirect('/cart?status=cleared');
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isPurchaseAuthenticated(): bool
    {
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            return true;
        }

        return isset($_SESSION['user_id']);
    }

    private function resolveNextPath(): string
    {
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $path = parse_url($referer, PHP_URL_PATH);
            if (is_string($path) && $path !== '' && str_starts_with($path, '/')) {
                return $path;
            }
        }

        return '/products';
    }

    private function isAjaxRequest(): bool
    {
        $headers = $this->request->headers();
        $normalized = [];

        foreach ($headers as $key => $value) {
            $normalized[strtolower((string)$key)] = (string)$value;
        }

        $requestedWith = strtolower(trim((string)($normalized['x-requested-with'] ?? '')));
        $accept = strtolower((string)($normalized['accept'] ?? ''));
        $ajaxInput = (int)$this->request->input('ajax', 0) === 1;

        return $ajaxInput
            || $requestedWith === 'xmlhttprequest'
            || str_contains($accept, 'application/json');
    }

    private function getCartCount(): int
    {
        $count = 0;
        foreach (($_SESSION['cart'] ?? []) as $item) {
            $count += (int)($item['qty'] ?? 0);
        }

        return $count;
    }

    private function syncCartWithLatestInventory(array $cart): array
    {
        if (empty($cart)) {
            return [];
        }

        $synced = [];
        foreach ($cart as $key => $item) {
            $productId = (int)($item['product_id'] ?? $key);
            if ($productId <= 0) {
                continue;
            }

            $fresh = Product::findForCart($productId);
            if (!$fresh) {
                continue;
            }

            $maxStock = max(0, (int)($fresh['stock_total'] ?? 0));
            $qty = max(1, (int)($item['qty'] ?? 1));
            if ($maxStock > 0 && $qty > $maxStock) {
                $qty = $maxStock;
            }

            if ($qty <= 0) {
                continue;
            }

            $synced[(string)$productId] = [
                'product_id' => $productId,
                'name' => (string)($fresh['name'] ?? ($item['name'] ?? 'Sản phẩm')),
                'slug' => (string)($fresh['slug'] ?? ($item['slug'] ?? '')),
                'image' => trim((string)($fresh['image'] ?? ($item['image'] ?? ''))),
                'price' => (int)($fresh['price'] ?? ($item['price'] ?? 0)),
                'original_price' => (int)($fresh['original_price'] ?? ($item['original_price'] ?? ($fresh['price'] ?? ($item['price'] ?? 0)))),
                'discount_percent' => (int)($fresh['discount_percent'] ?? ($item['discount_percent'] ?? 0)),
                'qty' => $qty,
                'stock_total' => $maxStock,
            ];
        }

        return $synced;
    }
}
