<style>
.about-tech-page {
    position: relative;
    overflow: hidden;
    min-height: calc(100vh - 76px);
    background:
        radial-gradient(80rem 60rem at 10% -20%, rgba(34,211,238,.22), transparent 60%),
        radial-gradient(70rem 55rem at 90% -30%, rgba(59,130,246,.28), transparent 62%),
        radial-gradient(64rem 48rem at 52% 118%, rgba(34,197,94,.2), transparent 64%),
        linear-gradient(180deg, #020617 0%, #0b1227 38%, #0a1b3f 100%) !important;
}

.about-tech-page::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(56,189,248,.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56,189,248,.1) 1px, transparent 1px);
    background-size: 38px 38px;
    mask-image: linear-gradient(180deg, rgba(0,0,0,.75), rgba(0,0,0,.2));
}

.about-tech-page::after {
    content: "";
    position: absolute;
    inset: -8% -10% auto -10%;
    height: 70%;
    pointer-events: none;
    background:
        radial-gradient(ellipse at 15% 22%, rgba(34,211,238,.35), transparent 56%),
        radial-gradient(ellipse at 82% 16%, rgba(59,130,246,.3), transparent 54%);
    filter: blur(14px);
    animation: aboutAurora 13s ease-in-out infinite alternate;
}

.about-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(125, 211, 252, .4);
    border-radius: 20px;
    background: linear-gradient(140deg, rgba(30, 64, 175, .36), rgba(14, 165, 233, .26), rgba(2, 132, 199, .22));
    backdrop-filter: blur(10px);
    color: #fff;
    box-shadow: 0 16px 34px rgba(8, 47, 73, .24), inset 0 0 0 1px rgba(224, 242, 254, .08);
}

.about-hero::before {
    content: "";
    position: absolute;
    width: 340px;
    height: 340px;
    border-radius: 999px;
    right: -120px;
    top: -120px;
    background: radial-gradient(circle, rgba(255,255,255,.35), rgba(255,255,255,0));
}

.about-hero::after {
    content: "";
    position: absolute;
    width: 240px;
    height: 240px;
    border-radius: 999px;
    left: -80px;
    bottom: -120px;
    background: radial-gradient(circle, rgba(125, 211, 252, .35), rgba(125, 211, 252, 0));
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .4rem .72rem;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.3);
    background: rgba(255,255,255,.14);
    font-size: .8rem;
    font-weight: 600;
}

.about-glass,
.reason-card,
.stat-card,
.cta-card {
    border: 1px solid rgba(125, 211, 252, .3);
    border-radius: 16px;
    box-shadow: 0 10px 22px rgba(2, 6, 23, .24), inset 0 0 0 1px rgba(224, 242, 254, .06);
    background: rgba(15, 23, 42, .5);
    backdrop-filter: blur(9px);
}

.about-glass {
    background: linear-gradient(170deg, rgba(15, 23, 42, .58), rgba(30, 41, 59, .48));
}

.reason-card {
    transition: transform .24s ease, box-shadow .24s ease, border-color .24s ease;
}

.reason-card:hover {
    transform: translateY(-5px);
    border-color: rgba(56, 189, 248, .52);
    box-shadow: 0 18px 32px rgba(8, 47, 73, .34);
}

.reason-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    background: linear-gradient(145deg, rgba(14, 165, 233, .3), rgba(37, 99, 235, .24));
    color: #bae6fd;
    box-shadow: inset 0 1px 1px rgba(255,255,255,.25), 0 8px 16px rgba(2, 132, 199, .2);
}

.stat-card {
    background: linear-gradient(170deg, rgba(15, 23, 42, .56), rgba(30, 41, 59, .46));
    transition: transform .24s ease, box-shadow .24s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 28px rgba(8, 47, 73, .32);
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #67e8f9;
    line-height: 1;
}

.cta-card {
    background: linear-gradient(170deg, rgba(15, 23, 42, .56), rgba(30, 41, 59, .46));
    position: relative;
    overflow: hidden;
}

.cta-card::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(120deg, rgba(29,78,216,.06), rgba(14,165,233,.02));
}

.about-tech-page h1,
.about-tech-page h2,
.about-tech-page h3,
.about-tech-page .fw-bold,
.about-tech-page .fw-semibold {
    color: #f1f5f9;
}

.about-tech-page .text-secondary,
.about-tech-page .text-muted,
.about-tech-page p,
.about-tech-page .small {
    color: #cbd5e1 !important;
}

.about-tech-page .btn-light {
    border-color: rgba(125, 211, 252, .4);
    background: rgba(224, 242, 254, .16);
    color: #e0f2fe;
}

.about-tech-page .btn-light:hover {
    background: rgba(125, 211, 252, .24);
    color: #f8fafc;
}

.about-tech-page .btn-primary {
    border-color: rgba(56, 189, 248, .55);
    background: linear-gradient(135deg, rgba(14, 165, 233, .85), rgba(37, 99, 235, .82));
}

@media (max-width: 575.98px) {
    .stat-number {
        font-size: 1.7rem;
    }
}

@keyframes aboutAurora {
    0% { transform: translate3d(0, 0, 0) scale(1); opacity: .72; }
    100% { transform: translate3d(-2%, 4%, 0) scale(1.05); opacity: .9; }
}
</style>

<section class="about-tech-page py-4 py-lg-5">
    <div class="container">
        <div class="about-hero p-4 p-md-5 mb-4 mb-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-8">
                    <h1 class="display-6 fw-bold mb-3">Về chúng tôi</h1>
                    <p class="text-white lead mb-4" style="color:rgba(255,255,255,.96) !important;">Chúng tôi cung cấp phụ kiện công nghệ chất lượng với giá tốt và dịch vụ chuyên nghiệp.</p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="hero-chip"><i class="fa-solid fa-bolt"></i>Mua sắm trực tuyến nhanh</span>
                        <span class="hero-chip"><i class="fa-solid fa-shield-halved"></i>Cam kết sản phẩm chất lượng</span>
                        <span class="hero-chip"><i class="fa-solid fa-headset"></i>Hỗ trợ tận tâm</span>
                    </div>
                    <a href="/products" class="btn btn-light btn-lg px-4 fw-semibold">Khám phá sản phẩm</a>
                </div>
                <div class="col-12 col-lg-4 text-lg-end">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill" style="background: rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28);">
                        <i class="fa-solid fa-microchip"></i>
                        <span class="fw-semibold">Phong cách công nghệ hiện đại</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4 mb-lg-5">
            <div class="col-12 col-lg-6">
                <div class="about-glass p-4 h-100">
                    <h2 class="h4 fw-bold mb-3">Giới thiệu website</h2>
                    <p class="text-secondary mb-0">TechGear là website bán phụ kiện công nghệ trực tuyến, cung cấp các sản phẩm như chuột, bàn phím, tai nghe, SSD, RAM và nhiều thiết bị công nghệ khác.</p>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="about-glass p-4 h-100">
                    <h2 class="h4 fw-bold mb-3">Sứ mệnh</h2>
                    <p class="text-secondary mb-3">Cung cấp sản phẩm công nghệ chất lượng với giá hợp lý và dịch vụ tốt.</p>
                    <h3 class="h5 fw-bold mb-2">Tầm nhìn</h3>
                    <p class="text-secondary mb-0">Trở thành nền tảng bán phụ kiện công nghệ uy tín.</p>
                </div>
            </div>
        </div>

        <div class="mb-4 mb-lg-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 fw-bold mb-0">Lý do chọn chúng tôi</h2>
                <span class="text-muted small">Cam kết trải nghiệm mua sắm trực tuyến</span>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="reason-card p-3 h-100">
                        <span class="reason-icon mb-3"><i class="fa-solid fa-circle-check"></i></span>
                        <h3 class="h6 fw-bold">Sản phẩm chính hãng</h3>
                        <p class="text-secondary small mb-0">Nguồn hàng rõ ràng, minh bạch và đảm bảo chất lượng.</p>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="reason-card p-3 h-100">
                        <span class="reason-icon mb-3"><i class="fa-solid fa-tags"></i></span>
                        <h3 class="h6 fw-bold">Giá cả cạnh tranh</h3>
                        <p class="text-secondary small mb-0">Mức giá hợp lý cùng nhiều chương trình ưu đãi hấp dẫn.</p>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="reason-card p-3 h-100">
                        <span class="reason-icon mb-3"><i class="fa-solid fa-truck-fast"></i></span>
                        <h3 class="h6 fw-bold">Giao hàng nhanh</h3>
                        <p class="text-secondary small mb-0">Xử lý đơn hàng nhanh chóng, giao hàng toàn quốc.</p>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="reason-card p-3 h-100">
                        <span class="reason-icon mb-3"><i class="fa-solid fa-headset"></i></span>
                        <h3 class="h6 fw-bold">Hỗ trợ khách hàng tận tâm</h3>
                        <p class="text-secondary small mb-0">Đội ngũ hỗ trợ sẵn sàng giải đáp và đồng hành cùng bạn.</p>
                    </article>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 mb-lg-5 text-center">
            <div class="col-12 col-md-4">
                <div class="stat-card p-4 h-100">
                    <div class="stat-number">500+</div>
                    <div class="text-muted">Sản phẩm</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card p-4 h-100">
                    <div class="stat-number">2000+</div>
                    <div class="text-muted">Khách hàng</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card p-4 h-100">
                    <div class="stat-number">3000+</div>
                    <div class="text-muted">Đơn hàng</div>
                </div>
            </div>
        </div>

        <div class="cta-card p-4 p-md-5 text-center">
            <h2 class="h4 fw-bold mb-2">Sẵn sàng nâng cấp góc máy của bạn?</h2>
            <p class="text-muted mb-3">Khám phá ngay danh mục phụ kiện công nghệ chất lượng tại TechGear.</p>
            <a href="/products" class="btn btn-primary btn-lg px-4">Xem sản phẩm</a>
        </div>
    </div>
</section>
