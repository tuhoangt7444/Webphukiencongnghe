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
                        <h2 class="fw-bold mb-2">Đặt lại mật khẩu</h2>
                        <p class="text-muted mb-4">Nhập OTP 6 số đã gửi về email và mật khẩu mới.</p>

                        <?php if ($status === 'otp-sent'): ?>
                            <div class="alert alert-success">Đã gửi OTP về email của bạn. Mã có hiệu lực trong 10 phút.</div>
                        <?php elseif ($status === 'otp-invalid'): ?>
                            <div class="alert alert-warning">OTP không đúng. Vui lòng kiểm tra lại.</div>
                        <?php elseif ($status === 'otp-expired'): ?>
                            <div class="alert alert-warning">OTP đã hết hạn. Vui lòng gửi lại mã mới.</div>
                        <?php elseif ($status === 'otp-too-many-attempts'): ?>
                            <div class="alert alert-danger">Bạn đã nhập sai quá số lần cho phép. Vui lòng yêu cầu OTP mới.</div>
                        <?php elseif ($status === 'otp-not-found' || $status === 'otp-used'): ?>
                            <div class="alert alert-warning">OTP không còn hiệu lực. Vui lòng gửi lại mã mới.</div>
                        <?php elseif ($status === 'password-invalid'): ?>
                            <div class="alert alert-warning">Mật khẩu mới chưa hợp lệ hoặc xác nhận mật khẩu không khớp.</div>
                        <?php elseif ($status === 'request-failed'): ?>
                            <div class="alert alert-danger">Không thể xử lý yêu cầu lúc này. Vui lòng thử lại.</div>
                        <?php endif; ?>

                        <form id="resetPasswordForm" method="POST" action="/forgot-password/verify" class="row g-3">
                            <?= \App\Core\View::csrf_field() ?>
                            <input type="hidden" id="emailHidden" name="email" value="<?= \App\Core\View::e($email) ?>">
                            
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <input id="email" type="email" name="emailDisplay" class="form-control form-control-lg" value="<?= \App\Core\View::e($email) ?>" required disabled readonly>
                                <small class="text-muted d-block mt-2">Không thể thay đổi email. <a href="/forgot-password">Gửi OTP cho email khác</a></small>
                            </div>
                            
                            <!-- OTP Section (Always Visible) -->
                            <div class="col-12">
                                <label for="otp" class="form-label fw-semibold">Mã OTP (6 số)</label>
                                <input id="otp" type="text" name="otp" class="form-control form-control-lg" inputmode="numeric" maxlength="6" placeholder="123456" required>
                                <small class="text-muted d-block mt-2">Chúng tôi sẽ gửi một mã từ mục "Không thư nào" hoặc "Quảng cáo"</small>
                            </div>
                            <div class="col-12">
                                <button type="button" id="verifyOtpBtn" class="btn btn-primary btn-lg fw-semibold w-100">Xác nhận OTP</button>
                                <div id="otpMessage"></div>
                            </div>

                            <!-- Password Section (Hidden Initially) -->
                            <div id="passwordSection" class="d-none w-100">
                                <div class="col-12">
                                    <hr class="my-3">
                                </div>
                                <div class="col-12">
                                    <p class="text-success mb-3"><i class="fas fa-check-circle"></i> OTP đúng! Vui lòng nhập mật khẩu mới.</p>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-semibold">Mật khẩu mới</label>
                                    <input id="password" type="password" name="password" class="form-control form-control-lg" minlength="6" placeholder="Tối thiểu 6 ký tự">
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirm" class="form-label fw-semibold">Nhập lại mật khẩu</label>
                                    <input id="password_confirm" type="password" name="password_confirm" class="form-control form-control-lg" minlength="6" placeholder="Nhập lại mật khẩu">
                                </div>
                                <div class="col-12 d-grid mt-2">
                                    <button type="submit" id="submitBtn" class="btn btn-success btn-lg fw-semibold">Cập nhật mật khẩu</button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-3 d-flex flex-wrap gap-3 small">
                            <a href="/forgot-password<?= $email !== '' ? '?email=' . rawurlencode($email) : '' ?>" class="text-decoration-none fw-semibold">Gửi lại OTP</a>
                            <a href="/login" class="text-decoration-none fw-semibold">Quay lại đăng nhập</a>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.getElementById('email');
    const otpInput = document.getElementById('otp');
    const verifyOtpBtn = document.getElementById('verifyOtpBtn');
    const otpMessage = document.getElementById('otpMessage');
    const passwordSection = document.getElementById('passwordSection');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const submitBtn = document.getElementById('submitBtn');
    const resetPasswordForm = document.getElementById('resetPasswordForm');

    // Handle OTP Verification
    verifyOtpBtn.addEventListener('click', async function () {
        const email = document.getElementById('emailHidden').value.trim();
        const otp = otpInput.value.trim();

        // Clear previous messages
        otpMessage.innerHTML = '';
        otpMessage.className = '';

        // Validate inputs
        if (!email) {
            showOtpMessage('Email không được trống. Hãy gửi lại OTP cho email khác.', 'danger');
            return;
        }

        if (!otp) {
            showOtpMessage('Vui lòng nhập OTP', 'danger');
            return;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showOtpMessage('Email không hợp lệ', 'danger');
            return;
        }

        if (otp.length !== 6 || !/^\d+$/.test(otp)) {
            showOtpMessage('OTP phải là 6 số', 'danger');
            return;
        }

        // Disable button and show loading
        verifyOtpBtn.disabled = true;
        verifyOtpBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xác nhận...';

        // Get CSRF token from meta tag or input field
        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                        document.querySelector('input[name="csrf_token"]')?.value || '';
        
        const payload = { email, otp };
        
        console.log('=== OTP Verification Request ===');
        console.log('Email:', email);
        console.log('Email length:', email.length);
        console.log('Email charCodes:', [...email].map(c => c + '(' + c.charCodeAt(0) + ')').join(' '));
        console.log('OTP:', otp);
        console.log('CSRF Token:', csrfToken ? 'Present' : 'Missing');
        console.log('Payload:', JSON.stringify(payload));

        try {
            const response = await fetch('/forgot-password/verify-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ email, otp })
            });

            console.log('=== OTP Verification Response ===');
            console.log('Response status:', response.status);

            const responseText = await response.text();
            console.log('Response text:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Parsed data:', data);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', responseText);
                
                // Check if it's an HTML error page
                if (responseText.includes('<html') || responseText.includes('<!DOCTYPE')) {
                    showOtpMessage('Lỗi máy chủ. Vui lòng kiểm tra trang web.', 'danger');
                } else {
                    showOtpMessage('Lỗi: ' + responseText, 'danger');
                }
                
                verifyOtpBtn.disabled = false;
                verifyOtpBtn.innerHTML = 'Xác nhận OTP';
                return;
            }

            console.log('data.ok:', data.ok);
            
            if (data.ok === true) {
                console.log('✓ OTP verification succeeded');
                // OTP is correct - show password section and hide OTP verification button
                showOtpMessage('✓ OTP đúng! Vui lòng nhập mật khẩu mới.', 'success');
                
                // Add required attribute to password fields
                passwordInput.required = true;
                passwordConfirmInput.required = true;
                
                // Hide OTP section and show password section
                setTimeout(() => {
                    otpInput.readOnly = true;
                    otpInput.classList.add('bg-light');
                    verifyOtpBtn.classList.add('d-none');
                    passwordSection.classList.remove('d-none');
                    passwordInput.focus();
                }, 300);
            } else {
                console.log('✗ OTP verification failed:', data.error);
                // OTP is incorrect
                const errorMessages = {
                    'otp-invalid': 'OTP không đúng. Vui lòng kiểm tra lại.',
                    'otp-expired': 'OTP đã hết hạn. Vui lòng gửi lại mã mới.',
                    'otp-too-many-attempts': 'Bạn đã nhập sai quá số lần. Vui lòng gửi lại OTP mới.',
                    'otp-not-found': 'OTP không tồn tại. Vui lòng gửi lại mã mới.',
                    'otp-used': 'OTP này đã được sử dụng. Vui lòng gửi lại mã mới.',
                    'email-invalid': 'OTP không hợp lệ.',
                    'server-error': 'Lỗi máy chủ. Vui lòng kiểm tra logs để biết thêm chi tiết.',
                };
                
                const error = data.error || 'unknown';
                const message = errorMessages[error] || 'Xác nhận OTP thất bại. Vui lòng thử lại.';
                showOtpMessage(message, 'danger');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showOtpMessage('Lỗi kết nối. Vui lòng thử lại.', 'danger');
        } finally {
            // Re-enable button
            verifyOtpBtn.disabled = false;
            verifyOtpBtn.innerHTML = 'Xác nhận OTP';
        }
    });

    // Allow Enter key to verify OTP
    otpInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            verifyOtpBtn.click();
        }
    });

    // Handle form submission for password update
    resetPasswordForm.addEventListener('submit', function (e) {
        const password = passwordInput.value;
        const passwordConfirm = passwordConfirmInput.value;

        if (!password || !passwordConfirm) {
            e.preventDefault();
            showPasswordMessage('Vui lòng nhập mật khẩu', 'danger');
            return;
        }

        if (password.length < 6) {
            e.preventDefault();
            showPasswordMessage('Mật khẩu phải có ít nhất 6 ký tự', 'danger');
            return;
        }

        if (password !== passwordConfirm) {
            e.preventDefault();
            showPasswordMessage('Mật khẩu không khớp', 'danger');
            return;
        }
    });

    function showOtpMessage(message, type) {
        otpMessage.innerHTML = `<div class="alert alert-${type} mt-2 mb-0">${message}</div>`;
    }

    function showPasswordMessage(message, type) {
        const passwordMsg = document.createElement('div');
        passwordMsg.className = `alert alert-${type} mt-2`;
        passwordMsg.textContent = message;
        
        const existingMsg = passwordSection.querySelector('.alert');
        if (existingMsg) {
            existingMsg.remove();
        }
        
        passwordSection.insertBefore(passwordMsg, passwordSection.firstChild);
    }
});
</script>
