<?php
require dirname(__DIR__) . '/vendor/autoload.php';
ini_set('default_charset', 'UTF-8');

// Lightweight .env loader for local/runtime secrets without extra dependencies.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
	$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || str_starts_with($line, '#')) {
			continue;
		}

		$parts = explode('=', $line, 2);
		if (count($parts) !== 2) {
			continue;
		}

		$key = trim($parts[0]);
		$value = trim($parts[1]);
		if ($key === '') {
			continue;
		}

		if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
			$value = substr($value, 1, -1);
		}

		putenv($key . '=' . $value);
		$_ENV[$key] = $value;
		$_SERVER[$key] = $value;
	}
}

use App\Core\Security\SecureSession;
use App\Core\Security\SecurityHeaders;

use App\Core\Request;
use App\Core\Router;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\LoginRateLimitMiddleware;

SecureSession::start();
SecurityHeaders::apply();

$request = new Request();
$router = new Router($request);

// Public routes
$router->get('/', 'HomeController@index');
$router->get('/about', 'AboutController@index');
$router->get('/contact', 'ContactController@index');
$router->post('/contact', 'ContactController@store');
$router->post('/newsletter/subscribe', 'NewsletterController@subscribe');
$router->post('/vouchers/claim', 'VoucherController@claim');
$router->get('/login', 'AuthController@showLogin');
$route = $router->post('/login', 'AuthController@login');
$route->middleware([LoginRateLimitMiddleware::class]);
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password/send', 'AuthController@sendForgotPasswordOtp');
$router->get('/forgot-password/verify', 'AuthController@showResetPassword');
$router->post('/forgot-password/verify', 'AuthController@resetPasswordWithOtp');
$router->post('/forgot-password/verify-otp', 'AuthController@verifyOtpOnly');
$router->get('/auth/google', 'AuthController@redirectToGoogle');
$router->get('/auth/google/callback', 'AuthController@handleGoogleCallback');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/account', 'AccountController@profile');
$router->get('/account/edit', 'AccountController@editProfile');
$router->post('/account/update', 'AccountController@updateProfile');
$router->post('/account/avatar', 'AccountController@updateAvatar');
$router->get('/orders/history', 'AccountController@history');
$router->post('/orders/cancel', 'AccountController@cancelOrder');
$router->get('/reviews/history', 'AccountController@reviewHistory');
$router->post('/orders/place', 'OrderController@place');
$router->post('/reviews', 'ReviewController@store');
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout/place', 'CheckoutController@place');
$router->get('/checkout/success', 'CheckoutController@success');
$router->get('/checkout/vnpay-return', 'CheckoutController@vnpayReturn');
$router->get('/checkout/vnpay-ipn', 'CheckoutController@vnpayIpn');
$router->get('/products', 'ProductController@index');
$router->get('/product/{slug}', 'ProductController@showBySlug');
$router->get('/products/{id}', 'ProductController@show');
$router->get('/chatbox/reply', 'ChatboxController@reply');
$router->get('/chatbox/product-detail', 'ChatboxController@productDetail');
$router->get('/blog/{slug}', 'BlogController@show');
$router->get('/cart', 'CartController@index');
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');
$router->post('/cart/clear', 'CartController@clear');
$router->get('/go', 'HomeController@go');

// Admin routes (protected)
$route = $router->get('/admin', 'AdminController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/products', 'AdminProductController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/products/create', 'AdminProductController@create');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/products', 'AdminProductController@store');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/products/{id}/edit', 'AdminProductController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/products/{id}', 'AdminProductController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/products/{id}/delete', 'AdminProductController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/product-discounts', 'AdminProductDiscountController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/product-discounts', 'AdminProductDiscountController@store');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/product-discounts/{id}/toggle', 'AdminProductDiscountController@toggle');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/product-discounts/{id}/delete', 'AdminProductDiscountController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/categories', 'AdminCategoryController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/categories/create', 'AdminCategoryController@create');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/categories', 'AdminCategoryController@store');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/categories/{id}/edit', 'AdminCategoryController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/categories/{id}', 'AdminCategoryController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/categories/{id}/delete', 'AdminCategoryController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/vouchers', 'AdminVoucherController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/vouchers/create', 'AdminVoucherController@create');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/vouchers', 'AdminVoucherController@store');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/vouchers/{id}/edit', 'AdminVoucherController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/vouchers/{id}', 'AdminVoucherController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/vouchers/{id}/toggle', 'AdminVoucherController@toggle');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/vouchers/{id}/delete', 'AdminVoucherController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/users', 'AdminUserController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/users/{id}', 'AdminUserController@show');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/users/{id}/edit', 'AdminUserController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/users/{id}', 'AdminUserController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/users/{id}/toggle', 'AdminUserController@toggle');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/users/{id}/delete', 'AdminUserController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/orders', 'AdminOrderController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/orders/{id}', 'AdminOrderController@show');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/orders/{id}/status', 'AdminOrderController@updateStatus');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/orders/{id}/cancel', 'AdminOrderController@cancel');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/inventory', 'AdminInventoryController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/inventory/import/{product_id}', 'AdminInventoryController@import');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/inventory/import/{product_id}', 'AdminInventoryController@storeImport');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/inventory/logs', 'AdminInventoryController@logs');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/reviews', 'AdminReviewController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/reviews/{id}', 'AdminReviewController@show');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/reviews/{id}/status', 'AdminReviewController@updateStatus');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/reviews/{id}/delete', 'AdminReviewController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/banners', 'AdminBannerController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/banners/create', 'AdminBannerController@create');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/banners', 'AdminBannerController@store');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/banners/{id}/edit', 'AdminBannerController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/banners/{id}/update', 'AdminBannerController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/banners/{id}/toggle', 'AdminBannerController@toggle');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/banners/{id}/delete', 'AdminBannerController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/posts', 'AdminPostController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/posts/create', 'AdminPostController@create');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/posts', 'AdminPostController@store');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/posts/{id}/edit', 'AdminPostController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/posts/{id}', 'AdminPostController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/posts/{id}/toggle', 'AdminPostController@toggle');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/posts/{id}/delete', 'AdminPostController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/contacts', 'AdminContactController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/contacts/{id}/handled', 'AdminContactController@handled');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/contacts/{id}/delete', 'AdminContactController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/newsletters', 'AdminNewsletterController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/newsletters/{id}/delete', 'AdminNewsletterController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/roles', 'AdminRoleController@index');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/roles', 'AdminRoleController@create');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/roles/{id}/edit', 'AdminRoleController@edit');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/roles/{id}', 'AdminRoleController@update');
$route->middleware([AdminMiddleware::class]);

$route = $router->post('/admin/roles/{id}/delete', 'AdminRoleController@destroy');
$route->middleware([AdminMiddleware::class]);

$route = $router->get('/admin/reports/export', 'ReportController@exportExcel');
$route->middleware([AdminMiddleware::class]);

$router->dispatch();
