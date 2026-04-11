<?php
use App\Core\View;

$p = $product ?? [];

$name = (string)($p['name'] ?? 'Sản phẩm');
$shortDescription = trim((string)($p['short_description'] ?? ''));
$description = (string)($p['description'] ?? 'Đang cập nhật mô tả sản phẩm.');
$categoryName = (string)($p['category_name'] ?? 'Chưa phân loại');
$brandName = trim((string)($p['brand_name'] ?? ''));
$warrantyMonths = max(0, (int)($p['warranty_months'] ?? 0));
$highlightsRaw = trim((string)($p['highlights'] ?? ''));
$shippingInfoRaw = trim((string)($p['shipping_info'] ?? ''));
$technicalSpecsRaw = trim((string)($p['technical_specs'] ?? ''));
$rating = (float)($p['rating'] ?? 0);
$stock = (int)($p['stock_total'] ?? ($p['stock'] ?? 0));
$createdAt = (string)($p['created_at'] ?? '');

$price = (int)($p['price_from'] ?? ($p['price'] ?? 0));
$originalPrice = (int)($p['original_price'] ?? $price);
$discountPercent = max(0, min(90, (int)($p['discount_percent'] ?? 0)));
$finalPrice = $discountPercent > 0 ? max(0, (int)floor($price * (100 - $discountPercent) / 100)) : $price;

$mainImage = trim((string)($p['image'] ?? ''));
$gallery = $p['images'] ?? [];
if (!is_array($gallery)) {
    $gallery = [];
}

$galleryClean = [];
foreach ($gallery as $img) {
    $url = trim((string)$img);
    if ($url !== '') {
        $galleryClean[] = $url;
    }
}
if ($mainImage !== '') {
    array_unshift($galleryClean, $mainImage);
}
$galleryClean = array_values(array_unique($galleryClean));

if (empty($galleryClean)) {
    $galleryClean[] = '/images/placeholder-product.svg';
}

$reviews = $reviews ?? [];
if (!is_array($reviews)) {
    $reviews = [];
}

$reviewStatus = (string)($reviewStatus ?? '');
$canReview = (bool)($canReview ?? false);
$userReview = is_array($userReview ?? null) ? $userReview : null;
$isReviewLoggedIn = (bool)($isReviewLoggedIn ?? false);

$productId = (int)($p['id'] ?? 0);
$reviews = array_values(array_filter($reviews, static function ($rv) use ($productId): bool {
    if (!is_array($rv)) {
        return false;
    }

    if (isset($rv['product_id'])) {
        return (int)$rv['product_id'] === $productId;
    }

    return true;
}));

$relatedProducts = $relatedProducts ?? [];
if (!is_array($relatedProducts)) {
    $relatedProducts = [];
}

$isPurchaseLoggedIn = isset($_SESSION['user']) || isset($_SESSION['user_id']);
$stockLabel = $stock > 0 ? 'Còn hàng' : 'Hết hàng';
$stockClass = $stock > 0 ? 'text-success' : 'text-danger';
$createdDateLabel = '';
$postedAgeLabel = 'Vừa cập nhật';

if ($createdAt !== '') {
    $createdTs = strtotime($createdAt);
    if ($createdTs !== false) {
        $createdDateLabel = date('d/m/Y', $createdTs);
        $days = (int)floor((time() - $createdTs) / 86400);
        if ($days <= 0) {
            $postedAgeLabel = 'Mới đăng hôm nay';
        } elseif ($days === 1) {
            $postedAgeLabel = 'Đăng từ hôm qua';
        } elseif ($days < 7) {
            $postedAgeLabel = 'Đăng ' . $days . ' ngày trước';
        } else {
            $postedAgeLabel = 'Đăng ổn định';
        }
    }
}

$renderStars = static function (float $value): string {
    $full = (int)floor($value);
    $full = max(0, min(5, $full));

    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fa-' . ($i <= $full ? 'solid' : 'regular') . ' fa-star"></i>';
    }

    return $html;
};

$specRows = [];
if ($technicalSpecsRaw !== '') {
    $lines = preg_split('/\r\n|\r|\n/', $technicalSpecsRaw) ?: [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') {
            continue;
        }

        $parts = explode('#', $line, 2);
        $label = trim((string)($parts[0] ?? ''));
        $value = trim((string)($parts[1] ?? ''));

        if ($label === '' && $value === '') {
            continue;
        }

        if ($value === '') {
            $value = $label;
            $label = 'Thông số';
        }

        $specRows[] = [
            'label' => $label,
            'value' => $value,
        ];
    }
}

$highlightItems = [];
if ($highlightsRaw !== '') {
    $lines = preg_split('/\r\n|\r|\n/', $highlightsRaw) ?: [];
    foreach ($lines as $line) {
        $item = trim((string)$line);
        if ($item === '') {
            continue;
        }
        $item = ltrim($item, "-• \t");
        if ($item !== '') {
            $highlightItems[] = $item;
        }
    }
}

$shippingItems = [];
if ($shippingInfoRaw !== '') {
    $lines = preg_split('/\r\n|\r|\n/', $shippingInfoRaw) ?: [];
    foreach ($lines as $line) {
        $item = trim((string)$line);
        if ($item === '') {
            continue;
        }
        $item = ltrim($item, "-• \t");
        if ($item !== '') {
            $shippingItems[] = $item;
        }
    }
}
?>

<style>
.pdp-page {
    background:
        radial-gradient(circle at 8% 2%, rgba(59, 130, 246, .14), transparent 23%),
        radial-gradient(circle at 92% 0%, rgba(14, 165, 233, .16), transparent 26%),
        linear-gradient(180deg, #f7fbff 0%, #edf5ff 100%);
}

.pdp-breadcrumb {
    --bs-breadcrumb-divider: '/';
}

.pdp-card {
    border: 1px solid rgba(148, 163, 184, .25);
    border-radius: 18px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, .07);
    backdrop-filter: blur(1.5px);
}

.pdp-card:hover {
    box-shadow: 0 18px 34px rgba(15, 23, 42, .11);
}

.pdp-gallery-main {
    aspect-ratio: 1 / 1;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    border: 1px solid rgba(148, 163, 184, .22);
    position: relative;
}

.pdp-gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .45s ease;
}

.pdp-gallery-main:hover img {
    transform: scale(1.06);
}

.pdp-thumbs {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .5rem;
}

.pdp-thumb {
    border: 1px solid rgba(148, 163, 184, .35);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    cursor: pointer;
    transition: all .2s ease;
    aspect-ratio: 1 / 1;
}

.pdp-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pdp-thumb:hover,
.pdp-thumb.active {
    border-color: #0d6efd;
    box-shadow: 0 6px 14px rgba(13, 110, 253, .22);
    transform: translateY(-1px);
}

.pdp-title {
    font-weight: 800;
    letter-spacing: -.02em;
    color: #0f172a;
}

.pdp-price-final {
    color: #0b3a8f;
    font-size: 2.1rem;
    font-weight: 800;
    line-height: 1;
    border-radius: 999px;
    background: linear-gradient(90deg, #dbeafe, #e0f2fe);
    border: 1px solid rgba(59, 130, 246, .2);
    padding: .46rem .78rem;
}

.pdp-qty {
    width: 148px;
}

.pdp-qty .btn {
    width: 40px;
}

.pdp-qty input {
    text-align: center;
}

.pdp-meta-panel {
    border-top: 1px dashed rgba(148, 163, 184, .6);
    margin-top: .75rem;
    padding-top: .9rem;
}

.pdp-meta-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    margin-bottom: .65rem;
}

.pdp-meta-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .32rem .62rem;
    border-radius: 999px;
    font-size: .76rem;
    font-weight: 700;
    color: #0f172a;
    background: linear-gradient(135deg, rgba(219, 234, 254, .95), rgba(186, 230, 253, .95));
    border: 1px solid rgba(59, 130, 246, .3);
}

.pdp-meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
}

.pdp-meta-item {
    border: 1px solid rgba(148, 163, 184, .28);
    border-radius: 12px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .95), rgba(248, 250, 252, .95));
    padding: .58rem .66rem;
}

.pdp-meta-label {
    display: flex;
    align-items: center;
    gap: .38rem;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    margin-bottom: .16rem;
}

.pdp-meta-value {
    font-size: .92rem;
    font-weight: 700;
    color: #0f172a;
}

.pdp-tabs .nav-link {
    color: #334155;
    font-weight: 600;
    border-radius: 10px;
}

.pdp-tabs .nav-link.active {
    color: #0d6efd;
    background: rgba(13, 110, 253, .12);
    border-color: rgba(13, 110, 253, .2);
}

.pdp-review-item {
    border: 1px solid rgba(148, 163, 184, .24);
    border-radius: 12px;
    background: #f8fbff;
    transition: transform .2s ease, box-shadow .2s ease;
}

.pdp-review-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(14, 116, 144, .12);
}

.pdp-review-form textarea {
    min-height: 120px;
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

.stock-out-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    font-weight: 700;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(239, 68, 68, .3);
}

.stock-out-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(1px);
    z-index: 10;
}

.pdp-related-card {
    border: 1px solid rgba(148, 163, 184, .25);
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    height: 100%;
    box-shadow: 0 10px 20px rgba(15, 23, 42, .05);
    transition: transform .24s ease, box-shadow .24s ease, border-color .24s ease;
}

.pdp-related-card:hover {
    transform: translateY(-6px);
    border-color: rgba(13, 110, 253, .38);
    box-shadow: 0 16px 28px rgba(15, 23, 42, .12);
}

.pdp-related-thumb {
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #eff6ff;
}

.pdp-related-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .35s ease;
}

.pdp-related-card:hover .pdp-related-thumb img {
    transform: scale(1.05);
}

@media (max-width: 575.98px) {
    .pdp-thumbs {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .pdp-price-final {
        font-size: 1.6rem;
    }

    .pdp-meta-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="pdp-page py-4 py-md-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb pdp-breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="/products" class="text-decoration-none">Danh mục</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= View::e($name) ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="pdp-card bg-white p-3 p-md-4">
                    <div class="pdp-gallery-main">
                        <img id="pdpMainImage" src="<?= View::e($galleryClean[0]) ?>" alt="<?= View::e($name) ?>" <?= $stock <= 0 ? 'style="opacity: 0.5;"' : '' ?>>
                        <?php if ($stock <= 0): ?>
                            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                                <div style="font-size: 1.8rem; font-weight: 700; color: rgba(255,255,255,0.9); text-align: center; text-shadow: 0 2px 8px rgba(0,0,0,0.5);">
                                    <i class="fa-solid fa-ban" style="margin-bottom: 0.5rem; display: block;"></i>
                                    HẾT HÀNG
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="pdp-thumbs mt-3" id="pdpThumbs">
                        <?php foreach ($galleryClean as $i => $img): ?>
                            <button type="button" class="pdp-thumb <?= $i === 0 ? 'active' : '' ?>" data-image="<?= View::e($img) ?>" aria-label="Ảnh sản phẩm <?= $i + 1 ?>">
                                <img src="<?= View::e($img) ?>" alt="<?= View::e($name) ?> ảnh <?= $i + 1 ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="pdp-card bg-white p-3 p-md-4 h-100">
                    <h1 class="pdp-title h3 mb-3"><?= View::e($name) ?></h1>
                    <?php if ($stock <= 0): ?>
                        <div class="stock-out-badge mb-3">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            Sản phẩm hết hàng
                        </div>
                    <?php endif; ?>
                    <?php if ($shortDescription !== ''): ?>
                        <p class="text-secondary mb-3" style="white-space: pre-line;"><?= View::e($shortDescription) ?></p>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="text-warning" aria-label="rating"><?= $renderStars($rating) ?></div>
                        <div class="text-muted small"><?= number_format($rating, 1) ?>/5</div>
                        <div class="small text-muted">Danh mục: <span class="fw-semibold text-dark"><?= View::e($categoryName) ?></span></div>
                        <div class="small <?= $stockClass ?> fw-semibold"><?= View::e($stockLabel) ?><?= $stock > 0 ? ' (' . $stock . ')' : '' ?></div>
                    </div>

                    <div class="d-flex align-items-end flex-wrap gap-2 mb-4">
                        <?php if ($discountPercent > 0 && $originalPrice > $finalPrice): ?>
                            <div>
                                <div class="text-muted text-decoration-line-through small mb-1"><?= number_format($originalPrice) ?>đ</div>
                                <div class="pdp-price-final"><?= number_format($finalPrice) ?>đ</div>
                            </div>
                            <span class="badge text-bg-danger fs-6 align-self-center">-<?= $discountPercent ?>%</span>
                        <?php else: ?>
                            <div class="pdp-price-final"><?= number_format($finalPrice) ?>đ</div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isPurchaseLoggedIn): ?>
                        <div class="alert alert-warning py-2 mb-3" role="alert">
                            Vui lòng đăng nhập để mua hàng
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/cart/add" class="mb-3" <?= $stock <= 0 ? 'data-disabled="1"' : '' ?> data-need-login="<?= $isPurchaseLoggedIn ? '0' : '1' ?>">
                        <input type="hidden" name="product_id" value="<?= (int)($p['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Số lượng</label>
                            <div class="input-group pdp-qty">
                                <button class="btn btn-outline-secondary" type="button" id="qtyMinus" <?= $stock <= 0 ? 'disabled' : '' ?>>-</button>
                                <input type="number" class="form-control" id="qtyInput" name="qty" value="1" min="1" max="<?= max(1, $stock) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>
                                <button class="btn btn-outline-secondary" type="button" id="qtyPlus" <?= $stock <= 0 ? 'disabled' : '' ?>>+</button>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-primary px-4" <?= ($stock <= 0 || !$isPurchaseLoggedIn) ? 'disabled' : '' ?>>
                                Thêm vào giỏ hàng
                            </button>
                            <button type="submit" name="buy_now" value="1" class="btn btn-success px-4" <?= ($stock <= 0 || !$isPurchaseLoggedIn) ? 'disabled' : '' ?>>
                                Mua ngay
                            </button>
                        </div>
                    </form>

                    <div class="pdp-meta-panel">
                        <div class="pdp-meta-badges">
                            <span class="pdp-meta-badge"><i class="fa-solid fa-shield-heart"></i> Hàng chính hãng</span>
                            <span class="pdp-meta-badge"><i class="fa-solid fa-truck-fast"></i> Giao nhanh toàn quốc</span>
                        </div>

                        <div class="pdp-meta-grid">
                            <?php if ($createdDateLabel !== ''): ?>
                                <div class="pdp-meta-item">
                                    <div class="pdp-meta-label"><i class="fa-regular fa-calendar"></i> Ngày lên kệ</div>
                                    <div class="pdp-meta-value"><?= View::e($createdDateLabel) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($brandName !== ''): ?>
                                <div class="pdp-meta-item">
                                    <div class="pdp-meta-label"><i class="fa-solid fa-tag"></i> Thương hiệu</div>
                                    <div class="pdp-meta-value"><?= View::e($brandName) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($warrantyMonths > 0): ?>
                                <div class="pdp-meta-item">
                                    <div class="pdp-meta-label"><i class="fa-solid fa-shield-halved"></i> Bảo hành</div>
                                    <div class="pdp-meta-value"><?= $warrantyMonths ?> tháng</div>
                                </div>
                            <?php endif; ?>

                            <div class="pdp-meta-item">
                                <div class="pdp-meta-label"><i class="fa-solid fa-hashtag"></i> Mã sản phẩm</div>
                                <div class="pdp-meta-value">SKU-<?= (int)($p['id'] ?? 0) ?></div>
                            </div>

                            <div class="pdp-meta-item">
                                <div class="pdp-meta-label"><i class="fa-solid fa-box-open"></i> Tình trạng</div>
                                <div class="pdp-meta-value <?= $stockClass ?>"><?= View::e($stockLabel) ?><?= $stock > 0 ? ' • ' . $stock . ' sản phẩm' : '' ?></div>
                            </div>

                            <div class="pdp-meta-item">
                                <div class="pdp-meta-label"><i class="fa-regular fa-clock"></i> Trạng thái bài đăng</div>
                                <div class="pdp-meta-value"><?= View::e($postedAgeLabel) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pdp-card bg-white p-3 p-md-4 mt-4">
            <ul class="nav nav-tabs border-0 pdp-tabs" id="pdpInfoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc-pane" type="button" role="tab" aria-controls="desc-pane" aria-selected="true">Mô tả sản phẩm</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="spec-tab" data-bs-toggle="tab" data-bs-target="#spec-pane" type="button" role="tab" aria-controls="spec-pane" aria-selected="false">Thông số kỹ thuật</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review-pane" type="button" role="tab" aria-controls="review-pane" aria-selected="false">Đánh giá</button>
                </li>
            </ul>

            <div class="tab-content pt-3" id="pdpTabContent">
                <div class="tab-pane fade show active" id="desc-pane" role="tabpanel" aria-labelledby="desc-tab">
                    <p class="mb-3 text-secondary" style="white-space: pre-line;"><?= View::e($description) ?></p>

                    <?php if (!empty($highlightItems)): ?>
                        <h3 class="h6 fw-bold mb-2">Điểm nổi bật</h3>
                        <ul class="mb-0 ps-3 text-secondary">
                            <?php foreach ($highlightItems as $item): ?>
                                <li class="mb-1"><?= View::e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="spec-pane" role="tabpanel" aria-labelledby="spec-tab">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="border rounded-3 p-2 bg-light-subtle"><strong>Danh mục:</strong> <?= View::e($categoryName) ?></div>
                        </div>
                        <?php if ($brandName !== ''): ?>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-2 bg-light-subtle"><strong>Thương hiệu:</strong> <?= View::e($brandName) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 col-md-6">
                            <div class="border rounded-3 p-2 bg-light-subtle"><strong>Tồn kho:</strong> <?= $stock > 0 ? $stock . ' sản phẩm' : 'Hết hàng' ?></div>
                        </div>
                        <?php if ($warrantyMonths > 0): ?>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-2 bg-light-subtle"><strong>Bảo hành:</strong> <?= $warrantyMonths ?> tháng</div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 col-md-6">
                            <div class="border rounded-3 p-2 bg-light-subtle"><strong>Giá hiện tại:</strong> <?= number_format($finalPrice) ?>đ</div>
                        </div>
                        <?php if (!empty($specRows)): ?>
                            <?php foreach ($specRows as $spec): ?>
                                <div class="col-12 col-md-6">
                                    <div class="border rounded-3 p-2 bg-light-subtle"><strong><?= View::e((string)$spec['label']) ?>:</strong> <?= View::e((string)$spec['value']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="border rounded-3 p-2 bg-light-subtle text-muted">Chưa có thông số kỹ thuật chi tiết cho sản phẩm này.</div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($shippingItems)): ?>
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <strong class="d-block mb-2">Thông tin vận chuyển</strong>
                                    <ul class="mb-0 ps-3 text-secondary">
                                        <?php foreach ($shippingItems as $item): ?>
                                            <li class="mb-1"><?= View::e($item) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="review-pane" role="tabpanel" aria-labelledby="review-tab">
                    <?php if ($reviewStatus === 'submitted'): ?>
                        <div class="alert alert-success">Đánh giá của bạn đã được đăng thành công.</div>
                    <?php elseif ($reviewStatus === 'already-reviewed'): ?>
                        <div class="alert alert-info">Bạn đã đánh giá sản phẩm này trước đó.</div>
                    <?php elseif ($reviewStatus === 'not-eligible'): ?>
                        <div class="alert alert-warning">Chỉ khách hàng đã mua sản phẩm này mới có thể đánh giá.</div>
                    <?php elseif ($reviewStatus === 'invalid'): ?>
                        <div class="alert alert-warning">Vui lòng chọn số sao hợp lệ và nhập nội dung đánh giá.</div>
                    <?php elseif ($reviewStatus === 'failed'): ?>
                        <div class="alert alert-danger">Không thể gửi đánh giá lúc này. Vui lòng thử lại sau.</div>
                    <?php endif; ?>

                    <?php if ($canReview): ?>
                        <div class="pdp-card pdp-review-form bg-white p-3 p-md-4 mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                                <div>
                                    <h3 class="h5 mb-1">Viết đánh giá cho sản phẩm</h3>
                                    <p class="text-muted mb-0">Bạn đã mua sản phẩm này, hãy chia sẻ trải nghiệm sử dụng thực tế.</p>
                                </div>
                                <a href="/reviews/history" class="btn btn-sm btn-outline-primary">Lịch sử đánh giá</a>
                            </div>
                            <form method="POST" action="/reviews" class="row g-3">
                                <input type="hidden" name="product_id" value="<?= $productId ?>">
                                <input type="hidden" name="redirect_to" value="<?= View::e((string)($_SERVER['REQUEST_URI'] ?? ('/products/' . $productId . '?tab=review'))) ?>">
                                <div class="col-12 col-md-4">
                                    <label for="reviewRating" class="form-label fw-semibold">Số sao</label>
                                    <select id="reviewRating" name="rating" class="form-select" required>
                                        <option value="">Chọn số sao</option>
                                        <option value="5">5 sao</option>
                                        <option value="4">4 sao</option>
                                        <option value="3">3 sao</option>
                                        <option value="2">2 sao</option>
                                        <option value="1">1 sao</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="reviewComment" class="form-label fw-semibold">Nội dung đánh giá</label>
                                    <textarea id="reviewComment" name="comment" class="form-control" placeholder="Mô tả trải nghiệm về chất lượng sản phẩm, đóng gói và giao hàng..." required></textarea>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($userReview !== null): ?>
                        <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>Bạn đã đánh giá sản phẩm này <?= (string)($userReview['status'] ?? '') === 'visible' ? 'và đánh giá đang hiển thị.' : 'và đánh giá đang chờ duyệt.' ?></div>
                            <a href="/reviews/history" class="btn btn-sm btn-outline-primary">Xem lịch sử đánh giá</a>
                        </div>
                    <?php elseif ($isReviewLoggedIn): ?>
                        <div class="alert alert-light border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>Chỉ khách hàng đã mua sản phẩm này mới có thể đánh giá.</div>
                            <a href="/orders/history" class="btn btn-sm btn-outline-primary">Xem lịch sử mua hàng</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>Đăng nhập và mua sản phẩm để gửi đánh giá thực tế.</div>
                            <a href="/login?status=review-login-required&next=<?= urlencode((string)($_SERVER['REQUEST_URI'] ?? ('/products/' . $productId . '?tab=review'))) ?>" class="btn btn-sm btn-outline-primary">Đăng nhập để đánh giá</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reviews)): ?>
                        <div class="d-grid gap-2">
                            <?php foreach ($reviews as $rv): ?>
                                <?php
                                $rvRating = (float)($rv['rating'] ?? 5);
                                $rvName = (string)($rv['customer_name'] ?? 'Khách hàng');
                                $rvComment = (string)($rv['comment'] ?? '');
                                ?>
                                <article class="pdp-review-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong><?= View::e($rvName) ?></strong>
                                        <span class="text-warning"><?= $renderStars($rvRating) ?></span>
                                    </div>
                                    <p class="mb-0 text-secondary"><?= View::e($rvComment) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Chưa có đánh giá cho sản phẩm này.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($relatedProducts)): ?>
            <div class="mt-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h4 mb-0">Sản phẩm liên quan</h2>
                    <a href="/products" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                </div>
                <div class="row g-3">
                    <?php foreach (array_slice($relatedProducts, 0, 4) as $rp): ?>
                        <?php
                        $rpName = (string)($rp['name'] ?? 'Sản phẩm');
                        $rpId = (int)($rp['id'] ?? 0);
                        $rpSlug = trim((string)($rp['slug'] ?? ''));
                        $rpImage = trim((string)($rp['image'] ?? ($rp['thumbnail'] ?? '')));
                        $rpPrice = (int)($rp['price'] ?? ($rp['price_from'] ?? 0));
                        $rpOriginalPrice = (int)($rp['original_price'] ?? $rpPrice);
                        $rpDiscountPct = (int)($rp['discount_percent'] ?? 0);
                        $rpLink = $rpSlug !== '' ? '/products/' . urlencode($rpSlug) : '/products/' . $rpId;
                        if ($rpImage === '') {
                            $rpImage = '/images/placeholder-product.svg';
                        }
                        ?>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <article class="pdp-related-card">
                                <a href="<?= View::e($rpLink) ?>" class="d-block pdp-related-thumb">
                                    <img src="<?= View::e($rpImage) ?>" alt="<?= View::e($rpName) ?>">
                                </a>
                                <div class="p-3 d-flex flex-column">
                                    <h3 class="h6 mb-2" style="min-height: 44px;"><?= View::e($rpName) ?></h3>
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                                        <?php if ($rpDiscountPct > 0 && $rpOriginalPrice > $rpPrice): ?>
                                            <span class="text-muted text-decoration-line-through small"><?= number_format($rpOriginalPrice) ?>đ</span>
                                            <span class="fw-bold text-danger"><?= number_format($rpPrice) ?>đ</span>
                                            <span class="badge text-bg-danger">-<?= $rpDiscountPct ?>%</span>
                                        <?php else: ?>
                                            <span class="fw-bold text-primary"><?= number_format($rpPrice) ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= View::e($rpLink) ?>" class="btn btn-outline-primary btn-sm mt-auto">Xem chi tiết</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

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
    const mainImage = document.getElementById('pdpMainImage');
    const thumbContainer = document.getElementById('pdpThumbs');

    if (mainImage && thumbContainer) {
        thumbContainer.addEventListener('click', (event) => {
            const button = event.target.closest('.pdp-thumb');
            if (!button) {
                return;
            }

            const image = button.getAttribute('data-image') || '';
            if (image !== '') {
                mainImage.src = image;
            }

            thumbContainer.querySelectorAll('.pdp-thumb').forEach((el) => el.classList.remove('active'));
            button.classList.add('active');
        });
    }

    const qtyInput = document.getElementById('qtyInput');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');

    if (qtyInput && qtyMinus && qtyPlus) {
        const maxQty = Math.max(1, parseInt(qtyInput.max || '1', 10) || 1);

        const clampQtyInput = () => {
            const raw = parseInt(qtyInput.value || '1', 10);
            const safe = Number.isFinite(raw) ? raw : 1;
            qtyInput.value = String(Math.min(maxQty, Math.max(1, safe)));
        };

        qtyInput.addEventListener('input', clampQtyInput);
        qtyInput.addEventListener('blur', clampQtyInput);

        qtyMinus.addEventListener('click', () => {
            const current = Math.max(1, parseInt(qtyInput.value || '1', 10) || 1);
            qtyInput.value = String(Math.max(1, current - 1));
        });

        qtyPlus.addEventListener('click', () => {
            const current = Math.max(1, parseInt(qtyInput.value || '1', 10) || 1);
            qtyInput.value = String(Math.min(maxQty, current + 1));
        });

        clampQtyInput();
    }

    const purchaseForm = document.querySelector('form[action="/cart/add"]');
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

    const flyToCart = () => {
        const cartButton = getCartButton();
        if (!cartButton) {
            return;
        }

        const cartRect = cartButton.getBoundingClientRect();
        const sourceImage = document.getElementById('pdpMainImage');
        let flyNode;

        if (sourceImage) {
            const startRect = sourceImage.getBoundingClientRect();
            flyNode = sourceImage.cloneNode(true);
            flyNode.className = 'cart-fly-item';
            flyNode.style.left = `${startRect.left}px`;
            flyNode.style.top = `${startRect.top}px`;
            flyNode.style.width = `${startRect.width}px`;
            flyNode.style.height = `${startRect.height}px`;
        } else if (purchaseForm) {
            const btnRect = purchaseForm.getBoundingClientRect();
            flyNode = document.createElement('div');
            flyNode.className = 'cart-fly-item';
            flyNode.style.left = `${btnRect.left + btnRect.width / 2 - 18}px`;
            flyNode.style.top = `${btnRect.top + btnRect.height / 2 - 18}px`;
            flyNode.style.width = '36px';
            flyNode.style.height = '36px';
            flyNode.style.borderRadius = '999px';
            flyNode.style.background = 'linear-gradient(135deg, #1d4ed8, #0ea5e9)';
        }

        if (!flyNode) {
            return;
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

    if (purchaseForm) {
        purchaseForm.addEventListener('submit', async (event) => {
            const submitter = event.submitter;
            const isBuyNow = !!(submitter && submitter.name === 'buy_now' && submitter.value === '1');

            if (purchaseForm.dataset.needLogin === '1') {
                event.preventDefault();
                window.location.href = '/login?status=buy-login-required&next=' + encodeURIComponent(window.location.pathname);
                return;
            }

            if (isBuyNow) {
                return;
            }

            event.preventDefault();

            const addButton = purchaseForm.querySelector('button[type="submit"]:not([name="buy_now"])');
            if (addButton) {
                addButton.disabled = true;
            }

            try {
                const formData = new FormData(purchaseForm);
                formData.delete('buy_now');
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
                flyToCart();
                showQuickToast(data.message || 'Đã thêm vào giỏ hàng.');
            } catch (_) {
                showQuickToast('Kết nối thất bại, vui lòng thử lại.', true);
            } finally {
                if (addButton) {
                    addButton.disabled = false;
                }
            }
        });
    }

    window.addEventListener('load', () => {
        const query = new URLSearchParams(window.location.search);
        const reviewTabTrigger = document.getElementById('review-tab');
        if (window.bootstrap && reviewTabTrigger && (query.get('tab') === 'review' || window.location.hash === '#review-pane')) {
            window.bootstrap.Tab.getOrCreateInstance(reviewTabTrigger).show();
        }
    });
})();
</script>
