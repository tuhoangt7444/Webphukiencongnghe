<?php use App\Core\View; ?>
<?php
$post = $post ?? [];
$title = (string)($post['title'] ?? 'Bài viết');
$excerpt = (string)($post['excerpt'] ?? '');
$content = (string)($post['content'] ?? '');
$coverImage = trim((string)($post['cover_image'] ?? ''));
$postedAt = (string)($post['posted_at'] ?? '');
$relatedProducts = is_array($relatedProducts ?? null) ? $relatedProducts : [];
?>

<section class="py-4 py-lg-5 bg-light">
    <div class="container">
        <div class="mx-auto" style="max-width: 900px;">
            <a href="/" class="btn btn-outline-secondary btn-sm mb-3">Quay lại trang chủ</a>
            <article class="card shadow-sm border-0">
                <?php if ($coverImage !== ''): ?>
                    <img src="<?= View::e($coverImage) ?>" alt="<?= View::e($title) ?>" class="card-img-top" style="max-height: 420px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h3 fw-bold mb-2"><?= View::e($title) ?></h1>
                    <?php if ($postedAt !== ''): ?>
                        <p class="text-muted small mb-3">Đăng ngày <?= View::e(date('d/m/Y', strtotime($postedAt))) ?></p>
                    <?php endif; ?>
                    <?php if ($excerpt !== ''): ?>
                        <p class="lead text-secondary"><?= View::e($excerpt) ?></p>
                    <?php endif; ?>
                    <hr>
                    <div class="lh-lg text-dark" style="white-space: pre-line;"><?= View::e($content !== '' ? $content : 'Nội dung đang được cập nhật.') ?></div>
                </div>
            </article>

            <?php if (!empty($relatedProducts)): ?>
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="h5 fw-bold mb-3">Sản phẩm liên quan</h2>
                        <div class="row g-3">
                            <?php foreach ($relatedProducts as $rp): ?>
                                <?php
                                $rpId = (int)($rp['id'] ?? 0);
                                $rpSlug = trim((string)($rp['slug'] ?? ''));
                                $rpName = (string)($rp['name'] ?? 'Sản phẩm');
                                $rpImage = trim((string)($rp['image_url'] ?? ''));
                                $rpPrice = (int)($rp['price_from'] ?? 0);
                                $rpLink = $rpSlug !== '' ? '/product/' . urlencode($rpSlug) : '/products/' . $rpId;
                                ?>
                                <div class="col-12 col-md-6">
                                    <a href="<?= View::e($rpLink) ?>" class="text-decoration-none text-reset">
                                        <div class="d-flex gap-3 p-2 rounded border h-100">
                                            <?php if ($rpImage !== ''): ?>
                                                <img src="<?= View::e($rpImage) ?>" alt="<?= View::e($rpName) ?>" style="width:92px;height:72px;object-fit:cover;border-radius:8px;">
                                            <?php else: ?>
                                                <div class="d-flex align-items-center justify-content-center bg-light rounded" style="width:92px;height:72px;">
                                                    <i class="fa-solid fa-box text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-1"><?= View::e($rpName) ?></div>
                                                <div class="small text-primary fw-bold"><?= number_format($rpPrice) ?>đ</div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
