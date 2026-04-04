<?php
use App\Core\View;
$status = $status ?? '';
?>

<section class="contact-wrap relative overflow-hidden py-12 lg:py-16">
    <div class="contact-glow contact-glow-a"></div>
    <div class="contact-glow contact-glow-b"></div>

    <div class="container mx-auto px-4 lg:px-10 relative z-10">
        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8 items-stretch">
            <aside class="lg:col-span-2 rounded-2xl border border-cyan-100 bg-white/85 backdrop-blur p-5 lg:p-6 shadow-sm">
                <p class="text-xs tracking-[0.22em] uppercase font-bold text-cyan-700">Contact TechGear</p>
                <h1 class="mt-3 text-2xl lg:text-3xl font-black text-slate-900 leading-tight">Liên hệ được tư vấn phụ kiện và hỗ trợ</h1>
                <p class="mt-4 text-slate-600 leading-relaxed">Đội ngũ TechGear sẽ phản hồi trong vòng 24 giờ. Bạn có thể hỏi về sản phẩm, bảo hành, giao hàng hoặc tư vấn AI.</p>

                <div class="mt-6 space-y-3 text-sm">
                    <div class="info-row">
                        <span class="material-symbols-outlined">Số điện thoại</span>
                        <span>0326754284</span>
                    </div>
                    <div class="info-row">
                        <span class="material-symbols-outlined">Email</span>
                        <span>tub2306648@student.ctu.edu.vn</span>
                    </div>
                    <div class="info-row">
                        <span class="material-symbols-outlined">Địa chỉ</span>
                        <span>TP Cần Thơ, Việt Nam</span>
                    </div>
                </div>
            </aside>

            <div class="lg:col-span-3 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 lg:p-8 shadow-lg">
                <?php if ($status === 'sent'): ?>
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">
                        Gửi liên hệ thành công. TechGear sẽ phản hồi bạn sớm.
                    </div>
                <?php elseif ($status === 'invalid'): ?>
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                        Vui lòng nhập đầy đủ họ tên, email hợp lệ và nội dung.
                    </div>
                <?php endif; ?>

                <form method="POST" action="/contact" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Họ và tên</label>
                            <input type="text" name="name" class="input" placeholder="Nguyễn Văn A" required>
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input type="email" name="email" class="input" placeholder="tub2306648@student.ctu.edu.vn" required>
                        </div>
                    </div>

                    <div>
                        <label class="label">Số điện thoại (tùy chọn)</label>
                        <input type="text" name="phone" class="input" placeholder="09xx xxx xxx">
                    </div>

                    <div>
                        <label class="label">Nội dung</label>
                        <textarea name="message" rows="5" class="input" placeholder="Bạn cần tư vấn sản phẩm nào?" required></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between pt-1">
                        <p class="text-xs text-slate-500">Bảng gửi này đã tối ưu cho laptop và điện thoại.</p>
                        <button type="submit" class="btn-send">
                            <span class="material-symbols-outlined text-base">send</span>
                            Gửi liên hệ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
    .contact-wrap {
        position: relative;
        min-height: calc(100vh - 76px);
        background:
            radial-gradient(80rem 60rem at 10% -20%, rgba(34,211,238,.22), transparent 60%),
            radial-gradient(70rem 55rem at 90% -30%, rgba(59,130,246,.28), transparent 62%),
            radial-gradient(64rem 48rem at 52% 118%, rgba(34,197,94,.2), transparent 64%),
            linear-gradient(180deg, #020617 0%, #0b1227 38%, #0a1b3f 100%) !important;
    }

    .contact-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(56,189,248,.09) 1px, transparent 1px),
            linear-gradient(90deg, rgba(56,189,248,.09) 1px, transparent 1px);
        background-size: 38px 38px;
        opacity: .4;
    }

    .contact-wrap::after {
        content: '';
        position: absolute;
        inset: -8% -10% auto -10%;
        height: 70%;
        pointer-events: none;
        background:
            radial-gradient(ellipse at 15% 22%, rgba(34,211,238,.35), transparent 56%),
            radial-gradient(ellipse at 82% 16%, rgba(59,130,246,.28), transparent 54%);
        filter: blur(14px);
        animation: contactAurora 13s ease-in-out infinite alternate;
    }

    .contact-glow {
        position: absolute;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        filter: blur(70px);
        opacity: .5;
        pointer-events: none;
    }

    .contact-glow-a {
        background: #06b6d4;
        left: -80px;
        top: 20px;
    }

    .contact-glow-b {
        background: #3b82f6;
        right: -80px;
        top: 0;
    }

    .contact-wrap .max-w-5xl > aside,
    .contact-wrap .max-w-5xl > div {
        border-color: rgba(125, 211, 252, .3) !important;
        background: rgba(15, 23, 42, .66) !important;
        backdrop-filter: blur(8px);
    }

    .contact-wrap h1,
    .contact-wrap p,
    .contact-wrap .text-slate-600,
    .contact-wrap .text-slate-500,
    .contact-wrap .text-slate-900,
    .contact-wrap .text-cyan-700 {
        color: #dbeafe !important;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: .55rem;
        color: #dbeafe;
        padding: .55rem .65rem;
        border: 1px solid rgba(125, 211, 252, .3);
        border-radius: .8rem;
        background: rgba(30, 41, 59, .58);
    }

    .label {
        display: block;
        margin-bottom: .4rem;
        font-size: .85rem;
        color: #e2e8f0;
        font-weight: 700;
    }

    .input {
        width: 100%;
        border: 1px solid rgba(125, 211, 252, .4);
        background: rgba(15, 23, 42, .58);
        color: #f8fafc;
        border-radius: .8rem;
        padding: .72rem .85rem;
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .input::placeholder {
        color: #94a3b8;
    }

    .input:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .16);
    }

    .btn-send {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        justify-content: center;
        padding: .72rem 1rem;
        border-radius: .8rem;
        background: linear-gradient(125deg, #0891b2 0%, #2563eb 100%);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 12px 22px rgba(37, 99, 235, .26);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .btn-send:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(37, 99, 235, .32);
    }

    @media (max-width: 640px) {
        .btn-send {
            width: 100%;
        }
    }

    @keyframes contactAurora {
        0% { transform: translate3d(0, 0, 0) scale(1); opacity: .7; }
        100% { transform: translate3d(-2%, 4%, 0) scale(1.05); opacity: .88; }
    }
</style>
