<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Review;

class ProductController extends Controller
{
    public function index(): void
    {
        $category = trim((string)$this->request->input('category', ''));
        $keyword = trim((string)$this->request->input('q', ''));
        $sort = trim((string)$this->request->input('sort', 'newest'));
        $page = max(1, (int)$this->request->input('page', 1));
        $perPage = 12;

        $minPriceRaw = $this->request->input('min_price', '');
        $maxPriceRaw = $this->request->input('max_price', '');
        $priceRange = trim((string)$this->request->input('price_range', ''));

        if ($priceRange !== '') {
            [$minByRange, $maxByRange] = $this->resolvePriceRange($priceRange);
            if ($minPriceRaw === '') {
                $minPriceRaw = $minByRange !== null ? (string)$minByRange : '';
            }
            if ($maxPriceRaw === '') {
                $maxPriceRaw = $maxByRange !== null ? (string)$maxByRange : '';
            }
        }

        $minPrice = ($minPriceRaw !== '' && is_numeric((string)$minPriceRaw)) ? max(0, (int)$minPriceRaw) : null;
        $maxPrice = ($maxPriceRaw !== '' && is_numeric((string)$maxPriceRaw)) ? max(0, (int)$maxPriceRaw) : null;

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $filters = [
            'category' => $category,
            'keyword' => $keyword,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort' => in_array($sort, ['newest', 'price_asc', 'price_desc', 'best_selling'], true) ? $sort : 'newest',
            'price_range' => $priceRange,
        ];

        $totalProducts = Product::countForCatalog($filters);
        $totalPages = max(1, (int)ceil($totalProducts / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $products = Product::listForCatalog($filters, $page, $perPage);
        $categories = Product::categoriesForCatalog();

        $this->view('products/index', [
            'title' => 'Sản phẩm',
            'products' => $products,
            'filters' => $filters,
            'categories' => $categories,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalProducts,
                'total_pages' => $totalPages,
            ],
        ], 'layouts/main');
    }

    private function resolvePriceRange(string $range): array
    {
        return match ($range) {
            '0_1m' => [0, 1000000],
            '1m_5m' => [1000000, 5000000],
            '5m_10m' => [5000000, 10000000],
            '10m_plus' => [10000000, null],
            default => [null, null],
        };
    }

    public function show(string $id): void
    {
        $productId = (int)$id;
        $product = Product::findWithVariants($productId);
        if (!$product) {
            $this->response->send('Sản phẩm không tồn tại', 404);
            return;
        }
        
        // Lấy sản phẩm liên quan cùng danh mục
        $relatedProducts = Product::relatedByCategory(
            $productId,
            (int)($product['category_id'] ?? 0), 
            8
        );

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userReview = null;
        $canReview = false;

        if ($userId > 0) {
            $userReview = Review::findByUserAndProduct($userId, $productId);
            $canReview = $userReview === null && Review::canUserReviewProduct($userId, $productId);
        }
        
        $this->view('product/show', [
            'title' => 'Chi tiết sản phẩm',
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'reviews' => Review::visibleByProduct($productId, 10),
            'reviewSummary' => Review::visibleAverageByProduct($productId),
            'reviewStatus' => (string)$this->request->input('review_status', ''),
            'canReview' => $canReview,
            'userReview' => $userReview,
            'isReviewLoggedIn' => $userId > 0,
        ], 'layouts/main');
    }

    public function showBySlug(string $slug): void
    {
        $productId = Product::findIdBySlug($slug);
        if (!$productId) {
            $this->response->send('Sản phẩm không tồn tại', 404);
            return;
        }

        $this->show((string)$productId);
    }

    
}
