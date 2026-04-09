<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $title ?? 'TechGear - Phụ kiện công nghệ chính hãng' ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <!-- Tailwind CDN – layout riêng, không dùng chung với Bootstrap -->
    <script>tailwind={config:{theme:{extend:{colors:{primary:{'50':'#e0f2fe','100':'#b3e5fc','200':'#81d4fa','300':'#4fc3f7','400':'#29b6f6','500':'#03a9f4','600':'#039be5','700':'#0288d1','800':'#0277bd','900':'#01579b'},sea:'#0891b2',mint:'#10b981',slate:{'50':'#f8fafc','100':'#f1f5f9','200':'#e2e8f0','300':'#cbd5e1','400':'#94a3b8','500':'#64748b','600':'#475569','700':'#334155','800':'#1e293b','900':'#0f172a'},storm:'#e2e8f0',ink:'#0f172a'},fontFamily:{sans:['Sora','Inter','ui-sans-serif','sans-serif']}}}}}</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        header {
            z-index: 1300 !important;
        }

        #accountDropdownWrap,
        #accountMenu {
            z-index: 1310;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

<?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $cartCount = 0;
    foreach (($_SESSION['cart'] ?? []) as $cartItem) {
        $cartCount += (int)($cartItem['qty'] ?? 0);
    }
    $isLoggedIn = isset($_SESSION['user_id']);
    $currentUserEmail = (string)($_SESSION['user_email'] ?? '');
    $isAdmin = (string)($_SESSION['user_role_code'] ?? '') === 'admin';
    $emailShort = mb_substr(explode('@', $currentUserEmail)[0], 0, 12);
?>

<header class="sticky top-0 z-50">
    <!-- Topbar -->
    <div class="hidden md:block bg-slate-900 text-slate-300 text-xs py-1.5">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span><i class="fas fa-phone-alt mr-1"></i> 0326754284 &nbsp;&bull;&nbsp; <i class="fas fa-envelope mr-1"></i>tub2306648@student.ctu.edu.vn</span>
            <span>Giao hàng toàn quốc &bull; Bảo hành chính hãng &bull; Đổi trả 7 ngày</span>
        </div>
    </div>

    <!-- Main navbar -->
    <nav class="bg-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center h-14">

                <!-- Logo -->
                <a href="/" class="flex items-center gap-2 flex-shrink-0">
                    <img src="/images/logo.png" alt="TechGear" style="height:38px;width:auto;object-fit:contain">
                    <span class="font-extrabold text-white text-lg tracking-tight">TECH<span class="text-blue-400">GEAR</span></span>
                </a>

                <!-- Desktop nav links (ngay cạnh logo, bên trái) -->
                <ul class="hidden lg:flex items-center gap-1 ml-4">
                    <li><a href="/" class="flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-blue-600/20 transition <?= $currentPath === '/' ? 'text-white bg-blue-600/25 font-semibold' : '' ?>"><i class="fas fa-home mr-1"></i>Trang chủ</a></li>
                    <li><a href="/products" class="flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-blue-600/20 transition <?= str_starts_with($currentPath, '/products') ? 'text-white bg-blue-600/25 font-semibold' : '' ?>"><i class="fas fa-shopping-bag mr-1"></i>Sản phẩm</a></li>
                    <li><a href="/about" class="flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-blue-600/20 transition <?= $currentPath === '/about' ? 'text-white bg-blue-600/25 font-semibold' : '' ?>"><i class="fas fa-circle-info mr-1"></i>Về chúng tôi</a></li>
                    <li><a href="/#home-categories" class="flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-blue-600/20 transition"><i class="fas fa-th-large mr-1"></i>Danh mục</a></li>
                    <li><a href="/contact" class="flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-blue-600/20 transition <?= $currentPath === '/contact' ? 'text-white bg-blue-600/25 font-semibold' : '' ?>"><i class="fas fa-envelope mr-1"></i>Liên hệ</a></li>
                </ul>

                <!-- Right: search + cart + account (đẩy sang phải) -->
                <div class="hidden lg:flex items-center gap-2 ml-auto">
                    <!-- Search -->
                    <form action="/products" method="get" class="flex items-center">
                        <div class="flex items-center rounded-md overflow-hidden" style="border:1px solid #475569">
                            <input type="search" name="q" placeholder="Tìm sản phẩm..."
                                   value="<?= \App\Core\View::e((string)($_GET['q'] ?? '')) ?>"
                                   class="text-sm px-3 py-1.5 outline-none"
                                   style="min-width:160px;background:#f8fafc;color:#1e293b">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 text-sm transition">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Cart -->
                    <a href="/cart" class="relative text-slate-300 hover:text-white border border-slate-600 rounded-lg px-3 py-1.5 text-sm transition hover:bg-slate-700" title="Giỏ hàng">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center border-2 border-slate-800"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Account -->
                    <?php if ($isLoggedIn): ?>
                        <div class="relative" id="accountDropdownWrap">
                            <button onclick="document.getElementById('accountMenu').classList.toggle('hidden')"
                                    class="flex items-center gap-1 text-sm text-slate-300 hover:text-white border border-slate-600 rounded-lg px-3 py-1.5 hover:bg-slate-700 transition">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?= \App\Core\View::e($emailShort) ?>
                                <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>
                            <div id="accountMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-100 z-50 text-sm">
                                <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-t-xl border-b border-slate-100">
                                    <p class="text-xs text-slate-500">Đã đăng nhập</p>
                                    <p class="font-semibold text-slate-800 truncate"><?= \App\Core\View::e($currentUserEmail) ?></p>
                                </div>
                                <a href="/account" class="flex items-center gap-2 px-4 py-2.5 text-slate-700 hover:bg-slate-50 transition"><i class="fas fa-user text-blue-500 w-4"></i>Thông tin tài khoản</a>
                                <a href="/orders/history" class="flex items-center gap-2 px-4 py-2.5 text-slate-700 hover:bg-slate-50 transition"><i class="fas fa-history text-blue-500 w-4"></i>Lịch sử mua hàng</a>
                                <?php if ($isAdmin): ?>
                                    <div class="border-t border-slate-100"></div>
                                    <a href="/admin" class="flex items-center gap-2 px-4 py-2.5 text-red-600 font-semibold hover:bg-red-50 transition"><i class="fas fa-shield-alt w-4"></i>Vào trang Admin</a>
                                <?php endif; ?>
                                <div class="border-t border-slate-100"></div>
                                <a href="/logout" class="flex items-center gap-2 px-4 py-2.5 text-red-500 hover:bg-red-50 transition rounded-b-xl"><i class="fas fa-sign-out-alt w-4"></i>Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="text-sm text-slate-300 hover:text-white border border-slate-600 rounded-lg px-3 py-1.5 hover:bg-slate-700 transition">Đăng nhập</a>
                        <a href="/register" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-3 py-1.5 transition font-medium">Đăng ký</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile hamburger -->
                <button id="mobileMenuBtn" class="lg:hidden text-slate-300 hover:text-white p-2 rounded-md" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden lg:hidden bg-slate-800 border-t border-slate-700 pb-3">
            <div class="max-w-7xl mx-auto px-4 pt-3 space-y-1">
                <a href="/" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700 transition"><i class="fas fa-home w-4"></i>Trang chủ</a>
                <a href="/products" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-white bg-blue-600/25 transition"><i class="fas fa-shopping-bag w-4"></i>Sản phẩm</a>
                <a href="/about" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium <?= $currentPath === '/about' ? 'text-white bg-blue-600/25' : 'text-slate-300 hover:text-white hover:bg-slate-700' ?> transition"><i class="fas fa-circle-info w-4"></i>Về chúng tôi</a>
                <a href="/#home-categories" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700 transition"><i class="fas fa-th-large w-4"></i>Danh mục</a>
                <a href="/contact" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700 transition"><i class="fas fa-envelope w-4"></i>Liên hệ</a>
                <div class="pt-2 border-t border-slate-700">
                    <form action="/products" method="get" class="flex items-center gap-2 mb-2">
                        <input type="search" name="q" placeholder="Tìm sản phẩm..."
                               value="<?= \App\Core\View::e((string)($_GET['q'] ?? '')) ?>"
                               class="flex-1 bg-slate-700 text-white text-sm placeholder-slate-400 px-3 py-2 rounded-lg outline-none border border-slate-600">
                        <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="flex items-center gap-2">
                        <a href="/cart" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm text-slate-300 border border-slate-600 rounded-lg hover:bg-slate-700 transition">
                            <i class="fas fa-shopping-cart"></i>Giỏ hàng
                            <?php if ($cartCount > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold rounded-full px-1.5"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if ($isLoggedIn): ?>
                            <a href="/account" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm text-slate-300 border border-slate-600 rounded-lg hover:bg-slate-700 transition"><i class="fas fa-user-circle"></i><?= \App\Core\View::e($emailShort) ?></a>
                        <?php else: ?>
                            <a href="/login" class="flex-1 text-center px-3 py-2 text-sm text-slate-300 border border-slate-600 rounded-lg hover:bg-slate-700 transition">Đăng nhập</a>
                            <a href="/register" class="flex-1 text-center px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Đăng ký</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<main>
    <?php if(isset($viewFile)) require $viewFile; ?>
</main>

<!-- Footer -->
<footer style="background:#0f172a" class="text-white mt-5 pt-5 pb-3">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Brand + Contact -->
            <div class="lg:col-span-1">
                <div class="flex items-center gap-2 mb-3">
                    <img src="/images/logo.png" alt="TechGear" style="height:36px;width:auto;object-fit:contain">
                    <span class="text-lg font-bold">TechGear</span>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed">
                    Cung cấp phụ kiện công nghệ chính hãng, đa dạng mẫu mã, giá tốt, bảo hành rõ ràng. Giao hàng nhanh toàn quốc.
                </p>
                <ul class="mt-3 space-y-1 text-slate-400 text-sm">
                    <li><i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>Hỗ trợ trực tuyến toàn quốc</li>
                    <li><i class="fas fa-phone-alt mr-2 text-blue-500"></i>0326754284</li>
                    <li><i class="fas fa-envelope mr-2 text-blue-500"></i>tub2306648@student.ctu.edu.vn</li>
                </ul>
                <div class="flex gap-2 mt-4">
                    <a href="https://www.facebook.com/share/1B5GXpFkBM/" target="_blank" class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/10 text-slate-300 hover:bg-blue-600 hover:text-white transition"><i class="fab fa-facebook-f text-sm"></i></a>
                    <a href="https://www.youtube.com/@lehoangtu5692" target="_blank" class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/10 text-slate-300 hover:bg-red-600 hover:text-white transition"><i class="fab fa-youtube text-sm"></i></a>
                    <a href="https://zalo.me/" target="_blank" class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/10 text-slate-300 hover:bg-blue-500 hover:text-white transition"><img src="https://upload.wikimedia.org/wikipedia/commons/9/91/Icon_of_Zalo.svg" width="16" height="16" alt="Zalo"></a>
                </div>
            </div>

            <!-- Danh mục -->
            <div>
                <h5 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-4">Danh mục</h5>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="/products?category=cpu" class="hover:text-white transition">CPU</a></li>
                    <li><a href="/products?category=gpu" class="hover:text-white transition">Card màn hình</a></li>
                    <li><a href="/products?category=ram" class="hover:text-white transition">RAM</a></li>
                    <li><a href="/products?category=mainboard" class="hover:text-white transition">Mainboard</a></li>
                    <li><a href="/products?category=chuot" class="hover:text-white transition">Chuột gaming</a></li>
                    <li><a href="/products?category=ban-phim" class="hover:text-white transition">Bàn phím cơ</a></li>
                </ul>
            </div>

            <!-- Hỗ trợ -->
            <div>
                <h5 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-4">Hỗ trợ</h5>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="/about" class="hover:text-white transition">Về TechGear</a></li>
                    <li><a href="/contact" class="hover:text-white transition">Liên hệ</a></li>
                    <li><a href="#" class="hover:text-white transition">Chính sách đổi trả</a></li>
                    <li><a href="#" class="hover:text-white transition">Chính sách bảo hành</a></li>
                    <li><a href="#" class="hover:text-white transition">Hướng dẫn mua hàng</a></li>
                </ul>
            </div>

            <!-- Nhận ưu đãi -->
            <div>
                <h5 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-4">Nhận ưu đãi</h5>
                <p class="text-slate-400 text-sm mb-3">Đăng ký nhận thông báo khuyến mãi và sản phẩm mới mỗi tuần.</p>
                <form class="flex gap-2 mb-5">
                    <input type="email" placeholder="Email của bạn" class="flex-1 bg-white/10 border border-slate-700 text-white placeholder-slate-500 text-sm rounded-lg px-3 py-2 outline-none focus:border-blue-500">
                    <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-2 rounded-lg transition whitespace-nowrap">Đăng ký</button>
                </form>
                <div class="grid grid-cols-2 gap-2 text-slate-400 text-xs">
                    <div class="flex items-center gap-1.5"><i class="fas fa-truck text-blue-500"></i> Giao hàng toàn quốc</div>
                    <div class="flex items-center gap-1.5"><i class="fas fa-certificate text-blue-500"></i> Hàng chính hãng</div>
                    <div class="flex items-center gap-1.5"><i class="fas fa-undo text-blue-500"></i> Đổi trả 7 ngày</div>
                    <div class="flex items-center gap-1.5"><i class="fas fa-headset text-blue-500"></i> Hỗ trợ 24/7</div>
                </div>
            </div>
        </div>

        <hr class="border-slate-800 mt-10">
        <div class="flex flex-col md:flex-row justify-between items-center text-slate-600 text-xs mt-4 gap-2">
            <span>&copy; <?= date('Y') ?> TechGear. Tất cả quyền được bảo lưu.</span>
            <span>Dự án học tập — Chuyên ngành Công nghệ thông tin</span>
        </div>
    </div>
</footer>

<script>
    // Đóng account dropdown khi click ra ngoài
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('accountDropdownWrap');
        const menu = document.getElementById('accountMenu');
        if (wrap && menu && !wrap.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
</body>
</html>
