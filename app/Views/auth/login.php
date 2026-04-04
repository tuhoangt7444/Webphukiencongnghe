<?php
$status = (string)($status ?? '');
$next = (string)($next ?? '');
?>

<section class="py-5 py-lg-6 auth-shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-lg overflow-hidden auth-card">
                    <div class="row g-0">
                        <div class="col-lg-5 auth-visual auth-login d-none d-lg-flex">
                            <div class="p-4 p-xl-5 text-white d-flex flex-column justify-content-between w-100">
                                <div>
                                    <span class="badge rounded-pill text-bg-light text-primary fw-semibold px-3 py-2 mb-3">TechGear Account</span>
                                    <h1 class="fw-bold mb-3">Đăng nhập để tiếp tục mua sắm</h1>
                                    <p class="text-white lead mb-1" style="color:rgba(133, 164, 199, 0.96) !important;">Quản lý giỏ hàng, theo dõi đơn hàng và nhận gợi ý linh kiện phù hợp với cấu hình của bạn.</p>
                                </div>
                                <div class="auth-benefits">
                                    <div><i class="fas fa-shield-halved me-2"></i>Mật khẩu được mã hóa an toàn</div>
                                    <div><i class="fas fa-cart-shopping me-2"></i>Đồng bộ đơn hàng và giỏ hàng</div>
                                    <div><i class="fas fa-bolt me-2"></i>Truy cập nhanh khu vực cá nhân</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 bg-white">
                            <div class="p-4 p-lg-5">
                                <div class="mb-4">
                                    <h2 class="fw-bold text-dark mb-1">Đăng nhập</h2>
                                    <p class="text-muted mb-0">Chưa có tài khoản? <a href="/register" class="fw-semibold text-decoration-none">Đăng ký ngay</a></p>
                                </div>

                                <?php if ($status === 'invalid'): ?>
                                    <div class="alert alert-warning" role="alert">Vui lòng nhập email hoặc username và mật khẩu.</div>
                                <?php elseif ($status === 'failed'): ?>
                                    <div class="alert alert-danger" role="alert">Email hoặc username hoặc mật khẩu chưa đúng.</div>
                                <?php elseif ($status === 'locked'): ?>
                                    <div class="alert alert-danger" role="alert">Tài khoản của bạn hiện đang bị khóa.</div>
                                <?php elseif ($status === 'logout'): ?>
                                    <div class="alert alert-success" role="alert">Bạn đã đăng xuất thành công.</div>
                                <?php elseif ($status === 'auth-required'): ?>
                                    <div class="alert alert-info" role="alert">Vui lòng đăng nhập để tiếp tục.</div>
                                <?php elseif ($status === 'buy-login-required'): ?>
                                    <div class="alert alert-warning" role="alert">Bạn cần đăng nhập trước khi mua hàng.</div>
                                <?php elseif ($status === 'review-login-required'): ?>
                                    <div class="alert alert-warning" role="alert">Bạn cần đăng nhập để gửi đánh giá cho sản phẩm đã mua.</div>
                                <?php elseif ($status === 'google-unavailable'): ?>
                                    <div class="alert alert-warning" role="alert">Đăng nhập Google chưa được cấu hình đầy đủ trên hệ thống.</div>
                                <?php elseif ($status === 'google-state-invalid'): ?>
                                    <div class="alert alert-danger" role="alert">Phiên đăng nhập Google không hợp lệ, vui lòng thử lại.</div>
                                <?php elseif ($status === 'google-denied'): ?>
                                    <div class="alert alert-warning" role="alert">Bạn đã từ chối quyền đăng nhập Google.</div>
                                <?php elseif ($status === 'google-email-required'): ?>
                                    <div class="alert alert-warning" role="alert">Tài khoản Google chưa cung cấp email hợp lệ.</div>
                                <?php elseif ($status === 'google-failed'): ?>
                                    <div class="alert alert-danger" role="alert">Đăng nhập Google thất bại, vui lòng thử lại sau.</div>
                                <?php elseif ($status === 'password-reset-success'): ?>
                                    <div class="alert alert-success" role="alert">Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.</div>
                                <?php endif; ?>

                                <a href="/auth/google" class="btn btn-outline-dark w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                                    <i class="fab fa-google"></i>
                                    <span>Tiếp tục với Google</span>
                                </a>

                                <div class="text-center text-muted small mb-3">hoặc đăng nhập bằng email</div>

                                <form method="POST" action="/login" class="row g-3">
                                    <?php if ($next !== ''): ?>
                                        <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php endif; ?>

                                    <div class="col-12">
                                        <label for="login" class="form-label fw-semibold">Email hoặc username</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                                            <input id="login" type="text" name="login" class="form-control" placeholder="vd: minhtran hoặc tub2306648@student.ctu.edu.vn" required>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                            <input id="password" type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                                        </div>
                                    </div>

                                    <div class="col-12 d-grid mt-2">
                                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">Đăng nhập</button>
                                    </div>

                                    <div class="col-12 text-end">
                                        <a href="/forgot-password" class="small text-decoration-none fw-semibold">Quên mật khẩu?</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .auth-shell {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 28%),
            radial-gradient(circle at bottom right, rgba(6, 182, 212, 0.12), transparent 30%),
            #f8fafc;
    }
    .auth-card {
        border-radius: 1.5rem;
    }
    .auth-visual {
        min-height: 100%;
    }
    .auth-login {
        background: linear-gradient(145deg, #0f172a 0%, #1d4ed8 55%, #0891b2 100%);
    }
    .auth-benefits {
        display: grid;
        gap: .85rem;
        font-size: .95rem;
        color: rgba(255, 255, 255, .88);
    }
    .auth-benefits i {
        color: #facc15;
    }
</style>
