<?php $status = (string)($status ?? ''); ?>

<section class="py-5 py-lg-6 auth-shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11 col-xl-10">
                <div class="card border-0 shadow-lg overflow-hidden auth-card">
                    <div class="row g-0">
                        <div class="col-lg-5 auth-visual auth-register d-none d-lg-flex">
                            <div class="p-4 p-xl-5 text-white d-flex flex-column justify-content-between w-100">
                                <div>
                                    <span class="badge rounded-pill text-bg-light text-success fw-semibold px-3 py-2 mb-3">TechGear Member</span>
                                    <h1 class="fw-bold mb-3">Tạo tài khoản mới trong 1 phút</h1>
                                    <p class="text-white lead mb-4" style="color:rgba(255,255,255,.96) !important;">Đăng ký để lưu thông tin mua sắm, theo dõi đơn hàng và nhận ưu đãi riêng cho thành viên.</p>
                                </div>
                                <div class="auth-benefits">
                                    <div><i class="fas fa-circle-check me-2"></i>Miễn phí tạo tài khoản</div>
                                    <div><i class="fas fa-user-shield me-2"></i>Bảo mật thông tin người dùng</div>
                                    <div><i class="fas fa-gift me-2"></i>Nhận ưu đãi theo nhu cầu mua sắm</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 bg-white">
                            <div class="p-4 p-lg-5">
                                <div class="mb-4">
                                    <h2 class="fw-bold text-dark mb-1">Đăng ký</h2>
                                    <p class="text-muted mb-0">Đã có tài khoản? <a href="/login" class="fw-semibold text-decoration-none">Quay lại đăng nhập</a></p>
                                </div>

                                <?php if ($status === 'invalid'): ?>
                                    <div class="alert alert-warning" role="alert">Thông tin chưa hợp lệ. Họ tên và số điện thoại là bắt buộc, mật khẩu cần ít nhất 6 ký tự và phải trùng khớp.</div>
                                <?php elseif ($status === 'exists'): ?>
                                    <div class="alert alert-danger" role="alert">Email này đã được đăng ký trong hệ thống.</div>
                                <?php endif; ?>

                                <a href="/auth/google" class="btn btn-outline-dark w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                                    <i class="fab fa-google"></i>
                                    <span>Đăng ký nhanh bằng Google</span>
                                </a>

                                <div class="text-center text-muted small mb-3">hoặc tạo tài khoản bằng email</div>

                                <form method="POST" action="/register" class="row g-3">
                                    <div class="col-12">
                                        <label for="full_name" class="form-label fw-semibold">Họ và tên</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-id-card text-muted"></i></span>
                                            <input id="full_name" type="text" name="full_name" class="form-control" placeholder="Nguyễn Văn A" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">Email</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                                            <input id="email" type="email" name="email" class="form-control" placeholder="tub2306648@student.ctu.edu.vn" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold">Số điện thoại</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-phone text-muted"></i></span>
                                            <input id="phone" type="text" name="phone" class="form-control" placeholder="09xxxxxxxx" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                            <input id="password" type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password_confirm" class="form-label fw-semibold">Nhập lại mật khẩu</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                            <input id="password_confirm" type="password" name="password_confirm" class="form-control" placeholder="Nhập lại mật khẩu" required>
                                        </div>
                                    </div>

                                    <div class="col-12 d-grid mt-2">
                                        <button type="submit" class="btn btn-success btn-lg fw-semibold">Đăng ký</button>
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
            radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.12), transparent 30%),
            #f8fafc;
    }
    .auth-card {
        border-radius: 1.5rem;
    }
    .auth-visual {
        min-height: 100%;
    }
    .auth-register {
        background: linear-gradient(145deg, #0f766e 0%, #10b981 50%, #22d3ee 100%);
    }
    .auth-benefits {
        display: grid;
        gap: .85rem;
        font-size: .95rem;
        color: rgba(255, 255, 255, .9);
    }
    .auth-benefits i {
        color: #fef08a;
    }
</style>
