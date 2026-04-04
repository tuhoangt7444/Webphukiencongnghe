<?php
$status = trim((string)($status ?? ''));
?>

<style>
.contact-page {
    position: relative;
    overflow: hidden;
    min-height: calc(100vh - 76px);
    background:
        radial-gradient(80rem 60rem at 10% -20%, rgba(34,211,238,.22), transparent 60%),
        radial-gradient(70rem 55rem at 90% -30%, rgba(59,130,246,.28), transparent 62%),
        radial-gradient(64rem 48rem at 52% 118%, rgba(34,197,94,.2), transparent 64%),
        linear-gradient(180deg, #020617 0%, #0b1227 38%, #0a1b3f 100%) !important;
}

.contact-page::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(56,189,248,.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56,189,248,.1) 1px, transparent 1px);
    background-size: 38px 38px;
    opacity: .42;
}

.contact-page::after {
    content: "";
    position: absolute;
    inset: -8% -10% auto -10%;
    height: 70%;
    pointer-events: none;
    background:
        radial-gradient(ellipse at 15% 22%, rgba(34,211,238,.35), transparent 56%),
        radial-gradient(ellipse at 82% 16%, rgba(59,130,246,.3), transparent 54%);
    filter: blur(14px);
    animation: contactAurora 13s ease-in-out infinite alternate;
}

.contact-page > .container {
    position: relative;
    z-index: 1;
}

.contact-banner {
    border: 1px solid rgba(125, 211, 252, .38);
    border-radius: 18px;
    background: linear-gradient(145deg, rgba(30, 64, 175, .34), rgba(14, 165, 233, .24), rgba(2, 132, 199, .2));
    backdrop-filter: blur(10px);
    color: #fff;
    box-shadow: 0 14px 28px rgba(8, 47, 73, .24), inset 0 0 0 1px rgba(224, 242, 254, .08);
}

.contact-card {
    border: 1px solid rgba(125, 211, 252, .3);
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(2, 6, 23, .24), inset 0 0 0 1px rgba(224, 242, 254, .06);
    background: linear-gradient(170deg, rgba(15, 23, 42, .56), rgba(30, 41, 59, .46));
    backdrop-filter: blur(9px);
}

.form-control,
.form-select {
    border-radius: 12px;
    border-color: rgba(125, 211, 252, .34);
    background: rgba(15, 23, 42, .52);
    color: #f8fafc;
}

.form-control::placeholder,
.form-select::placeholder {
    color: #94a3b8;
}

.form-control:focus,
.form-select:focus {
    background: rgba(15, 23, 42, .62);
    color: #fff;
    border-color: rgba(56, 189, 248, .62);
    box-shadow: 0 0 0 4px rgba(56, 189, 248, .18);
}

.btn-contact-submit {
    border-radius: 12px;
    font-weight: 700;
    padding: .7rem 1rem;
    border-color: rgba(56, 189, 248, .55);
    background: linear-gradient(135deg, rgba(14, 165, 233, .9), rgba(37, 99, 235, .86));
}

.contact-page h1,
.contact-page h2,
.contact-page .fw-bold,
.contact-page .fw-semibold {
    color: #f1f5f9;
}

.contact-page p,
.contact-page .text-muted,
.contact-page .small,
.contact-page .form-label,
.contact-page li,
.contact-page a {
    color: #cbd5e1 !important;
}

.contact-page .alert-success,
.contact-page .alert-warning,
.contact-page .alert-danger {
    border-width: 1px;
    backdrop-filter: blur(8px);
}

.contact-page .alert-success {
    background: rgba(5, 150, 105, .16);
    border-color: rgba(16, 185, 129, .45);
    color: #d1fae5;
}

.contact-page .alert-warning {
    background: rgba(217, 119, 6, .16);
    border-color: rgba(245, 158, 11, .45);
    color: #fef3c7;
}

.contact-page .alert-danger {
    background: rgba(185, 28, 28, .16);
    border-color: rgba(239, 68, 68, .45);
    color: #fee2e2;
}

@keyframes contactAurora {
    0% { transform: translate3d(0, 0, 0) scale(1); opacity: .72; }
    100% { transform: translate3d(-2%, 4%, 0) scale(1.05); opacity: .9; }
}
</style>

<section class="contact-page py-4 py-lg-5">
    <div class="container">
        <div class="contact-banner p-4 p-lg-5 mb-4 mb-lg-5">
            <h1 class="display-6 fw-bold mb-2">Liên hệ với chúng tôi</h1>
            <p class="text-white lead mb-4" style="color:rgba(255,255,255,.96) !important;">Nếu bạn có câu hỏi hoặc cần hỗ trợ về đơn hàng, hãy gửi tin nhắn cho chúng tôi.</p>
        </div>

        <?php if ($status === 'sent'): ?>
            <div class="alert alert-success" role="alert">Cảm ơn bạn đã liên hệ với chúng tôi. Chúng tôi sẽ phản hồi sớm.</div>
        <?php elseif ($status === 'invalid'): ?>
            <div class="alert alert-warning" role="alert">Vui lòng nhập đầy đủ thông tin.</div>
        <?php elseif ($status === 'failed'): ?>
            <div class="alert alert-danger" role="alert">Gửi liên hệ thất bại, vui lòng thử lại sau.</div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="contact-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">Thông tin hỗ trợ</h2>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="text-muted small">Email hỗ trợ</div>
                            <div><a class="text-decoration-none" href="mailto:tub2306648@student.ctu.edu.vn">tub2306648@student.ctu.edu.vn</a></div>
                        </li>
                        <li class="mb-3">
                            <div class="text-muted small">Hotline hỗ trợ</div>
                            <div class="fw-semibold">0326754284</div>
                        </li>
                        <li>
                            <div class="text-muted small">Kênh hỗ trợ</div>
                            <div class="fw-semibold">Hỗ trợ trực tuyến qua email và điện thoại</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="contact-card p-4">
                    <h2 class="h5 fw-bold mb-3">Gửi tin nhắn liên hệ</h2>
                    <form method="POST" action="/contact" class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="contactName" class="form-label fw-semibold">Họ và tên</label>
                            <input id="contactName" type="text" name="name" class="form-control" placeholder="Nhập họ và tên" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="contactEmail" class="form-label fw-semibold">Email</label>
                            <input id="contactEmail" type="email" name="email" class="form-control" placeholder="Nhập email" required>
                        </div>
                        <div class="col-12">
                            <label for="contactPhone" class="form-label fw-semibold">Số điện thoại</label>
                            <input id="contactPhone" type="text" name="phone" class="form-control" placeholder="Nhập số điện thoại" required>
                        </div>
                        <div class="col-12">
                            <label for="contactSubject" class="form-label fw-semibold">Tiêu đề</label>
                            <input id="contactSubject" type="text" name="subject" class="form-control" placeholder="Nhập tiêu đề liên hệ" required>
                        </div>
                        <div class="col-12">
                            <label for="contactMessage" class="form-label fw-semibold">Nội dung tin nhắn</label>
                            <textarea id="contactMessage" name="message" rows="5" class="form-control" placeholder="Nhập nội dung bạn cần hỗ trợ" required></textarea>
                        </div>
                        <div class="col-12 d-grid d-sm-block">
                            <button type="submit" class="btn btn-primary btn-contact-submit">Gửi liên hệ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
