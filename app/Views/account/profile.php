<?php use App\Core\View; ?>

<?php
$status = (string)($status ?? '');
$isAdmin = (bool)($isAdmin ?? false);
$avatarUrl = trim((string)($avatarUrl ?? ''));
$avatarFallback = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" rx="60" fill="#dbeafe"/><circle cx="60" cy="46" r="22" fill="#93c5fd"/><path d="M20 106c8-20 24-30 40-30s32 10 40 30" fill="#93c5fd"/></svg>');
$profile = $profile ?? [
    'full_name' => '',
    'phone' => '',
    'address_line' => '',
    'ward' => '',
    'district' => '',
    'city' => '',
];

$fullAddress = trim(implode(', ', array_filter([
    (string)$profile['address_line'],
    (string)$profile['ward'],
    (string)$profile['district'],
    (string)$profile['city'],
], static fn($v) => $v !== '')));

if ((string)($profile['full_address'] ?? '') !== '') {
    $fullAddress = (string)$profile['full_address'];
}
?>

<section class="py-5 bg-light" style="min-height:60vh">
    <div class="container px-3 px-lg-4">
        <div class="mx-auto card border-0 shadow-sm" style="max-width:900px;border-radius:18px;">
            <div class="card-body p-4 p-lg-5">
                <p class="text-uppercase fw-bold mb-1" style="letter-spacing:.16em;font-size:.72rem;color:#0e7490">Tài khoản</p>
                <h1 class="mt-2 mb-0 h3 fw-bold text-dark">Thông tin cá nhân khách hàng</h1>

            <?php if ($status === 'profile-updated'): ?>
                <div class="alert alert-success mt-4 mb-0">Đã cập nhật thông tin cá nhân thành công.</div>
            <?php endif; ?>

            <div class="mt-4 d-flex align-items-center gap-3 p-3 border rounded-3 bg-white">
                <img
                    src="<?= View::e($avatarUrl !== '' ? $avatarUrl : $avatarFallback) ?>"
                    onerror="this.onerror=null;this.src='<?= View::e($avatarFallback) ?>';"
                    alt="Avatar tài khoản"
                    style="width:72px;height:72px;border-radius:999px;object-fit:cover;border:2px solid rgba(14,116,144,0.35);"
                >
                <div>
                    <p class="text-uppercase text-muted mb-1" style="font-size:.72rem">Avatar</p>
                    <p class="mb-0 fw-semibold text-dark">Ảnh đại diện tài khoản đang đăng nhập</p>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12 col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-white">
                        <p class="text-uppercase text-muted mb-1" style="font-size:.72rem">Mã người dùng</p>
                        <p class="mb-0 fs-5 fw-bold text-dark">#<?= (int)$userId ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-white">
                        <p class="text-uppercase text-muted mb-1" style="font-size:.72rem">Email</p>
                        <p class="mb-0 fw-semibold text-dark text-break"><?= View::e($userEmail) ?></p>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-white">
                        <p class="text-uppercase text-muted mb-1" style="font-size:.72rem">Họ và tên</p>
                        <p class="mb-0 fw-semibold text-dark"><?= View::e((string)($profile['full_name'] ?: 'Chưa cập nhật')) ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-white">
                        <p class="text-uppercase text-muted mb-1" style="font-size:.72rem">Số điện thoại</p>
                        <p class="mb-0 fw-semibold text-dark"><?= View::e((string)($profile['phone'] ?: 'Chưa cập nhật')) ?></p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded-3 p-3 bg-white">
                        <p class="text-uppercase text-muted mb-1" style="font-size:.72rem">Địa chỉ nhận hàng</p>
                        <p class="mb-0 fw-semibold text-dark"><?= View::e($fullAddress !== '' ? $fullAddress : 'Chưa cập nhật') ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                <?php if ($isAdmin): ?>
                    <a href="/admin" class="btn btn-dark">Vào trang admin</a>
                <?php endif; ?>
                <a href="/account/edit" class="btn btn-primary">Cập nhật thông tin</a>
                <a href="/orders/history" class="btn btn-primary">Xem lịch sử mua hàng</a>
                <a href="/logout" class="btn btn-outline-secondary">Đăng xuất</a>
            </div>
        </div>
    </div>
</section>
