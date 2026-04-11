<?php
use App\Core\View;

$products = is_array($products ?? null) ? $products : [];
$categories = is_array($categories ?? null) ? $categories : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'per_page' => 12, 'total' => 0, 'total_pages' => 1];

$selectedCategory = trim((string)($filters['category'] ?? ''));
$selectedKeyword = trim((string)($filters['keyword'] ?? ''));
$selectedSort = trim((string)($filters['sort'] ?? 'newest'));
$selectedRange = trim((string)($filters['price_range'] ?? ''));

$currentPage = max(1, (int)($pagination['page'] ?? 1));
$totalPages = max(1, (int)($pagination['total_pages'] ?? 1));
$totalProducts = max(0, (int)($pagination['total'] ?? 0));
$isPurchaseLoggedIn = isset($_SESSION['user']) || isset($_SESSION['user_id']);

$buildPageUrl = static function (int $page) use ($selectedCategory, $selectedKeyword, $selectedSort, $selectedRange): string {
    $params = [
        'category' => $selectedCategory,
        'q' => $selectedKeyword,
        'sort' => $selectedSort,
        'price_range' => $selectedRange,
        'page' => $page,
    ];

    $params = array_filter($params, static fn($v) => $v !== '');
    $query = http_build_query($params);

    return '/products' . ($query !== '' ? ('?' . $query) : '');
};
?>

<style>
.products-page {
    background: transparent;
}

.filter-card,
.product-tech-card {
    border: 1px solid rgba(125, 211, 252, .3);
    border-radius: 18px;
    box-shadow: 0 18px 36px rgba(2, 6, 23, .34);
}

.filter-card {
    background: linear-gradient(160deg, rgba(15,23,42,.88), rgba(30,41,59,.74));
    backdrop-filter: blur(6px);
}

.products-filter-panel {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(125, 211, 252, .46);
    background: linear-gradient(155deg, rgba(96, 165, 250, .28), rgba(56, 189, 248, .22), rgba(224, 242, 254, .18));
    box-shadow: 0 16px 30px rgba(56, 189, 248, .16), inset 0 0 0 1px rgba(240, 249, 255, .2);
}

main.is-inner-page .products-page .products-filter-panel {
    border-color: rgba(125, 211, 252, .46) !important;
    background: linear-gradient(155deg, rgba(96, 165, 250, .28), rgba(56, 189, 248, .22), rgba(224, 242, 254, .18)) !important;
    background-color: rgba(56, 189, 248, .14) !important;
    box-shadow: 0 16px 30px rgba(56, 189, 248, .16), inset 0 0 0 1px rgba(240, 249, 255, .2) !important;
}

.products-filter-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(120deg, rgba(248, 250, 252, .2), transparent 44%, rgba(191, 219, 254, .16));
}

.products-filter-panel > * {
    position: relative;
    z-index: 1;
}

.products-filter-title {
    color: #ffffff !important;
    letter-spacing: .01em;
    text-shadow: 0 6px 14px rgba(8, 47, 73, .28);
}

.products-filter-panel .products-filter-label {
    color: #eff6ff !important;
    font-weight: 700;
}

.products-filter-panel .form-control,
.products-filter-panel .form-select {
    background: rgba(219, 234, 254, .12);
    color: #ffffff;
    border-color: rgba(147, 197, 253, .5);
}

.products-filter-panel .form-control::placeholder {
    color: rgba(239, 246, 255, .9);
}

.products-filter-panel .form-select option {
    color: #082f49;
    background: #eff6ff;
}

.products-filter-panel .form-control:focus,
.products-filter-panel .form-select:focus {
    background: rgba(224, 242, 254, .16);
    border-color: rgba(125, 211, 252, .88);
    box-shadow: 0 0 0 4px rgba(56, 189, 248, .2);
}

.products-filter-panel .filter-apply-btn {
    border: 0;
    color: #fff;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6, #0ea5e9);
    box-shadow: 0 12px 20px rgba(37, 99, 235, .26);
}

.products-filter-panel .filter-apply-btn:hover {
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #0284c7);
    transform: translateY(-1px);
}

.products-filter-panel .filter-reset-btn {
    border-color: rgba(147, 197, 253, .6);
    color: #dbeafe;
    background: rgba(30, 64, 175, .2);
}

.products-filter-panel .filter-reset-btn:hover {
    color: #f0f9ff;
    border-color: rgba(125, 211, 252, .85);
    background: rgba(14, 116, 144, .3);
}

.product-tech-card {
    overflow: hidden;
    background: linear-gradient(165deg, rgba(15,23,42,.84), rgba(30,41,59,.72));
    transition: transform .26s ease, box-shadow .26s ease, border-color .26s ease;
    display: flex;
    flex-direction: column;
}

.product-tech-card:hover {
    transform: translateY(-8px);
    border-color: rgba(14, 165, 233, .7);
    box-shadow: 0 26px 50px rgba(2, 6, 23, .56), 0 0 0 1px rgba(34, 211, 238, .22);
}

.product-thumb {
    height: 190px;
    flex: 0 0 190px;
    overflow: hidden;
    border-bottom: 1px solid rgba(148, 163, 184, .2);
    background: linear-gradient(140deg, rgba(15,23,42,.92), rgba(30,41,59,.76));
}

.product-thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .35s ease;
}

.product-tech-card:hover .product-thumb img {
    transform: scale(1.05);
}

.discount-pill {
    position: absolute;
    top: .85rem;
    left: .85rem;
    z-index: 4;
    border-radius: 999px;
    padding: .32rem .62rem;
    font-size: .72rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #ef4444, #f97316);
    box-shadow: 0 10px 20px rgba(220,38,38,.3);
}

.product-title {
    min-height: 44px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: #e2e8f0;
}

.product-quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem;
    margin-top: auto;
}

.product-actions {
    position: relative;
    z-index: 2;
    display: grid;
    gap: .5rem;
}

.product-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    width: 100%;
    border-radius: 10px;
    padding: .52rem .72rem;
    font-size: .88rem;
    font-weight: 700;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
}

.product-action-btn:hover {
    transform: translateY(-1px);
}

.product-action-btn.detail {
    border: 1px solid rgba(125, 211, 252, .35);
    color: #bae6fd;
    background: linear-gradient(145deg, rgba(14,116,144,.34), rgba(37,99,235,.24));
}

.product-action-btn.detail:hover {
    background: linear-gradient(145deg, rgba(14,165,233,.44), rgba(37,99,235,.3));
    color: #e0f2fe;
}

.product-action-btn.add {
    border: 1px solid transparent;
    color: #fff;
    background: linear-gradient(135deg, #1d4ed8, #06b6d4);
    box-shadow: 0 10px 22px rgba(29, 78, 216, .28);
}

.product-action-btn.add:hover {
    background: linear-gradient(135deg, #1e40af, #0891b2);
    color: #fff;
}

.product-action-btn.disabled {
    border: 1px solid rgba(100, 116, 139, .45);
    color: #94a3b8;
    background: rgba(51, 65, 85, .55);
    cursor: not-allowed;
}

.price-current {
    font-size: 1.22rem;
    font-weight: 800;
    color: #22d3ee;
}

.price-old {
    color: #94a3b8;
    text-decoration: line-through;
    font-size: .9rem;
}

.products-page h1,
.products-page h2,
.products-page h3,
.products-page .form-label,
.products-page .fw-semibold {
    color: #f1f5ff;
}

.products-page p,
.products-page .text-muted,
.products-page .small {
    color: #cfe2ff !important;
}

.products-page .form-control,
.products-page .form-select {
    background: rgba(15, 23, 42, .62);
    color: #e2e8f0;
    border-color: rgba(125, 211, 252, .26);
}

.products-page .form-control::placeholder {
    color: #94a3b8;
}

.products-page .form-control:focus,
.products-page .form-select:focus {
    background: rgba(15, 23, 42, .72);
    color: #f8fafc;
    border-color: rgba(34, 211, 238, .55);
    box-shadow: 0 0 0 4px rgba(34, 211, 238, .14);
}

.products-page .page-link {
    color: #cbd5e1;
    background: rgba(15, 23, 42, .7);
    border-color: rgba(125, 211, 252, .24);
}

.products-page .page-item.active .page-link {
    color: #fff;
    background: linear-gradient(135deg, #1d4ed8, #06b6d4);
    border-color: transparent;
}

.products-page .page-link:hover {
    color: #e0f2fe;
    background: rgba(30, 41, 59, .92);
}

.quick-cart-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 1080;
    color: #fff;
    font-size: .9rem;
    font-weight: 600;
    border-radius: 12px;
    padding: .62rem .9rem;
    background: linear-gradient(135deg, rgba(30, 64, 175, .96), rgba(14, 165, 233, .96));
    box-shadow: 0 10px 24px rgba(15, 23, 42, .2);
    opacity: 0;
    transform: translateY(10px);
    transition: opacity .22s ease, transform .22s ease;
}

.quick-cart-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.cart-fly-item {
    position: fixed;
    z-index: 1085;
    border-radius: 14px;
    object-fit: cover;
    pointer-events: none;
    transition: transform .8s cubic-bezier(.2, .7, .2, 1), opacity .8s ease;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .25);
}
.product-thumb {
    position: relative;
    background:
        repeating-linear-gradient(
            135deg,
            rgba(37,99,235,0.08) 0px,
            rgba(37,99,235,0.08) 2px,
            transparent 2px,
            transparent 12px
        ),
        linear-gradient(135deg, #eef4ff, #f8fbff);
}
.products-page .text-muted {
    color: #93c5fd !important;
    text-shadow: 0 0 6px rgba(21, 106, 202, 0.3);
}
</style>

<section class="products-page py-4 py-lg-5">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Sản phẩm</h1>
                <p class="text-white lead mb-4" style="color:rgba(255,255,255,.96) !important;">Khám phá phụ kiện công nghệ phù hợp với nhu cầu của bạn.</p>
            </div>
            <span class="badge text-bg-primary rounded-pill px-3 py-2">Tổng cộng: <?= number_format($totalProducts) ?> sản phẩm</span>
        </div>

        <div class="row g-4">
            <aside class="col-12 col-lg-3">
                <div class="filter-card products-filter-panel p-3 p-lg-4 position-sticky" style="top: 88px;">
                    <h2 class="h5 fw-bold mb-3 products-filter-title">Lọc sản phẩm</h2>

                    <form method="GET" action="/products" class="d-grid gap-3">
                        <div>
                            <label class="form-label fw-semibold products-filter-label">Tìm kiếm theo tên</label>
                            <input type="text" class="form-control" name="q" value="<?= View::e($selectedKeyword) ?>" placeholder="Nhập tên sản phẩm...">
                        </div>

                        <div>
                            <label class="form-label fw-semibold products-filter-label">Danh mục sản phẩm</label>
                            <select class="form-select" name="category">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <?php $slug = (string)($category['slug'] ?? ''); ?>
                                    <option value="<?= View::e($slug) ?>" <?= $selectedCategory === $slug ? 'selected' : '' ?>>
                                        <?= View::e((string)($category['name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label fw-semibold products-filter-label">Khoảng giá</label>
                            <select class="form-select" name="price_range">
                                <option value="" <?= $selectedRange === '' ? 'selected' : '' ?>>Tất cả mức giá</option>
                                <option value="0_1m" <?= $selectedRange === '0_1m' ? 'selected' : '' ?>>0 - 1 triệu</option>
                                <option value="1m_5m" <?= $selectedRange === '1m_5m' ? 'selected' : '' ?>>1 - 5 triệu</option>
                                <option value="5m_10m" <?= $selectedRange === '5m_10m' ? 'selected' : '' ?>>5 - 10 triệu</option>
                                <option value="10m_plus" <?= $selectedRange === '10m_plus' ? 'selected' : '' ?>>Trên 10 triệu</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label fw-semibold products-filter-label">Sắp xếp sản phẩm</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="price_asc" <?= $selectedSort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                                <option value="price_desc" <?= $selectedSort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                                <option value="best_selling" <?= $selectedSort === 'best_selling' ? 'selected' : '' ?>>Bán chạy</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn filter-apply-btn">Áp dụng bộ lọc</button>
                            <a href="/products" class="btn filter-reset-btn">Xóa bộ lọc</a>
                        </div>
                    </form>
                </div>
            </aside>

            <div class="col-12 col-lg-9">
                <?php if (empty($products)): ?>
                    <div class="filter-card p-5 text-center">
                        <h2 class="h5 fw-semibold mb-2">Không tìm thấy sản phẩm phù hợp</h2>
                        <p class="text-muted mb-0">Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3 g-md-4">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $id = (int)($product['id'] ?? 0);
                            $name = (string)($product['name'] ?? 'Sản phẩm');
                            $slug = trim((string)($product['slug'] ?? ''));
                            $image = trim((string)($product['image'] ?? ''));
                            $price = (int)($product['price'] ?? 0);
                            $originalPrice = (int)($product['original_price'] ?? $price);
                            $stockTotal = (int)($product['stock_total'] ?? 0);
                            $discountPercent = max(0, (int)($product['discount_percent'] ?? 0));
                            $link = $slug !== '' ? '/product/' . urlencode($slug) : '/products/' . $id;
                            if ($image === '') {
                                $image = '/images/placeholder-product.svg';
                            }
                            ?>
                            <div class="col-12 col-sm-6 col-xl-4">
                                <article class="product-tech-card h-100">
                                    <a href="<?= View::e($link) ?>" class="position-relative d-block product-thumb">
                                        <?php if ($stockTotal <= 0): ?>
                                            <span class="discount-pill" style="background: linear-gradient(135deg, #64748b, #475569);">Hết hàng</span>
                                        <?php elseif ($discountPercent > 0): ?>
                                            <span class="discount-pill">-<?= $discountPercent ?>%</span>
                                        <?php endif; ?>
                                        <img src="<?= View::e($image) ?>" alt="<?= View::e($name) ?>" <?= $stockTotal <= 0 ? 'style="opacity: 0.6;"' : '' ?>>
                                    </a>
                                    <div class="p-3 d-flex flex-column flex-grow-1">
                                        <h3 class="h6 fw-semibold product-title mb-2"><?= View::e($name) ?></h3>
                                        <div class="small text-muted mb-2">Số lượng còn: <?= max(0, $stockTotal) ?></div>
                                        <?php if ($discountPercent > 0 && $originalPrice > $price): ?>
                                            <div class="price-current mb-1"><?= number_format($price) ?>₫</div>
                                            <div class="price-old mb-3"><?= number_format($originalPrice) ?>₫</div>
                                        <?php else: ?>
                                            <div class="price-current mb-3"><?= number_format($price) ?>₫</div>
                                        <?php endif; ?>
                                        <div class="product-quick-actions">
                                            <a href="<?= View::e($link) ?>" class="product-action-btn detail">
                                                Xem chi tiết
                                            </a>
                                            <?php if ($stockTotal > 0): ?>
                                                <form method="POST" action="/cart/add" class="d-grid" data-need-login="<?= $isPurchaseLoggedIn ? '0' : '1' ?>">
                                                    <input type="hidden" name="product_id" value="<?= $id ?>">
                                                    <input type="hidden" name="qty" value="1">
                                                    <button type="submit" class="product-action-btn add">
                                                        Thêm vào giỏ
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="product-action-btn disabled" disabled>Hết hàng</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4 mt-lg-5" aria-label="Phân trang sản phẩm">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= View::e($buildPageUrl(max(1, $currentPage - 1))) ?>">Trước</a>
                                </li>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= View::e($buildPageUrl($i)) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= View::e($buildPageUrl(min($totalPages, $currentPage + 1))) ?>">Sau</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const getVisibleElement = (selectors) => {
        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (!element) {
                continue;
            }

            const rect = element.getBoundingClientRect();
            const styles = window.getComputedStyle(element);
            if (rect.width > 0 && rect.height > 0 && styles.display !== 'none' && styles.visibility !== 'hidden') {
                return element;
            }
        }

        return document.querySelector(selectors[0]) || null;
    };

    const getCartButton = () => getVisibleElement(['#headerCartButton-desktop', '#headerCartButton']);
    const forms = document.querySelectorAll('form[action="/cart/add"]');

    const showQuickToast = (message, isError = false) => {
        const toast = document.createElement('div');
        toast.className = 'quick-cart-toast';
        if (isError) {
            toast.style.background = 'linear-gradient(135deg, rgba(185, 28, 28, .96), rgba(220, 38, 38, .96))';
        }
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 260);
        }, 1400);
    };

    const updateCartCount = (count) => {
        const safeCount = Math.max(0, Number(count) || 0);
        const badges = document.querySelectorAll('#headerCartCount, #headerCartCount-desktop');
        badges.forEach((badge) => {
            badge.textContent = String(safeCount);
            badge.classList.toggle('d-none', safeCount <= 0);
        });
    };

    const flyToCart = (form) => {
        const cartButton = getCartButton();
        if (!cartButton) {
            return;
        }

        const productCard = form.closest('.product-tech-card');
        const sourceImage = productCard ? productCard.querySelector('.product-thumb img') : null;
        const cartRect = cartButton.getBoundingClientRect();

        let flyNode;
        if (sourceImage) {
            const startRect = sourceImage.getBoundingClientRect();
            flyNode = sourceImage.cloneNode(true);
            flyNode.className = 'cart-fly-item';
            flyNode.style.left = `${startRect.left}px`;
            flyNode.style.top = `${startRect.top}px`;
            flyNode.style.width = `${startRect.width}px`;
            flyNode.style.height = `${startRect.height}px`;
        } else {
            const btnRect = form.getBoundingClientRect();
            flyNode = document.createElement('div');
            flyNode.className = 'cart-fly-item';
            flyNode.style.left = `${btnRect.left + btnRect.width / 2 - 18}px`;
            flyNode.style.top = `${btnRect.top + btnRect.height / 2 - 18}px`;
            flyNode.style.width = '36px';
            flyNode.style.height = '36px';
            flyNode.style.borderRadius = '999px';
            flyNode.style.background = 'linear-gradient(135deg, #1d4ed8, #0ea5e9)';
        }

        document.body.appendChild(flyNode);

        requestAnimationFrame(() => {
            const flyRect = flyNode.getBoundingClientRect();
            const targetX = cartRect.left + cartRect.width / 2 - (flyRect.left + flyRect.width / 2);
            const targetY = cartRect.top + cartRect.height / 2 - (flyRect.top + flyRect.height / 2);
            flyNode.style.transform = `translate(${targetX}px, ${targetY}px) scale(.14)`;
            flyNode.style.opacity = '.16';
        });

        setTimeout(() => {
            flyNode.remove();
            cartButton.classList.add('shadow');
            setTimeout(() => cartButton.classList.remove('shadow'), 220);
        }, 820);
    };

    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (form.dataset.needLogin === '1') {
                window.location.href = '/login?status=buy-login-required&next=' + encodeURIComponent(window.location.pathname);
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            try {
                const formData = new FormData(form);
                formData.append('ajax', '1');

                const response = await fetch('/cart/add', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json().catch(() => null);
                if (!response.ok || !data || data.success !== true) {
                    if (data && data.requiresLogin && data.loginUrl) {
                        window.location.href = data.loginUrl;
                        return;
                    }
                    showQuickToast((data && data.message) ? data.message : 'Không thể thêm vào giỏ hàng.', true);
                    return;
                }

                updateCartCount(data.cartCount);
                flyToCart(form);
                showQuickToast(data.message || 'Đã thêm vào giỏ hàng.');
            } catch (_) {
                showQuickToast('Kết nối thất bại, vui lòng thử lại.', true);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    });
})();
</script>
