<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Voucher;

class HomeController extends Controller
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $categories = [];
        $flashSaleProducts = [];
        $bestSellingProducts = [];
        $newProducts = [];
        $homeVouchers = [];
        $homeSliderBanners = [];
        $promoBanners = [];
        $latestPosts = [];
        $visibleReviews = [];
        $claimedVoucherIds = [];
        $voucherStatus = trim((string)($_SESSION['voucher_claim_status'] ?? ''));
        unset($_SESSION['voucher_claim_status']);

        try {
            $homeSliderBanners = Banner::activeByPosition('home_slider', 6);
            $promoBanners = Banner::activeByPosition('promo_banner', 2);
            $categories = Category::homeFeatured(6);
            $flashSaleProducts = Product::homeFlashSale(8);
            $bestSellingProducts = Product::homeBestSelling(8);
            $newProducts = Product::homeNewestDetailed(8);
            $homeVouchers = Voucher::listPublicAvailable(6);
            $visibleReviews = Review::latestVisible(6);
            $latestPosts = Post::latestPublished(3);

            $userId = (int)($_SESSION['user_id'] ?? 0);
            if ($userId > 0) {
                $claimedVoucherIds = Voucher::allClaimedVoucherIdsByUser($userId);
            }
        } catch (\Throwable $e) {
            # Keep homepage available even when one of the data queries fails.
        }

        $this->view('home/index', [
            'title' => 'Trang chủ',
            'homeSliderBanners' => $homeSliderBanners,
            'promoBanners' => $promoBanners,
            'categories' => $categories,
            'flashSaleProducts' => $flashSaleProducts,
            'bestSellingProducts' => $bestSellingProducts,
            'newProducts' => $newProducts,
            'homeVouchers' => $homeVouchers,
            'claimedVoucherIds' => $claimedVoucherIds,
            'voucherStatus' => $voucherStatus,
            'latestPosts' => $latestPosts,
            'visibleReviews' => $visibleReviews,
        ]);
    }

    public function about(): void
    {
        $this->view('home/about', ['title' => 'Giới thiệu - TechGear']);
    }

    public function contact(): void
    {
        $status = (string)$this->request->input('status', '');
        $this->view('home/contact', [
            'title' => 'Liên hệ - TechGear',
            'status' => $status,
        ]);
    }

    public function ping(): void
    {
        $this->response->json([
            'ok' => true,
            'time' => date('c')
        ]);
    }
    public function go(): void
    {
        $this->response->redirect('/products');
    }
    public function fakeLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = 1;
        $this->response->redirect('/admin');
    }
    public function dbTest(): void
    {
        try {
            $pdo = DB::conn();
            $row = $pdo->query("SELECT 1 AS ok")->fetch();

            $this->response->json([
                'db' => true,
                'ok' => (int)($row['ok'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            $this->response->json([
                'db' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
