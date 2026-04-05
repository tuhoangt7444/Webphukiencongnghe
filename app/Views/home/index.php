<?php use App\Core\View; ?>
<?php
$homeSliderBanners = $homeSliderBanners ?? [];
$categories = $categories ?? [];
$flashSaleProducts = $flashSaleProducts ?? [];
$bestSellingProducts = $bestSellingProducts ?? [];
$newProducts = $newProducts ?? [];
$homeVouchers = $homeVouchers ?? [];
$claimedVoucherIds = $claimedVoucherIds ?? [];
$voucherStatus = trim((string)($voucherStatus ?? ''));
$latestPosts = $latestPosts ?? [];
$visibleReviews = $visibleReviews ?? [];
$isPurchaseLoggedIn = isset($_SESSION['user']) || isset($_SESSION['user_id']);

$voucherToastMessage = '';
$voucherToastType = 'success';
if ($voucherStatus === 'claimed') {
    $voucherToastMessage = 'Bạn đã lấy phiếu giảm giá thành công.';
    $voucherToastType = 'success';
} elseif ($voucherStatus === 'already-claimed') {
    $voucherToastMessage = 'Bạn đã lấy phiếu này trước đó.';
    $voucherToastType = 'info';
} elseif ($voucherStatus === 'unavailable') {
    $voucherToastMessage = 'Phiếu giảm giá hiện không còn khả dụng.';
    $voucherToastType = 'warning';
} elseif ($voucherStatus === 'not-eligible') {
    $voucherToastMessage = 'Bạn chưa thuộc nhóm khách hàng áp dụng cho phiếu này.';
    $voucherToastType = 'warning';
} elseif ($voucherStatus === 'failed') {
    $voucherToastMessage = 'Không thể lấy phiếu giảm giá lúc này. Vui lòng thử lại.';
    $voucherToastType = 'danger';
}

$defaultSlides = [
    [
        'badge' => 'Hệ thống ưu đãi thông minh',
        'title' => 'Phụ kiện công nghệ chính hãng cho mọi cấu hình',
        'desc' => 'TechGear kết hợp giá tốt, bảo hành rõ ràng và giao hàng nhanh để bạn nâng cấp hệ thống dễ dàng.',
        'link' => '/products',
        'button' => 'Mua ngay',
        'class' => 'fallback-slide-one',
    ],
    [
        'badge' => 'Bộ sưu tập mới',
        'title' => 'Gaming setup hiện đại với hiệu năng vượt trội',
        'desc' => 'CPU, GPU, RAM, SSD và phụ kiện được cập nhật liên tục theo xu hướng công nghệ mới nhất.',
        'link' => '/products',
        'button' => 'Xem danh mục',
        'class' => 'fallback-slide-two',
    ],
    [
        'badge' => 'Tư vấn 1-1',
        'title' => 'Build cấu hình đúng nhu cầu, tối ưu ngân sách',
        'desc' => 'Đội ngũ tư vấn hỗ trợ chọn linh kiện tương thích, cân bằng giá thành và hiệu năng cho bạn.',
        'link' => '/contact',
        'button' => 'Nhận tư vấn',
        'class' => 'fallback-slide-three',
    ],
];

$defaultPosts = [
    [
        'title' => 'Hướng dẫn chọn bộ nguồn an toàn cho dàn máy',
        'excerpt' => 'Công suất, chuẩn 80 Plus và cách tính để tránh thiếu nguồn khi nâng cấp GPU.',
        'date' => 'Mới cập nhật',
    ],
    [
        'title' => '5 mẹo tối ưu nhiệt độ cho case gaming',
        'excerpt' => 'Sắp xếp luồng gió, vị trí fan và cách quản lý dây để hệ thống mát hơn.',
        'date' => 'Kiến thức hay',
    ],
    [
        'title' => 'RAM bao nhiêu là đủ cho học tập và đa nhiệm',
        'excerpt' => 'So sánh 8GB, 16GB, 32GB và cách chọn đúng nhu cầu để tiết kiệm chi phí.',
        'date' => 'Tin nổi bật',
    ],
];

$heroStats = [
    ['label' => 'Danh mục', 'value' => (string)count($categories)],
    ['label' => 'Flash sale', 'value' => (string)count($flashSaleProducts)],
    ['label' => 'Sản phẩm mới', 'value' => (string)count($newProducts)],
    ['label' => 'Đánh giá', 'value' => (string)count($visibleReviews)],
];

$renderProductCard = static function (array $p, bool $showSold = false) use ($isPurchaseLoggedIn): string {
    $id = (int)($p['id'] ?? 0);
    $slug = trim((string)($p['slug'] ?? ''));
    $name = (string)($p['name'] ?? 'Sản phẩm công nghệ');
    $price = (int)($p['price_from'] ?? 0);
    $basePrice = (int)($p['base_price_from'] ?? $price);
    $discountPercent = max(0, (int)($p['discount_percent'] ?? 0));
    $stock = (int)($p['stock_total'] ?? 0);
    $sold = (int)($p['sold_qty'] ?? 0);
    $avgRating = max(0, min(5, (float)($p['avg_rating'] ?? 0)));
    $reviewCount = (int)($p['review_count'] ?? 0);
    $image = trim((string)($p['image_url'] ?? ''));
    $detailUrl = $slug !== '' ? '/product/' . urlencode($slug) : '/products/' . $id;

    ob_start();
    ?>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="product-tech-card h-100 position-relative">
            <?php if ($discountPercent > 0): ?>
                <span class="discount-pill">-<?= $discountPercent ?>%</span>
            <?php endif; ?>

            <a href="<?= View::e($detailUrl) ?>" class="product-thumb d-block text-decoration-none">
                <?php if ($image !== ''): ?>
                    <img src="<?= View::e($image) ?>" alt="<?= View::e($name) ?>" class="w-100 h-100" style="object-fit: cover;">
                <?php else: ?>
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-primary bg-white">
                        <i class="fa-solid fa-microchip fs-2 mb-2"></i>
                        <span class="small text-muted">Chưa có ảnh</span>
                    </div>
                <?php endif; ?>
            </a>

            <div class="p-3 d-flex flex-column flex-grow-1">
                <h3 class="h6 fw-semibold mb-2 product-title"><?= View::e($name) ?></h3>

                <div class="d-flex align-items-end gap-2 mb-2 flex-wrap">
                    <div class="price-current"><?= number_format($price) ?>d</div>
                    <?php if ($basePrice > $price): ?>
                        <div class="price-old"><?= number_format($basePrice) ?>d</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between small mb-2">
                    <div class="rating-tech">
                        <?php
                        $stars = (int)round($avgRating > 0 ? $avgRating : 5);
                        for ($i = 1; $i <= 5; $i++):
                        ?>
                            <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted"><?= $reviewCount > 0 ? $reviewCount . ' đánh giá' : 'Chưa có đánh giá' ?></span>
                </div>

                <div class="small text-muted mb-3"><?= $stock > 0 ? 'Tồn kho: ' . $stock : 'Đặt trước - tư vấn nhanh' ?></div>
                <?php if ($showSold): ?>
                    <div class="small text-success mb-3">Đã bán: <?= $sold ?></div>
                <?php endif; ?>

                <div class="mt-auto d-grid gap-2 product-actions">
                    <form method="POST" action="/cart/add" data-need-login="<?= $isPurchaseLoggedIn ? '0' : '1' ?>" class="m-0">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        <input type="hidden" name="qty" value="1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Thêm vào giỏ hàng</button>
                    </form>
                    <a href="<?= View::e($detailUrl) ?>" class="btn btn-outline-primary btn-sm w-100">Xem chi tiết</a>
                </div>
            </div>
        </article>
    </div>
    <?php

    return (string)ob_get_clean();
};
?>

<style>@import url('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');</style>
<style>
:root {
    --tech-blue-900: #07122b;
    --tech-blue-800: #0b1e46;
    --tech-blue-700: #123574;
    --tech-blue-600: #1d4ed8;
    --tech-cyan-500: #06b6d4;
    --tech-cyan-400: #22d3ee;
    --tech-sky-300: #7dd3fc;
    --tech-surface: rgba(255, 255, 255, .9);
}

.tech-homepage {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 8% 2%, rgba(29, 78, 216, .28), transparent 25%),
        radial-gradient(circle at 92% 4%, rgba(6, 182, 212, .26), transparent 22%),
        linear-gradient(180deg, #eef5ff 0%, #deecff 35%, #edf5ff 100%);
}

.tech-homepage::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(34, 211, 238, .09) 1px, transparent 1px),
        linear-gradient(90deg, rgba(34, 211, 238, .09) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: linear-gradient(180deg, rgba(0,0,0,.9), rgba(0,0,0,.2));
}

.tech-homepage::after {
    content: '';
    position: absolute;
    inset: -10% -10% auto -10%;
    height: 72%;
    pointer-events: none;
    background:
        radial-gradient(ellipse at 12% 24%, rgba(34,211,238,.4), transparent 58%),
        radial-gradient(ellipse at 84% 18%, rgba(29,78,216,.34), transparent 54%),
        radial-gradient(ellipse at 54% 12%, rgba(14,165,233,.24), transparent 52%);
    filter: blur(16px);
    animation: auroraSweep 14s ease-in-out infinite alternate;
}

.tech-decor-grid,
.tech-decor-particles,
.tech-decor-scanline,
.tech-orbs {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.tech-decor-grid::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(135deg, rgba(29,78,216,.08) 0, rgba(29,78,216,.08) 1px, transparent 1px, transparent 24px);
    animation: gridShift 18s linear infinite;
    opacity: .5;
}

.tech-decor-scanline::before {
    content: '';
    position: absolute;
    inset: -120% 0 auto;
    height: 220px;
    background: linear-gradient(180deg, rgba(34,211,238,0), rgba(34,211,238,.24), rgba(34,211,238,0));
    animation: scanlineMove 9s linear infinite;
}

.tech-particle {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: radial-gradient(circle, #fff, rgba(125,211,252,.35));
    box-shadow: 0 0 0 8px rgba(56,189,248,.08), 0 0 24px rgba(6,182,212,.42);
    animation: particleRise 12s linear infinite;
}

.tech-particle:nth-child(1) { left: 7%; top: 30%; animation-delay: -1s; }
.tech-particle:nth-child(2) { left: 18%; top: 65%; animation-delay: -6s; }
.tech-particle:nth-child(3) { left: 34%; top: 36%; animation-delay: -3s; }
.tech-particle:nth-child(4) { left: 53%; top: 70%; animation-delay: -8s; }
.tech-particle:nth-child(5) { left: 67%; top: 42%; animation-delay: -4s; }
.tech-particle:nth-child(6) { left: 82%; top: 62%; animation-delay: -7s; }

.tech-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(20px);
    opacity: .6;
}

.tech-orb.one {
    width: 280px;
    height: 280px;
    top: 90px;
    left: -90px;
    background: radial-gradient(circle, rgba(29,78,216,.35), transparent 70%);
}

.tech-orb.two {
    width: 300px;
    height: 300px;
    top: 520px;
    right: -120px;
    background: radial-gradient(circle, rgba(6,182,212,.36), transparent 70%);
}

.tech-orb.three {
    width: 260px;
    height: 260px;
    bottom: 160px;
    left: 14%;
    background: radial-gradient(circle, rgba(14,165,233,.26), transparent 70%);
}

.gear-field {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.gear {
    position: absolute;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: rgba(191, 219, 254, .88);
    border: 1px solid rgba(147, 197, 253, .28);
    background: radial-gradient(circle, rgba(37,99,235,.22), rgba(7,18,43,.06));
    box-shadow: inset 0 0 22px rgba(34,211,238,.12), 0 0 24px rgba(34,211,238,.14);
    backdrop-filter: blur(2px);
}

.gear i {
    filter: drop-shadow(0 8px 12px rgba(2, 132, 199, .3));
}

.gear.g1 { width: 92px; height: 92px; left: 5%; top: 14%; animation: gearRotateCW 16s linear infinite; }
.gear.g2 { width: 70px; height: 70px; left: 17%; top: 24%; animation: gearRotateCCW 12s linear infinite; }
.gear.g3 { width: 110px; height: 110px; right: 7%; top: 20%; animation: gearRotateCCW 19s linear infinite; }
.gear.g4 { width: 80px; height: 80px; right: 15%; top: 34%; animation: gearRotateCW 15s linear infinite; }
.gear.g5 { width: 74px; height: 74px; left: 10%; bottom: 16%; animation: gearRotateCCW 13s linear infinite; }
.gear.g6 { width: 96px; height: 96px; right: 22%; bottom: 12%; animation: gearRotateCW 17s linear infinite; }

.voucher-toast-wrap {
    position: fixed;
    top: 86px;
    right: 16px;
    z-index: 1200;
    width: min(92vw, 420px);
}

.voucher-toast-item {
    border: 1px solid rgba(148, 163, 184, .35);
    border-radius: 12px;
    box-shadow: 0 16px 36px rgba(15, 23, 42, .18);
}

@media (max-width: 768px) {
    .voucher-toast-wrap {
        top: 78px;
        right: 10px;
        left: 10px;
        width: auto;
    }
}

.content-layer {
    position: relative;
    z-index: 1;
}

.hero-zone {
    margin-top: 1.2rem;
}

.hero-swiper {
    border-radius: 22px;
    border: 1px solid rgba(125,211,252,.45);
    overflow: hidden;
    box-shadow: 0 30px 90px rgba(7,18,43,.26), 0 0 0 1px rgba(56,189,248,.2);
}

.hero-slide {
    position: relative;
    min-height: 470px;
}

.hero-slide img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-slide-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(95deg, rgba(7,18,43,.88) 0%, rgba(7,18,43,.58) 42%, rgba(7,18,43,.25) 100%);
}

.hero-slide-content {
    position: relative;
    z-index: 2;
    min-height: 470px;
    display: flex;
    align-items: center;
    padding-right: 36px;
}

.hero-tech-layout {
    width: 100%;
    display: flex;
    align-items: center;
}

.hero-copy {
    padding-left: clamp(12px, 2.4vw, 30px);
    margin-left: 20px;
}

.tech-feature-strip {
    margin-top: 1.4rem;
    border-radius: 18px;
    border: 1px solid rgba(125,211,252,.36);
    background: linear-gradient(145deg, rgba(11,30,70,.86), rgba(29,78,216,.78));
    box-shadow: 0 18px 34px rgba(7,18,43,.26);
    padding: 1rem 1.1rem;
}

.feature-tile {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: #dbeafe;
    border-radius: 12px;
    padding: .6rem .7rem;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(191,219,254,.24);
}

.feature-tile i {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(34,211,238,.24);
    color: #7dd3fc;
}

.reveal-ready {
    opacity: 0;
    transform: translate3d(0, 22px, 0) scale(.985);
    transition: opacity .56s ease, transform .56s cubic-bezier(.22,.72,.2,1);
}

.reveal-ready.in-view {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    color: #dbeafe;
    border: 1px solid rgba(125,211,252,.5);
    border-radius: 999px;
    padding: .55rem .95rem;
    background: rgba(34,211,238,.14);
    box-shadow: 0 0 0 1px rgba(255,255,255,.14) inset;
    backdrop-filter: blur(8px);
    margin-bottom: .9rem;
}

.hero-title {
    color: #fff;
    font-weight: 800;
    letter-spacing: -.02em;
    text-shadow: 0 8px 28px rgba(0,0,0,.32);
}

.hero-desc {
    color: rgba(226, 232, 240, .95);
    max-width: 620px;
}

.hero-stats {
    margin-top: 12px;
    position: relative;
    z-index: 3;
}

.hero-stat-card {
    border-radius: 16px;
    border: 1px solid rgba(125,211,252,.34);
    background: rgba(255,255,255,.86);
    box-shadow: 0 18px 36px rgba(11, 30, 70, .14);
    padding: .95rem 1rem;
}

.hero-stat-card .v {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--tech-blue-700);
    line-height: 1;
}

.hero-stat-card .k {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: .05em;
}

.section-wrap {
    padding-top: 3.1rem;
}

.section-head {
    margin-bottom: 1.1rem;
}

.section-kicker {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    font-size: .76rem;
    font-weight: 700;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: var(--tech-blue-600);
    background: rgba(37,99,235,.1);
    border: 1px solid rgba(56,189,248,.34);
    border-radius: 999px;
    padding: .28rem .8rem;
    margin-bottom: .55rem;
}

.section-title {
    font-weight: 800;
    letter-spacing: -.02em;
    color: #0f172a;
    margin-bottom: .35rem;
}

.section-subtitle {
    color: #475569;
    max-width: 720px;
    margin-bottom: 0;
}

.neo-panel {
    border-radius: 20px;
    background: rgba(255,255,255,.58);
    border: 1px solid rgba(125,211,252,.42);
    box-shadow: 0 16px 34px rgba(11,30,70,.1);
    backdrop-filter: blur(4px);
    position: relative;
}

.neo-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    pointer-events: none;
    background: linear-gradient(120deg, rgba(56,189,248,.22), transparent 45%, rgba(37,99,235,.2));
    opacity: .55;
    z-index: 0;
}

.neo-panel > * {
    position: relative;
    z-index: 1;
}

.category-tech-card {
    border-radius: 16px;
    border: 1px solid rgba(125,211,252,.38);
    background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(241,245,249,.9));
    text-decoration: none;
    color: inherit;
    display: block;
    padding: 1rem .75rem;
    text-align: center;
    transition: transform .24s ease, box-shadow .24s ease, border-color .24s ease;
    height: 100%;
}

.category-tech-card:hover {
    transform: translateY(-7px);
    border-color: rgba(14,165,233,.7);
    box-shadow: 0 20px 36px rgba(11,30,70,.18);
}

.category-tech-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto .55rem;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #fff;
    background: linear-gradient(135deg, var(--tech-blue-600), var(--tech-cyan-500));
    box-shadow: 0 14px 24px rgba(29,78,216,.28);
}

.product-tech-card {
    border-radius: 18px;
    border: 1px solid rgba(125,211,252,.36);
    background: linear-gradient(180deg, rgba(255,255,255,.97), rgba(241,245,249,.94));
    box-shadow: 0 14px 28px rgba(11,30,70,.12);
    transition: transform .26s ease, box-shadow .26s ease, border-color .26s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.product-tech-card:hover {
    transform: translateY(-8px);
    border-color: rgba(14,165,233,.7);
    box-shadow: 0 26px 50px rgba(11,30,70,.2);
}

.product-thumb {
    height: 190px;
    flex: 0 0 190px;
    overflow: hidden;
    border-bottom: 1px solid rgba(148,163,184,.2);
    background: linear-gradient(140deg, #f8fafc, #eef6ff);
}

.product-thumb img {
    transition: transform .35s ease;
}

.product-tech-card:hover .product-thumb img {
    transform: scale(1.05);
}

.product-actions {
    position: relative;
    z-index: 2;
}

.cart-fly-item {
    position: fixed;
    z-index: 2000;
    pointer-events: none;
    border-radius: 12px;
    object-fit: cover;
    box-shadow: 0 12px 24px rgba(30, 64, 175, .28);
    transition: transform .78s cubic-bezier(.18,.7,.2,1), opacity .78s ease;
}

.quick-cart-toast {
    position: fixed;
    right: 18px;
    top: 92px;
    z-index: 2050;
    background: linear-gradient(135deg, rgba(30, 64, 175, .96), rgba(2, 132, 199, .96));
    color: #fff;
    padding: .58rem .9rem;
    border-radius: 11px;
    font-size: .84rem;
    font-weight: 600;
    box-shadow: 0 14px 30px rgba(2, 132, 199, .36);
    opacity: 0;
    transform: translateY(-8px);
    transition: opacity .24s ease, transform .24s ease;
}

.quick-cart-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.product-title {
    min-height: 44px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.price-current {
    font-size: 1.22rem;
    font-weight: 800;
    color: var(--tech-blue-700);
}

.price-old {
    color: #64748b;
    text-decoration: line-through;
    font-size: .9rem;
}

.rating-tech {
    color: #f59e0b;
    display: flex;
    gap: .12rem;
}

.discount-pill {
    position: absolute;
    top: .85rem;
    left: .85rem;
    z-index: 4;
    font-size: .72rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-radius: 999px;
    padding: .32rem .62rem;
    box-shadow: 0 10px 20px rgba(220,38,38,.3);
    animation: salePulse 2s ease-in-out infinite;
}

.news-tech-card,
.review-tech-card,
.info-tech-card {
    border-radius: 16px;
    border: 1px solid rgba(125,211,252,.36);
    background: linear-gradient(180deg, rgba(255,255,255,.95), rgba(241,245,249,.9));
    box-shadow: 0 14px 28px rgba(11,30,70,.1);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}

.news-tech-card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.news-tech-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}

.news-tech-card:hover,
.review-tech-card:hover,
.info-tech-card:hover {
    transform: translateY(-6px);
    border-color: rgba(14,165,233,.66);
    box-shadow: 0 24px 42px rgba(11,30,70,.18);
}

.blog-title {
    min-height: 48px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.voucher-tech-card {
    border-radius: 16px;
    border: 1px solid rgba(59, 130, 246, .28);
    background: linear-gradient(145deg, rgba(29, 78, 216, .98), rgba(6, 182, 212, .96));
    color: #fff;
    box-shadow: 0 14px 28px rgba(11, 30, 70, .18);
    transition: transform .22s ease, box-shadow .22s ease;
}

.voucher-tech-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 34px rgba(11, 30, 70, .22);
}

.voucher-tech-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    background: rgba(255, 255, 255, .16);
    border: 1px solid rgba(255, 255, 255, .28);
    border-radius: 999px;
    padding: .26rem .6rem;
}

.voucher-tech-title {
    font-size: 1.35rem;
    font-weight: 800;
    margin: .7rem 0 .2rem;
}

.voucher-tech-desc {
    opacity: .9;
    min-height: 46px;
}

.hero-swiper .swiper-button-prev,
.hero-swiper .swiper-button-next {
    width: 46px;
    height: 46px;
    border-radius: 999px;
    background: rgba(34,211,238,.22);
    border: 1px solid rgba(125,211,252,.45);
    color: #fff;
    backdrop-filter: blur(8px);
    top: auto;
    bottom: 16px;
    transform: none;
    z-index: 5;
}

.hero-swiper .swiper-button-prev {
    left: auto;
    right: 72px;
}

.hero-swiper .swiper-button-next {
    right: 18px;
}

.hero-swiper .swiper-button-prev::after,
.hero-swiper .swiper-button-next::after {
    font-size: .95rem;
    font-weight: 800;
}

.hero-swiper .swiper-pagination-bullet {
    width: 10px;
    height: 10px;
    opacity: 1;
    background: rgba(226,232,240,.6);
}

.hero-swiper .swiper-pagination-bullet-active {
    background: var(--tech-sky-300);
    box-shadow: 0 0 0 5px rgba(34,211,238,.22);
}

.hero-swiper .swiper-pagination {
    bottom: 24px !important;
    text-align: left;
    padding-left: 20px;
    width: auto;
}

/* Neo clean refresh: modern but less aggressive */
:root {
    --neo-radius: 12px;
}

.hero-swiper,
.hero-stat-card,
.neo-panel,
.category-tech-card,
.product-tech-card,
.voucher-tech-card,
.news-tech-card,
.review-tech-card,
.feature-tile,
.voucher-toast-item,
.quick-cart-toast {
    border-radius: var(--neo-radius) !important;
}

.hero-chip,
.section-kicker,
.voucher-tech-chip,
.discount-pill,
.hero-swiper .swiper-button-prev,
.hero-swiper .swiper-button-next,
.category-tech-icon,
.tech-homepage .btn,
.tech-homepage .form-control,
.tech-homepage .form-select {
    border-radius: 8px !important;
}

.hero-swiper .swiper-button-prev,
.hero-swiper .swiper-button-next {
    width: 44px;
    height: 44px;
    border: 1px solid rgba(186,230,253,.58);
    background: linear-gradient(145deg, rgba(14,165,233,.26), rgba(29,78,216,.24));
}

.hero-stat-card,
.feature-tile,
.category-tech-card,
.product-tech-card,
.voucher-tech-card,
.news-tech-card,
.review-tech-card {
    position: relative;
    overflow: hidden;
}

.hero-stat-card::before,
.feature-tile::before,
.category-tech-card::before,
.product-tech-card::before,
.voucher-tech-card::before,
.news-tech-card::before,
.review-tech-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(56,189,248,.14), transparent 36%, transparent 64%, rgba(59,130,246,.12));
    opacity: .72;
    pointer-events: none;
}

.hero-stat-card::after,
.feature-tile::after,
.category-tech-card::after,
.product-tech-card::after,
.voucher-tech-card::after,
.news-tech-card::after,
.review-tech-card::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, rgba(56,189,248,.96), rgba(59,130,246,.1));
    pointer-events: none;
}

.category-tech-card:hover,
.product-tech-card:hover,
.news-tech-card:hover,
.review-tech-card:hover,
.voucher-tech-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 26px 48px rgba(11,30,70,.2), 0 0 0 1px rgba(56,189,248,.24);
}

/* Creative futuristic override */
.tech-homepage {
    background:
    radial-gradient(80rem 60rem at 10% -20%, rgba(34,211,238,.22), transparent 60%),
    radial-gradient(70rem 55rem at 90% -30%, rgba(59,130,246,.28), transparent 62%),
    radial-gradient(64rem 48rem at 52% 118%, rgba(34,197,94,.2), transparent 64%),
        linear-gradient(180deg, #020617 0%, #0b1227 38%, #0a1b3f 100%) !important;
}

.tech-homepage::before {
    background-image:
        linear-gradient(rgba(56,189,248,.12) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56,189,248,.12) 1px, transparent 1px);
    background-size: 36px 36px;
    opacity: .45;
}

.content-layer {
    font-family: 'Inter', sans-serif;
}

.hero-swiper {
    border-radius: 26px !important;
    border: 1px solid rgba(56,189,248,.35);
    box-shadow: 0 34px 80px rgba(2,6,23,.6), inset 0 0 0 1px rgba(125,211,252,.18);
}

.hero-slide-overlay {
    background:
        linear-gradient(102deg, rgba(2,6,23,.92) 0%, rgba(7,18,43,.7) 42%, rgba(7,18,43,.26) 100%),
    radial-gradient(circle at 88% 18%, rgba(34,211,238,.24), transparent 44%),
    radial-gradient(circle at 18% 90%, rgba(34,197,94,.18), transparent 42%);
}

.hero-copy {
    margin-left: 20px;
    max-width: 720px;
    
}

.hero-chip {
    background: linear-gradient(145deg, rgba(8,47,73,.54), rgba(30,64,175,.35), rgba(21,128,61,.32));
    border-color: rgba(125,211,252,.55);
    color: #bae6fd;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-size: .74rem;
}

.hero-title {
    font-family: 'Sora', sans-serif;
    font-size: clamp(2rem, 4.4vw, 3.4rem);
    letter-spacing: -.03em;
    line-height: 1.03;
    color: #f8fbff;
    text-shadow: 0 16px 40px rgba(15,23,42,.72);
}

.hero-desc {
    color: rgba(191,219,254,.92);
    font-size: 1.03rem;
    max-width: 560px;
}

.hero-slide-content .btn-info {
    background: linear-gradient(145deg, #06b6d4 0%, #2563eb 52%, #22c55e 100%);
    border: 0;
    border-radius: 12px !important;
    box-shadow: 0 12px 28px rgba(37,99,235,.45);
    transition: transform .22s ease, box-shadow .22s ease;
}

.hero-slide-content .btn-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(6,182,212,.38), 0 0 0 1px rgba(34,197,94,.28);
}

.hero-stat-card {
    background: linear-gradient(145deg, rgba(12,24,52,.8), rgba(15,23,42,.68));
    border-color: rgba(56,189,248,.32);
    box-shadow: 0 16px 30px rgba(2,6,23,.45);
}

.hero-stat-card .v {
    color: #67e8f9;
}

.hero-stat-card .k {
    color: #cbd5e1;
}

.section-title {
    font-family: 'Sora', sans-serif;
    color: #e2e8f0;
}

.section-subtitle {
    color: #94a3b8;
}

.section-kicker {
    color: #67e8f9;
    border-color: rgba(103,232,249,.5);
    background: rgba(14,116,144,.25);
}

.neo-panel {
    background: linear-gradient(150deg, rgba(15,23,42,.68), rgba(30,41,59,.46));
    border-color: rgba(56,189,248,.25);
    box-shadow: 0 20px 40px rgba(2,6,23,.4);
}


.category-tech-card,
.product-tech-card,
.voucher-tech-card,
.news-tech-card,
.review-tech-card {
    background: linear-gradient(165deg, rgba(15,23,42,.84), rgba(30,41,59,.72));
    border-color: rgba(56,189,248,.2);
    color: #dbeafe;
    box-shadow: 0 16px 28px rgba(2,6,23,.38);
}

.category-tech-card:hover,
.product-tech-card:hover,
.voucher-tech-card:hover,
.news-tech-card:hover,
.review-tech-card:hover {
    border-color: rgba(34,211,238,.58);
    box-shadow: 0 26px 42px rgba(2,6,23,.56), 0 0 0 1px rgba(34,211,238,.25);
}

.category-tech-icon {
    background: linear-gradient(145deg, rgba(14,116,144,.92), rgba(37,99,235,.86), rgba(21,128,61,.8));
}

.product-thumb {
    background: linear-gradient(140deg, rgba(15,23,42,.9), rgba(30,41,59,.7));
    border-bottom-color: rgba(148,163,184,.16);
}

.price-current {
    color: #22d3ee;
}

.price-old,
.text-muted,
.small.text-muted,
.section-wrap .alert {
    color: #94a3b8 !important;
}

.news-tech-body p,
.review-tech-card p,
.voucher-tech-desc {
    color: #cbd5e1 !important;
}

.voucher-tech-card {
    background: linear-gradient(150deg, rgba(37,99,235,.9), rgba(8,145,178,.82), rgba(21,128,61,.76));
}

.tech-feature-strip {
    background: linear-gradient(145deg, rgba(12,24,52,.92), rgba(3,105,161,.62), rgba(21,128,61,.5));
    border-color: rgba(56,189,248,.35);
}

.feature-tile {
    background: linear-gradient(145deg, rgba(15,23,42,.74), rgba(8,47,73,.5), rgba(20,83,45,.4));
    border-color: rgba(125,211,252,.3);
}

.feature-tile i {
    background: linear-gradient(145deg, rgba(14,165,233,.38), rgba(37,99,235,.32), rgba(34,197,94,.3));
    color: #67e8f9;
}

.hero-swiper .swiper-pagination-bullet {
    background: rgba(148,163,184,.5);
}

.hero-swiper .swiper-pagination-bullet-active {
    background: #22d3ee;
    box-shadow: 0 0 0 5px rgba(34,211,238,.22);
}

@media (max-width: 575.98px) {
    .hero-copy {
        padding-left: 4px;
    }
}

@keyframes auroraSweep {
    0% { transform: translate3d(0, 0, 0) scale(1); opacity: .74; }
    100% { transform: translate3d(-2%, 4%, 0) scale(1.06); opacity: .92; }
}

@keyframes gridShift {
    from { transform: translate3d(0, 0, 0); }
    to { transform: translate3d(52px, 52px, 0); }
}

@keyframes scanlineMove {
    from { transform: translateY(0); opacity: 0; }
    12% { opacity: .85; }
    55% { opacity: .35; }
    to { transform: translateY(190%); opacity: 0; }
}

@keyframes particleRise {
    0% { transform: translate3d(0, 0, 0) scale(.88); opacity: .2; }
    40% { opacity: .85; }
    100% { transform: translate3d(12px, -64px, 0) scale(1.04); opacity: .1; }
}

@keyframes salePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.04); }
}

@keyframes gearRotateCW {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes gearRotateCCW {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}

@media (prefers-reduced-motion: reduce) {
    .tech-homepage::after,
    .tech-decor-grid::before,
    .tech-decor-scanline::before,
    .tech-particle,
    .discount-pill,
    .gear,
    .core-scan {
        animation: none !important;
    }
}

@media (max-width: 991.98px) {
    .hero-slide,
    .hero-slide-content { min-height: 390px; }
    .hero-slide-content { padding-right: 1rem; }
    .hero-swiper .swiper-button-prev,
    .hero-swiper .swiper-button-next { display: none; }
    .hero-stats { margin-top: 10px; }
    .hero-swiper .swiper-pagination {
        left: 0;
        right: 0;
        width: 100%;
        text-align: center;
        padding-left: 0;
    }
    .gear-field { opacity: .35; }
}

@media (max-width: 575.98px) {
    .section-wrap { padding-top: 2.2rem; }
    .hero-title { font-size: 1.6rem; }
    .hero-desc { font-size: .95rem; }
    .tech-particle:nth-child(n+4) { display: none; }
    .feature-tile { font-size: .86rem; }
    .gear.g3,
    .gear.g4,
    .gear.g6 { display: none; }
    :root {
        --sq-cut: 9px;
    }
}
</style>

<div class="tech-homepage">
    <div class="tech-decor-grid"></div>
    <div class="tech-decor-scanline"></div>
    <div class="tech-decor-particles">
        <span class="tech-particle"></span>
        <span class="tech-particle"></span>
        <span class="tech-particle"></span>
        <span class="tech-particle"></span>
        <span class="tech-particle"></span>
        <span class="tech-particle"></span>
    </div>
    <div class="tech-orbs">
        <span class="tech-orb one"></span>
        <span class="tech-orb two"></span>
        <span class="tech-orb three"></span>
    </div>
    <div class="gear-field" aria-hidden="true">
        <span class="gear g1"><i class="fa-solid fa-gear"></i></span>
        <span class="gear g2"><i class="fa-solid fa-cog"></i></span>
        <span class="gear g3"><i class="fa-solid fa-gear"></i></span>
        <span class="gear g4"><i class="fa-solid fa-cog"></i></span>
        <span class="gear g5"><i class="fa-solid fa-gear"></i></span>
        <span class="gear g6"><i class="fa-solid fa-cog"></i></span>
    </div>

    <div class="container content-layer pb-5">
        <section class="hero-zone">
            <div class="swiper hero-swiper">
                <div class="swiper-wrapper">
                    <?php if (!empty($homeSliderBanners)): ?>
                        <?php foreach ($homeSliderBanners as $banner): ?>
                            <?php
                            $link = trim((string)($banner['link'] ?? ''));
                            $title = (string)($banner['title'] ?? 'Banner trang chu');
                            $image = (string)($banner['image'] ?? '');
                            ?>
                            <div class="swiper-slide">
                                <div class="hero-slide">
                                    <img src="<?= View::e($image) ?>" alt="<?= View::e($title) ?>">
                                    <div class="hero-slide-overlay"></div>
                                    <div class="container hero-slide-content py-4">
                                        <div class="row hero-tech-layout g-4 align-items-center">
                                            <div class="col-xl-7 px-0 hero-copy">
                                                <span class="hero-chip"><i class="fa-solid fa-bolt"></i>Công nghệ mới mỗi ngày</span>
                                                <h1 class="display-5 hero-title mb-3"><?= View::e($title) ?></h1>
                                                <p class="hero-desc mb-4">Khám phá giá tốt, deal nhanh và hệ sinh thái phụ kiện công nghệ tối ưu cho học tập, công việc và gaming.</p>
                                                <?php if ($link !== ''): ?>
                                                    <a href="<?= View::e($link) ?>" class="btn btn-info btn-lg fw-semibold px-4">Xem ngay</a>
                                                <?php else: ?>
                                                    <a href="/products" class="btn btn-info btn-lg fw-semibold px-4">Xem ngay</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($defaultSlides as $slide): ?>
                            <div class="swiper-slide">
                                <div class="hero-slide <?= View::e($slide['class']) ?>" style="background: linear-gradient(135deg, #0b1e46, #1d4ed8);">
                                    <div class="hero-slide-overlay"></div>
                                    <div class="container hero-slide-content py-4">
                                        <div class="row hero-tech-layout g-4 align-items-center">
                                            <div class="col-xl-7 px-0 hero-copy">
                                                <span class="hero-chip"><i class="fa-solid fa-microchip"></i><?= View::e($slide['badge']) ?></span>
                                                <h1 class="display-5 hero-title mb-3"><?= View::e($slide['title']) ?></h1>
                                                <p class="hero-desc mb-4"><?= View::e($slide['desc']) ?></p>
                                                <a href="<?= View::e($slide['link']) ?>" class="btn btn-info btn-lg fw-semibold px-4"><?= View::e($slide['button']) ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>

            <div class="hero-stats">
                <div class="row g-3">
                    <?php foreach ($heroStats as $s): ?>
                        <div class="col-6 col-lg-3">
                            <div class="hero-stat-card">
                                <div class="v"><?= View::e($s['value']) ?></div>
                                <div class="k"><?= View::e($s['label']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tech-feature-strip reveal-up">
                <div class="row g-2 g-lg-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="feature-tile"><i class="fa-solid fa-gears"></i><span>Cập nhật sản phẩm theo nhịp công nghệ</span></div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="feature-tile"><i class="fa-solid fa-bolt"></i><span>Deal mới mỗi ngày, giá hiển thị theo thời gian thực</span></div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="feature-tile"><i class="fa-solid fa-shield-halved"></i><span>Bảo hành rõ ràng, hỗ trợ kỹ thuật nhanh</span></div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="feature-tile"><i class="fa-solid fa-truck-fast"></i><span>Đóng gói kỹ, giao hàng toàn quốc</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-wrap" id="home-categories">
            <div class="section-head d-flex flex-column flex-lg-row justify-content-between gap-2 align-items-lg-end">
                <div>
                    <div class="section-kicker">Khám phá nhanh</div>
                    <h2 class="section-title h3">Danh mục nổi bật</h2>
                    <p class="section-subtitle">Lựa chọn nhanh những nhóm sản phẩm được quan tâm nhiều trong hệ thống.</p>
                </div>
                <a href="/products" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
            </div>
            <div class="neo-panel p-3">
                <div class="row g-3">
                    <?php foreach ($categories as $c): ?>
                        <?php
                        $icon = trim((string)($c['icon'] ?? 'fa-microchip'));
                        $isFa = str_starts_with($icon, 'fa-');
                        ?>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a class="category-tech-card" href="/products?category=<?= urlencode((string)$c['slug']) ?>">
                                <div class="category-tech-icon">
                                    <?php if ($isFa): ?>
                                        <i class="fa-solid <?= View::e($icon) ?>"></i>
                                    <?php else: ?>
                                        <span class="material-symbols-outlined"><?= View::e($icon) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-semibold small text-light"><?= View::e((string)$c['name']) ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <div class="col-12"><div class="alert alert-light border mb-0">Hiện chưa có danh mục để hiển thị.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section-wrap">
            <div class="section-head">
                <div class="section-kicker">Giá tốt trong ngày</div>
                <h2 class="section-title h3">Flash Sale</h2>
                <p class="section-subtitle">Sản phẩm đang giảm giá được tổng hợp từ giá gốc và giá bán của biến thể.</p>
            </div>
            <div class="row g-3">
                <?php foreach ($flashSaleProducts as $p): ?>
                    <?= $renderProductCard($p, false) ?>
                <?php endforeach; ?>
                <?php if (empty($flashSaleProducts)): ?>
                    <div class="col-12"><div class="alert alert-light border">Chưa có sản phẩm Flash Sale.</div></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-wrap">
            <div class="section-head">
                <div class="section-kicker">Dữ liệu bán hàng</div>
                <h2 class="section-title h3">Sản phẩm bán chạy</h2>
                <p class="section-subtitle">Danh sách được sắp xếp theo tổng số lượng bán trong hệ thống đơn hàng.</p>
            </div>
            <div class="row g-3">
                <?php foreach ($bestSellingProducts as $p): ?>
                    <?= $renderProductCard($p, true) ?>
                <?php endforeach; ?>
                <?php if (empty($bestSellingProducts)): ?>
                    <div class="col-12"><div class="alert alert-light border">Chưa có dữ liệu bán chạy.</div></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-wrap">
            <div class="section-head">
                <div class="section-kicker">Hàng mới về</div>
                <h2 class="section-title h3">Sản phẩm mới</h2>
                <p class="section-subtitle">Cập nhật những sản phẩm mới nhất để bạn theo dõi xu hướng công nghệ.</p>
            </div>
            <div class="row g-3">
                <?php foreach ($newProducts as $p): ?>
                    <?= $renderProductCard($p, false) ?>
                <?php endforeach; ?>
                <?php if (empty($newProducts)): ?>
                    <div class="col-12"><div class="alert alert-light border">Chưa có sản phẩm mới.</div></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-wrap" id="home-voucher-claim">
            <div class="section-head">
                <div class="section-kicker">Ưu đãi thành viên</div>
                <h2 class="section-title h3">Phiếu giảm giá dành cho bạn</h2>
                <p class="section-subtitle">Nhấn lấy phiếu ngay hôm nay để áp dụng khi thanh toán cho đơn hàng phù hợp.</p>
            </div>
            <div class="row g-3">
                <?php if (!empty($homeVouchers)): ?>
                    <?php foreach ($homeVouchers as $voucher): ?>
                        <?php
                        $voucherId = (int)($voucher['id'] ?? 0);
                        $isClaimed = in_array($voucherId, $claimedVoucherIds, true);
                        $remainingQty = max(0, (int)($voucher['quantity'] ?? 0));
                        $applyCategoryName = trim((string)($voucher['apply_category_name'] ?? ''));
                        $customerType = trim((string)($voucher['customer_type'] ?? 'all'));
                        $customerTypeLabel = match ($customerType) {
                            'new' => 'Khách mới',
                            'low' => 'Chi tiêu thấp',
                            'mid' => 'Chi tiêu vừa',
                            'vip' => 'Khách VIP',
                            default => 'Tất cả khách hàng',
                        };
                        ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <article class="voucher-tech-card h-100 p-3 p-lg-4 d-flex flex-column">
                                <span class="voucher-tech-chip"><i class="fa-solid fa-ticket"></i> Phiếu giảm giá</span>
                                <div class="voucher-tech-title">Giảm <?= number_format((int)($voucher['discount_amount'] ?? 0)) ?>đ</div>
                                <div class="fw-semibold mb-2"><?= View::e((string)($voucher['name'] ?? 'Ưu đãi đặc biệt')) ?></div>
                                <p class="voucher-tech-desc mb-3">Mã: <span class="fw-bold"><?= View::e((string)($voucher['code'] ?? '')) ?></span></p>
                                <div class="small mb-1">
                                    Danh mục áp dụng: <span class="fw-semibold"><?= View::e($applyCategoryName !== '' ? $applyCategoryName : 'Tất cả danh mục') ?></span>
                                </div>
                                <div class="small mb-1">
                                    Dành cho: <span class="fw-semibold"><?= View::e($customerTypeLabel) ?></span>
                                </div>
                                <div class="small mb-1">Hạn dùng: <?= View::e(date('d/m/Y', strtotime((string)($voucher['end_date'] ?? '')))) ?></div>
                                <div class="small text-muted mb-3">Số lượng còn: <?= $remainingQty ?></div>
                                <div class="mt-auto d-grid">
                                    <?php if ($isPurchaseLoggedIn): ?>
                                        <?php if ($isClaimed): ?>
                                            <button type="button" class="btn btn-secondary fw-semibold" disabled>Đã lấy</button>
                                        <?php else: ?>
                                            <form method="POST" action="/vouchers/claim" class="m-0">
                                                <input type="hidden" name="voucher_id" value="<?= $voucherId ?>">
                                                <button type="submit" class="btn btn-light fw-semibold">Lấy phiếu</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="/login?next=/" class="btn btn-light fw-semibold">Đăng nhập để lấy phiếu</a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-light border">Hiện chưa có phiếu giảm giá khả dụng.</div></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-wrap">
            <div class="section-head">
                <div class="section-kicker">Tin công nghệ</div>
                <h2 class="section-title h3">Blog và tin tức</h2>
                <p class="section-subtitle">Thông tin mới, mẹo sử dụng và kinh nghiệm chọn phụ kiện cho từng nhu cầu.</p>
            </div>
            <div class="row g-3">
                <?php if (!empty($latestPosts)): ?>
                    <?php foreach ($latestPosts as $post): ?>
                        <?php
                        $slug = trim((string)($post['slug'] ?? ''));
                        $postedAt = (string)($post['posted_at'] ?? '');
                        ?>
                        <div class="col-12 col-md-4">
                            <article class="news-tech-card h-100">
                                <?php if (!empty($post['cover_image'])): ?>
                                    <img src="<?= View::e((string)$post['cover_image']) ?>" class="w-100" alt="<?= View::e((string)$post['title']) ?>" style="height: 190px; object-fit: cover; border-radius: 12px 12px 0 0;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center text-primary bg-white" style="height: 190px; border-radius: 12px 12px 0 0;">
                                        <i class="fa-regular fa-newspaper fs-1"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="p-3 news-tech-body">
                                    <h3 class="h6 fw-semibold blog-title mb-2"><?= View::e((string)$post['title']) ?></h3>
                                    <div class="small text-muted mb-2"><?= $postedAt !== '' ? View::e(date('d/m/Y', strtotime($postedAt))) : '' ?></div>
                                    <p class="text-muted small mb-3"><?= View::e((string)($post['excerpt'] ?? 'Nội dung tóm tắt đang được cập nhật.')) ?></p>
                                    <a href="/blog/<?= urlencode($slug) ?>" class="btn btn-outline-primary btn-sm mt-auto align-self-start">Đọc bài viết</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($defaultPosts as $post): ?>
                        <div class="col-12 col-md-4">
                            <article class="news-tech-card h-100">
                                <div class="d-flex align-items-center justify-content-center text-primary bg-white" style="height: 190px; border-radius: 12px 12px 0 0;">
                                    <i class="fa-regular fa-newspaper fs-1"></i>
                                </div>
                                <div class="p-3 news-tech-body">
                                    <div class="small text-muted mb-2"><?= View::e($post['date']) ?></div>
                                    <h3 class="h6 fw-semibold blog-title mb-2"><?= View::e($post['title']) ?></h3>
                                    <p class="text-muted small mb-3"><?= View::e($post['excerpt']) ?></p>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-auto align-self-start" disabled>Sắp cập nhật</button>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-wrap pb-3">
            <div class="section-head">
                <div class="section-kicker">Niềm tin khách hàng</div>
                <h2 class="section-title h3">Đánh giá khách hàng</h2>
                <p class="section-subtitle">Phản hồi thực tế giúp bạn có thêm cơ sở trước khi lựa chọn sản phẩm.</p>
            </div>
            <div class="row g-3">
                <?php if (!empty($visibleReviews)): ?>
                    <?php foreach ($visibleReviews as $rv): ?>
                        <?php
                        $productSlug = trim((string)($rv['product_slug'] ?? ''));
                        $productId = (int)($rv['product_id'] ?? 0);
                        $reviewProductUrl = $productSlug !== ''
                            ? '/product/' . rawurlencode($productSlug)
                            : '/products/' . $productId;
                        ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <a href="<?= View::e($reviewProductUrl) ?>" class="text-decoration-none d-block h-100">
                                <article class="review-tech-card h-100 p-3">
                                    <div class="text-warning mb-2">
                                        <?php $rating = max(1, min(5, (int)($rv['rating'] ?? 5))); ?>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa-<?= $i <= $rating ? 'solid' : 'regular' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-secondary mb-3" style="min-height: 72px;"><?= View::e((string)($rv['comment'] ?? '')) ?></p>
                                    <div class="small fw-semibold mb-1"><?= View::e((string)($rv['customer_name'] ?? 'Khách hàng')) ?></div>
                                    <div class="small text-primary fw-semibold">Sản phẩm: <?= View::e((string)($rv['product_name'] ?? '')) ?></div>
                                </article>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="review-tech-card h-100 p-3">
                            <div class="text-warning mb-2"><i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i></div>
                            <p class="text-secondary mb-3" style="min-height: 72px;">"Giao diện dễ theo dõi deal, card sản phẩm rõ ràng và thông tin dễ hiểu."</p>
                            <div class="small fw-semibold">Khách hàng tham khảo</div>
                        </article>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="review-tech-card h-100 p-3">
                            <div class="text-warning mb-2"><i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-regular fa-star"></i></div>
                            <p class="text-secondary mb-3" style="min-height: 72px;">"Flash Sale và khu banner quảng cáo nổi bật, cập nhật khuyến mãi nhanh."</p>
                            <div class="small fw-semibold">Người dùng mới</div>
                        </article>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="review-tech-card h-100 p-3">
                            <div class="text-warning mb-2"><i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i></div>
                            <p class="text-secondary mb-3" style="min-height: 72px;">"Tổng thể giao diện mang chất công nghệ, xem trên điện thoại vẫn rất mượt."</p>
                            <div class="small fw-semibold">Cộng đồng TechGear</div>
                        </article>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if ($voucherToastMessage !== ''): ?>
        <div class="voucher-toast-wrap" id="voucherToastWrap">
            <div class="alert alert-<?= View::e($voucherToastType) ?> alert-dismissible fade show voucher-toast-item mb-0" role="alert">
                <?= View::e($voucherToastMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        </div>
        <script>
        (function () {
            var wrap = document.getElementById('voucherToastWrap');
            if (!wrap) return;
            setTimeout(function () {
                var alertEl = wrap.querySelector('.alert');
                if (!alertEl) return;
                alertEl.classList.remove('show');
                alertEl.classList.add('hide');
                setTimeout(function () {
                    if (wrap && wrap.parentNode) {
                        wrap.parentNode.removeChild(wrap);
                    }
                }, 350);
            }, 2800);
        })();
        </script>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
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

    if (typeof Swiper !== 'undefined') {
        new Swiper('.hero-swiper', {
            loop: true,
            speed: 760,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.hero-swiper .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.hero-swiper .swiper-button-next',
                prevEl: '.hero-swiper .swiper-button-prev',
            },
        });
    }

    const revealNodes = document.querySelectorAll([
        '.hero-stat-card',
        '.section-wrap',
        '.feature-tile',
        '.category-tech-card',
        '.product-tech-card',
        '.voucher-tech-card',
        '.news-tech-card',
        '.review-tech-card',
        '.reveal-up'
    ].join(','));

    revealNodes.forEach((node) => node.classList.add('reveal-ready'));

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('in-view');
                obs.unobserve(entry.target);
            });
        }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });

        revealNodes.forEach((node) => observer.observe(node));
    } else {
        revealNodes.forEach((node) => node.classList.add('in-view'));
    }

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
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const needsLogin = form.dataset.needLogin === '1';
            if (needsLogin) {
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

                let data = null;
                try {
                    data = await response.json();
                } catch (_) {
                    data = null;
                }

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
