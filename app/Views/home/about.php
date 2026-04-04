<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

<section class="about-wrap relative overflow-hidden">
    <div class="aurora aurora-a"></div>
    <div class="aurora aurora-b"></div>

    <div class="container mx-auto px-4 lg:px-10 py-16 lg:py-20 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7 reveal-up">
                <p class="eyebrow">TechGear Story</p>
                <h1 class="hero-title mt-4">
                    Chung toi xay dung mot khong gian phu kien cong nghe
                    <span class="hero-accent">dep, thong minh va de su dung</span>
                </h1>
                <p class="hero-copy mt-6">
                    Tu mot do an nho trong lop hoc, TechGear da phat trien thanh website thuong mai dien tu
                    toi uu cho tra cuu nhanh, trinh bay dep mat va trai nghiem mua sam tron ven.
                    Chung toi tap trung vao toc do, tinh ro rang va su tien loi cho nguoi dung Viet Nam.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <span class="pill">UI/UX ro rang</span>
                    <span class="pill">Danh muc thong minh</span>
                    <span class="pill">Quan tri de mo rong</span>
                    <span class="pill">Toi uu mobile</span>
                </div>

                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="/products" class="btn-main">Kham pha san pham</a>
                    <a href="/admin" class="btn-ghost">Quan tri he thong</a>
                </div>
            </div>

            <div class="lg:col-span-5 reveal-right">
                <div class="glass-card">
                    <div class="stat-grid">
                        <article class="stat-item">
                            <p class="stat-num">1000+</p>
                            <p class="stat-label">Luot xem moi ngay</p>
                        </article>
                        <article class="stat-item">
                            <p class="stat-num">98%</p>
                            <p class="stat-label">Nguoi dung hai long</p>
                        </article>
                        <article class="stat-item">
                            <p class="stat-num">24/7</p>
                            <p class="stat-label">San sang mo rong</p>
                        </article>
                        <article class="stat-item">
                            <p class="stat-num">A+</p>
                            <p class="stat-label">Hieu nang hien thi</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 lg:mt-20 reveal-up">
            <div class="timeline">
                <div class="timeline-item">
                    <p class="t-year">2024</p>
                    <h3 class="t-title">Khoi dau du an</h3>
                    <p class="t-copy">Xay dung kien truc MVC va dat nen mong cho website ban phu kien.</p>
                </div>
                <div class="timeline-item">
                    <p class="t-year">2025</p>
                    <h3 class="t-title">Nang cap trai nghiem</h3>
                    <p class="t-copy">Toi uu trang san pham, bo loc va giao dien quan tri de thao tac nhanh hon.</p>
                </div>
                <div class="timeline-item">
                    <p class="t-year">2026</p>
                    <h3 class="t-title">Dinh huong AI</h3>
                    <p class="t-copy">Huong toi goi y thong minh va phan tich hanh vi de ca nhan hoa mua sam.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .about-wrap {
        position: relative;
        min-height: calc(100vh - 76px);
        background:
            radial-gradient(80rem 60rem at 10% -20%, rgba(34,211,238,.22), transparent 60%),
            radial-gradient(70rem 55rem at 90% -30%, rgba(59,130,246,.28), transparent 62%),
            radial-gradient(64rem 48rem at 52% 118%, rgba(34,197,94,.2), transparent 64%),
            linear-gradient(180deg, #020617 0%, #0b1227 38%, #0a1b3f 100%) !important;
    }

    .about-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(56,189,248,.09) 1px, transparent 1px),
            linear-gradient(90deg, rgba(56,189,248,.09) 1px, transparent 1px);
        background-size: 38px 38px;
        opacity: .42;
    }

    .about-wrap::after {
        content: '';
        position: absolute;
        inset: -8% -10% auto -10%;
        height: 70%;
        pointer-events: none;
        background:
            radial-gradient(ellipse at 15% 22%, rgba(34,211,238,.36), transparent 56%),
            radial-gradient(ellipse at 82% 16%, rgba(59,130,246,.3), transparent 54%);
        filter: blur(14px);
        animation: aboutAurora 13s ease-in-out infinite alternate;
    }

    .about-wrap * {
        font-family: 'Be Vietnam Pro', sans-serif;
    }

    .hero-title,
    .stat-num,
    .t-year {
        font-family: 'Space Grotesk', sans-serif;
    }

    .aurora {
        position: absolute;
        width: 420px;
        height: 420px;
        border-radius: 999px;
        filter: blur(65px);
        opacity: .45;
        pointer-events: none;
        animation: floaty 10s ease-in-out infinite;
    }

    .aurora-a {
        background: #22d3ee;
        top: -80px;
        left: -80px;
    }

    .aurora-b {
        background: #3b82f6;
        right: -120px;
        top: 40px;
        animation-delay: -3s;
    }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: rgba(15, 23, 42, .58);
        border: 1px solid rgba(125, 211, 252, .45);
        color: #7dd3fc;
        padding: .45rem .9rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .hero-title {
        color: #f8fbff;
        font-size: clamp(2rem, 4vw, 3.7rem);
        line-height: 1.02;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .hero-accent {
        display: block;
        margin-top: .35rem;
        color: #67e8f9;
    }

    .hero-copy {
        max-width: 62ch;
        color: #cbd5e1;
        line-height: 1.8;
        font-size: 1.03rem;
    }

    .pill {
        border: 1px solid rgba(125, 211, 252, .36);
        background: rgba(15, 23, 42, .62);
        color: #dbeafe;
        font-size: .85rem;
        padding: .45rem .8rem;
        border-radius: 999px;
        font-weight: 600;
    }

    .btn-main,
    .btn-ghost {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .8rem 1.2rem;
        border-radius: .9rem;
        font-weight: 700;
        transition: transform .25s ease, box-shadow .25s ease, background-color .25s ease;
    }

    .btn-main {
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(37, 99, 235, .28);
    }

    .btn-main:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #0284c7, #1d4ed8);
    }

    .btn-ghost {
        border: 1px solid rgba(125, 211, 252, .4);
        color: #dbeafe;
        background: rgba(15, 23, 42, .5);
    }

    .btn-ghost:hover {
        transform: translateY(-2px);
        border-color: rgba(125, 211, 252, .82);
    }

    .glass-card {
        border: 1px solid rgba(125, 211, 252, 0.3);
        background: rgba(15, 23, 42, 0.66);
        backdrop-filter: blur(8px);
        border-radius: 1.2rem;
        padding: 1rem;
        box-shadow: 0 16px 40px rgba(2, 6, 23, 0.35);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .9rem;
    }

    .stat-item {
        border-radius: .9rem;
        border: 1px solid rgba(125, 211, 252, .24);
        background: rgba(30, 41, 59, .6);
        padding: 1rem;
    }

    .stat-num {
        color: #67e8f9;
        font-size: clamp(1.3rem, 3.2vw, 1.9rem);
        font-weight: 700;
        line-height: 1;
    }

    .stat-label {
        margin-top: .45rem;
        color: #cbd5e1;
        font-size: .85rem;
    }

    .timeline {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .timeline-item {
        background: rgba(15, 23, 42, .62);
        border: 1px solid rgba(125, 211, 252, .28);
        border-radius: 1rem;
        padding: 1.1rem;
        box-shadow: 0 10px 20px rgba(2, 6, 23, 0.3);
    }

    .t-year {
        color: #22d3ee;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .t-title {
        margin-top: .35rem;
        color: #f1f5f9;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .t-copy {
        margin-top: .45rem;
        color: #cbd5e1;
        line-height: 1.65;
    }

    .reveal-up {
        animation: revealUp .7s ease both;
    }

    .reveal-right {
        animation: revealRight .8s ease both;
    }

    @media (max-width: 1024px) {
        .timeline {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .stat-grid {
            grid-template-columns: 1fr;
        }

        .btn-main,
        .btn-ghost {
            width: 100%;
        }
    }

    @keyframes floaty {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-20px);
        }
    }

    @keyframes revealUp {
        from {
            opacity: 0;
            transform: translateY(18px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes revealRight {
        from {
            opacity: 0;
            transform: translateX(18px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes aboutAurora {
        0% { transform: translate3d(0, 0, 0) scale(1); opacity: .72; }
        100% { transform: translate3d(-2%, 4%, 0) scale(1.05); opacity: .9; }
    }
</style>
