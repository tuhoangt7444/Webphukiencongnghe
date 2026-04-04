<?php
$status = (string)($status ?? '');
$email = (string)($email ?? '');
?>

<section class="py-5 py-lg-6 auth-shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card border-0 shadow-lg auth-card">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="fw-bold mb-2">Quên mật khẩu</h2>
                        <p class="text-muted mb-4">Nhập email tài khoản, hệ thống sẽ gửi mã OTP 6 số để bạn đặt lại mật khẩu.</p>

                        <?php if ($status === 'email-invalid'): ?>
                            <div class="alert alert-warning">Email không hợp lệ.</div>
                        <?php elseif ($status === 'otp-rate-limited'): ?>
                            <div class="alert alert-warning">Bạn vừa yêu cầu OTP gần đây. Vui lòng đợi khoảng <?= (int)($_GET['retry_after'] ?? 30) ?> giây rồi thử lại.</div>
                        <?php elseif ($status === 'mail-failed'): ?>
                            <div class="alert alert-danger">Không gửi được OTP qua email. Vui lòng thử lại sau.</div>
                        <?php elseif ($status === 'request-failed'): ?>
                            <div class="alert alert-danger">Không thể xử lý yêu cầu lúc này. Vui lòng thử lại.</div>
                        <?php endif; ?>

                        <form method="POST" action="/forgot-password/send" class="row g-3">
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <input id="email" type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" value="<?= \App\Core\View::e($email) ?>" required>
                            </div>
                            <div class="col-12 d-grid mt-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-semibold">Gửi mã OTP</button>
                            </div>
                        </form>

                        <div class="mt-4 text-muted small">
                            Đã có mật khẩu? <a href="/login" class="text-decoration-none fw-semibold">Quay lại đăng nhập</a>
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
            radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.12), transparent 30%),
            #f8fafc;
    }
    .auth-card { border-radius: 1.25rem; }
</style>
