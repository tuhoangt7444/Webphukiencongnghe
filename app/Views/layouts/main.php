<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $title ?? 'TechGear - Phụ kiện công nghệ chính hãng' ?></title>
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts: Inter, Sora, Be Vietnam Pro -->
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="/css/site-theme.css"/>
    <link rel="stylesheet" href="/css/chatbox.css"/>
    <style>
        body { font-family: 'Inter', 'Be Vietnam Pro', 'Sora', 'Segoe UI', Tahoma, sans-serif; background-color: #f8f9fa; }

        /* Header */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 1300;
        }

        .site-header .topbar,
        .site-header .main-navbar {
            position: relative;
            z-index: 1300;
        }

        /* Topbar */
        .topbar { font-size: 0.8rem; background: #0f172a; padding: 0.35rem 0 !important; }
        .topbar span { display: inline-flex; align-items: center; gap: 0.25rem; }
        @media (max-width: 991.98px) {
            .topbar { font-size: 0.7rem; }
            .topbar span:last-child { display: none; }
        }

        /* Navbar */
        .brand-name { font-weight: 800; letter-spacing: -0.5px; font-size: 1.1rem; white-space: nowrap; }
        .main-navbar { background: #1e293b !important; padding: 0.5rem 0; }
        .main-navbar .container { padding: 0 0.5rem; }
        .navbar-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 0.15rem; }
        .main-navbar .nav-link {
            font-weight: 500; font-size: 0.85rem;
            color: rgba(255,255,255,.82) !important;
            border-radius: 6px;
            transition: background 0.18s, color 0.18s;
            padding: 0.35rem 0.45rem !important;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .main-navbar .nav-link:hover,
        .main-navbar .nav-link.active { color: #fff !important; background: rgba(59,130,246,0.22); }
        .main-navbar .nav-link.active { font-weight: 600; }
        .cart-count { font-size: 10px; min-width: 18px; height: 18px; }
        .account-dropdown-menu { font-size: 0.875rem; min-width: 220px; }
        .account-dropdown-menu { z-index: 1310; }
        .account-dropdown-menu .dropdown-item { padding: 0.5rem 0.75rem; }
        .dd-header { background: linear-gradient(120deg,rgba(6,182,212,.1),rgba(37,99,235,.1)); border-radius: 8px; padding: 0.5rem 0.65rem; margin-bottom: 0.35rem; }
        
        /* Search input responsive */
        .main-navbar .input-group { min-width: auto; }
        .main-navbar .input-group input { min-width: 120px; max-width: 180px; }
        .navbar-collapse { gap: 0.35rem; }
        .navbar-actions { flex-wrap: wrap; }
        .desktop-search .form-control {
            height: 38px;
            min-width: 200px;
            border-radius: 11px 0 0 11px;
            border: 1px solid rgba(125, 211, 252, 0.55);
            background: rgba(15, 23, 42, 0.35);
            color: #e2e8f0;
        }
        .desktop-search .form-control::placeholder { color: rgba(226, 232, 240, 0.72); }
        .desktop-search .form-control:focus {
            background: rgba(15, 23, 42, 0.55);
            color: #fff;
            border-color: rgba(186, 230, 253, 0.9);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .desktop-search .btn {
            border-radius: 0 11px 11px 0;
            border: 1px solid rgba(125, 211, 252, 0.55);
            border-left: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.72), rgba(14, 165, 233, 0.72));
            color: #fff;
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.26);
        }
        .desktop-search .btn:hover { background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(14, 165, 233, 0.9)); }
        .desktop-control-btn {
            height: 38px;
            border-radius: 11px;
            border: 1px solid rgba(125, 211, 252, 0.55) !important;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.42), rgba(14, 165, 233, 0.42));
            color: #e2e8f0 !important;
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.24);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }
        .desktop-control-btn:hover,
        .desktop-control-btn:focus {
            transform: translateY(-1px);
            border-color: rgba(186, 230, 253, 0.9) !important;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.62), rgba(14, 165, 233, 0.62));
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(2, 132, 199, 0.34);
        }
        .desktop-control-btn i { line-height: 1; }
        .desktop-user-toggle::after { display: none !important; }
        .header-avatar {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.65);
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
        }
        .mobile-control-btn .header-avatar { width: 22px; height: 22px; }
        .desktop-auth-btn { padding: 0.42rem 0.75rem; }
        .navbar-mobile-controls { margin-left: auto; }
        .navbar-mobile-controls .navbar-toggler { margin-right: 0.3rem; margin-left: 0; }
        .mobile-control-btn {
            width: 38px;
            height: 38px;
            padding: 0 !important;
            border: 1px solid rgba(125, 211, 252, 0.55) !important;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.42), rgba(14, 165, 233, 0.42));
            color: #e2e8f0 !important;
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.28);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }
        .mobile-control-btn:hover,
        .mobile-control-btn:focus {
            transform: translateY(-1px);
            border-color: rgba(186, 230, 253, 0.9) !important;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.62), rgba(14, 165, 233, 0.62));
            box-shadow: 0 10px 22px rgba(2, 132, 199, 0.36);
            color: #fff !important;
        }
        .mobile-control-btn i { font-size: 0.95rem; line-height: 1; }
        .mobile-user-toggle::after { display: none !important; }
        
        @media (max-width: 991.98px) {
            .brand-name { font-size: 0.95rem; }
            .main-navbar .nav-link { font-size: 0.78rem; padding: 0.3rem 0.35rem !important; }
            .main-navbar .input-group input { min-width: 90px; max-width: 130px; font-size: 0.8rem; }
            .main-navbar .btn-sm { padding: 0.3rem 0.45rem; font-size: 0.78rem; }
            .account-dropdown-menu { min-width: 180px; font-size: 0.8rem; }
            .account-dropdown-menu .dropdown-item { padding: 0.4rem 0.6rem; }
            .navbar-collapse { padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
            .navbar-actions { width: 100%; justify-content: flex-start; }
        }
        
        @media (max-width: 576px) {
            .brand-name { font-size: 0.85rem; }
            .navbar-mobile-controls { gap: 0.25rem; }
            .navbar-mobile-controls .navbar-toggler { margin-right: 0.2rem; }
            .mobile-control-btn { width: 34px; height: 34px; border-radius: 10px; }
            .mobile-control-btn i { font-size: 0.88rem; }
            .brand-name .text-primary { display: none; }
            .main-navbar .navbar-brand { gap: 0.35rem; }
            .main-navbar .navbar-brand img { height: 32px; }
            .main-navbar .nav-link { font-size: 0.7rem; padding: 0.25rem 0.25rem !important; gap: 0.2rem; }
            .main-navbar .nav-link i { font-size: 0.8rem; }
            .main-navbar .input-group { display: none; }
            .main-navbar .btn-sm { padding: 0.25rem 0.35rem; font-size: 0.7rem; }
            .cart-count { font-size: 8px; min-width: 16px; height: 16px; }
            .account-dropdown-menu { min-width: 150px; font-size: 0.75rem; right: -10px; }
            .account-dropdown-menu .dropdown-item { padding: 0.35rem 0.5rem; }
            .dd-header { padding: 0.4rem 0.5rem; }
            .dd-header div:first-child { font-size: 0.65rem; }
            .dd-header div:last-child { font-size: 0.7rem; }
            .navbar-nav { gap: 0.25rem; }
            .navbar-collapse > * { gap: 0.25rem; }
            .navbar-actions { width: auto; gap: 0.35rem; margin-left: auto; }
        }

        /* Footer */
        .site-footer { background: #0f172a; }
        .footer-heading { color: #64748b; font-size: 0.72rem; letter-spacing: 0.18em; text-transform: uppercase; font-weight: 700; margin-bottom: 0.85rem; }
        .site-footer a { color: #94a3b8; text-decoration: none; transition: color 0.18s; }
        .site-footer a:hover { color: #fff; }
        .footer-social a {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,0.08); color: #e2e8f0;
            transition: background 0.2s, transform 0.2s; text-decoration: none;
        }
        .footer-social a:hover { background: #2563eb; color: #fff; transform: translateY(-2px); }
        .policy-chip { display: flex; align-items: center; gap: 0.45rem; color: #94a3b8; font-size: 0.82rem; }
        .policy-chip i { color: #3b82f6; font-size: 0.85rem; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <?php
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $newsletterStatus = trim((string)($_GET['newsletter'] ?? ''));
        $cartCount = 0;
        foreach (($_SESSION['cart'] ?? []) as $cartItem) {
            $cartCount += (int)($cartItem['qty'] ?? 0);
        }
        $isLoggedIn = isset($_SESSION['user_id']);
        $currentUserEmail = (string)($_SESSION['user_email'] ?? '');
        $roleCode = (string)($_SESSION['user_role_code'] ?? '');
        $canAccessAdmin = $isLoggedIn && $roleCode !== '' && $roleCode !== 'customer';
        $currentUserAvatar = trim((string)($_SESSION['user_avatar'] ?? ''));
        $avatarFallback = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" rx="60" fill="#dbeafe"/><circle cx="60" cy="46" r="22" fill="#93c5fd"/><path d="M20 106c8-20 24-30 40-30s32 10 40 30" fill="#93c5fd"/></svg>');
        $renderAvatarUrl = $currentUserAvatar !== '' ? $currentUserAvatar : $avatarFallback;
    ?>

    <header class="site-header no-print">
        <!-- Topbar -->
        <div class="topbar text-white py-1 d-none d-md-block">
            <div class="container d-flex justify-content-between align-items-center">
                <span><i class="fas fa-phone-alt me-1"></i> 0326754284 &nbsp;&bull;&nbsp; <i class="fas fa-envelope me-1"></i>tub2306648@student.ctu.edu.vn</span>
                <span>Giao hàng toàn quốc &bull; Bảo hành chính hãng &bull; Đổi trả 7 ngày</span>
            </div>
        </div>

        <!-- Main Navbar -->
        <nav class="navbar navbar-expand-lg main-navbar shadow-sm">
            <div class="container d-flex align-items-center">
                <!-- Logo -->
                <a class="navbar-brand d-flex align-items-center gap-2" href="/">
                    <img src="/images/logo.png" alt="TechGear" style="height:40px;width:auto;object-fit:contain">
                    <span class="brand-name text-white">TECH<span class="text-primary">GEAR</span></span>

                </a>

                <!-- Mobile controls: toggler + actions -->
                <div class="navbar-mobile-controls d-flex align-items-center d-lg-none">
                    <!-- Mobile toggler -->
                    <button class="navbar-toggler border-secondary mobile-control-btn" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-label="Mở menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Mobile actions (Cart & Account) - Always visible on mobile -->
                    <div class="navbar-mobile-actions d-flex align-items-center gap-1">
                    <!-- Cart -->
                    <a id="headerCartButton" href="/cart" class="btn btn-outline-light btn-sm mobile-control-btn position-relative d-flex align-items-center gap-1" title="Giỏ hàng">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="headerCartCount" class="cart-count badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle border border-dark <?= $cartCount > 0 ? '' : 'd-none' ?>"><?= $cartCount ?></span>
                    </a>

                    <!-- Account -->
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle mobile-control-btn mobile-user-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img class="header-avatar" src="<?= \App\Core\View::e($renderAvatarUrl) ?>" onerror="this.onerror=null;this.src='<?= \App\Core\View::e($avatarFallback) ?>';" alt="Avatar">
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu shadow">
                                <li>
                                    <div class="dd-header mx-2">
                                        <div class="text-muted">Đã đăng nhập</div>
                                        <div class="fw-semibold text-truncate" style="max-width:160px"><?= \App\Core\View::e($currentUserEmail) ?></div>
                                    </div>
                                </li>
                                <li><a class="dropdown-item" href="/account"><i class="fas fa-user me-2 text-primary"></i>Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="/orders/history"><i class="fas fa-history me-2 text-primary"></i>Lịch sử mua hàng</a></li>
                                <?php if ($canAccessAdmin): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger fw-semibold" href="/admin"><i class="fas fa-shield-alt me-2"></i>Vào trang quản trị</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline-light btn-sm mobile-control-btn" title="Đăng nhập">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                    </div>
                </div>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <!-- Nav links -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPath === '/' ? 'active' : '' ?>" href="/">
                                <i class="fas fa-home me-1"></i>Trang chủ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($currentPath, '/products') ? 'active' : '' ?>" href="/products">
                                <i class="fas fa-shopping-bag me-1"></i>Sản phẩm
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPath === '/about' ? 'active' : '' ?>" href="/about">
                                <i class="fas fa-circle-info me-1"></i>Về chúng tôi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/#home-categories">
                                <i class="fas fa-th-large me-1"></i>Danh mục
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPath === '/contact' ? 'active' : '' ?>" href="/contact">
                                <i class="fas fa-envelope me-1"></i>Liên hệ
                            </a>
                        </li>
                    </ul>

                    <!-- Right side actions (Desktop) -->
                    <div class="navbar-actions d-none d-lg-flex align-items-center gap-1 ms-auto">
                        <!-- Search form -->
                        <form class="desktop-search d-none d-md-flex me-1" action="/products" method="get" role="search">
                            <div class="input-group input-group-sm desktop-search-group">
                                <input class="form-control" type="search" name="q" placeholder="Tìm sản phẩm..."
                                       value="<?= \App\Core\View::e((string)($_GET['q'] ?? '')) ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </form>

                        <!-- Cart -->
                        <a id="headerCartButton-desktop" href="/cart" class="btn btn-outline-light btn-sm desktop-control-btn position-relative d-flex align-items-center gap-1 text-nowrap" title="Giỏ hàng">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Giỏ</span>
                            <span id="headerCartCount-desktop" class="cart-count badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle border border-dark <?= $cartCount > 0 ? '' : 'd-none' ?>"><?= $cartCount ?></span>
                        </a>

                        <!-- Account -->
                        <?php if ($isLoggedIn): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-light btn-sm dropdown-toggle desktop-control-btn desktop-user-toggle text-nowrap" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img class="header-avatar" src="<?= \App\Core\View::e($renderAvatarUrl) ?>" onerror="this.onerror=null;this.src='<?= \App\Core\View::e($avatarFallback) ?>';" alt="Avatar">
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu shadow">
                                    <li>
                                        <div class="dd-header mx-2">
                                            <div class="text-muted">Đã đăng nhập</div>
                                            <div class="fw-semibold text-truncate" style="max-width:160px"><?= \App\Core\View::e($currentUserEmail) ?></div>
                                        </div>
                                    </li>
                                    <li><a class="dropdown-item" href="/account"><i class="fas fa-user me-2 text-primary"></i>Thông tin tài khoản</a></li>
                                    <li><a class="dropdown-item" href="/orders/history"><i class="fas fa-history me-2 text-primary"></i>Lịch sử mua hàng</a></li>
                                    <?php if ($canAccessAdmin): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger fw-semibold" href="/admin"><i class="fas fa-shield-alt me-2"></i>Vào trang quản trị</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/login" class="btn btn-outline-light btn-sm desktop-control-btn desktop-auth-btn text-nowrap">Đăng nhập</a>
                            <a href="/register" class="btn btn-primary btn-sm desktop-control-btn desktop-auth-btn text-nowrap">Đăng ký</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="<?= $currentPath === '/' ? 'is-home-page' : 'is-inner-page' ?>" style="margin:0;padding:0"><?php if(isset($viewFile)) require $viewFile; ?></main>

    <!-- Footer -->
    <footer class="site-footer text-white pt-5 pb-3 no-print">
        <div class="container">
            <div class="row g-4">
                <!-- Brand + Contact + Social -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="/images/logo.png" alt="TechGear" style="height:36px;width:auto;object-fit:contain">
                        <span class="fs-5 fw-bold">TechGear</span>
                    </div>
                    <p style="color:#94a3b8;font-size:0.88rem;line-height:1.6">
                        Cung cấp phụ kiện công nghệ chính hãng, đa dạng mẫu mã, giá tốt, bảo hành rõ ràng. Giao hàng nhanh toàn quốc.
                    </p>
                    <ul class="list-unstyled mt-3" style="color:#94a3b8;font-size:0.85rem">
                        <li class="mb-1"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Hỗ trợ trực tuyến toàn quốc</li>
                        <li class="mb-1"><i class="fas fa-phone-alt me-2 text-primary"></i>0326754284</li>
                        <li><i class="fas fa-envelope me-2 text-primary"></i>tub2306648@student.ctu.edu.vn</li>
                    </ul>
                    <div class="footer-social d-flex gap-2 mt-4">
                        <a href="https://www.facebook.com/share/1B5GXpFkBM/" target="_blank" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.youtube.com/@lehoangtu5692" target="_blank" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://zalo.me/" target="_blank" title="Zalo">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/9/91/Icon_of_Zalo.svg" width="16" height="16" alt="Zalo">
                        </a>
                    </div>
                </div>

                <!-- Danh mục nhanh -->
                <div class="col-lg-2 col-md-3 col-6">
                    <h5 class="footer-heading">Danh mục</h5>
                    <ul class="list-unstyled" style="font-size:0.88rem">
                        <li class="mb-2"><a href="/products?q=&category=am-thanh&price_range=&sort=newest">�m thanh</a></li>
                        <li class="mb-2"><a href="/products?q=&category=lot-chuot-mousepad&price_range=&sort=newest">Lót chuột</a></li>
                        <li class="mb-2"><a href="/products?q=&category=sac-cap&price_range=&sort=newest">Sạc & cáp</a></li>
                        <li class="mb-2"><a href="/products?q=&category=phu-kien-apple&price_range=&sort=newest">Phụ kiện Apple</a></li>
                        <li class="mb-2"><a href="/products?q=&category=den-led-decor&price_range=&sort=newest">Đèn LED Decor</a></li>
                        <li><a href="/products?q=&category=chuot-gaming&price_range=&sort=newest">Chuột Gaming</a></li>
                    </ul>
                </div>

                <!-- Hỗ trợ -->
                <div class="col-lg-2 col-md-3 col-6">
                    <h5 class="footer-heading">Hỗ trợ</h5>
                    <ul class="list-unstyled" style="font-size:0.88rem">
                        <li class="mb-2"><a href="/about">Về TechGear</a></li>
                        <li class="mb-2"><a href="/contact">Liên hệ</a></li>
                    </ul>
                </div>

                <!-- Nhận ưu đãi + Chính sách -->
                <div class="col-lg-4 col-md-12">
                    <h5 class="footer-heading">Nhận ưu đãi</h5>
                    <p style="color:#94a3b8;font-size:0.88rem">Đăng ký nhận thông báo khuyến mãi và sản phẩm mới mỗi tuần.</p>
                    <?php if ($newsletterStatus === 'ok'): ?>
                        <div class="alert alert-success py-2 px-3 mb-2" style="font-size:0.82rem;">Đăng ký nhận ưu đãi thành công.</div>
                    <?php elseif ($newsletterStatus === 'invalid'): ?>
                        <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:0.82rem;">Email chưa hợp lệ, vui lòng kiểm tra lại.</div>
                    <?php elseif ($newsletterStatus === 'failed'): ?>
                        <div class="alert alert-danger py-2 px-3 mb-2" style="font-size:0.82rem;">Không thể đăng ký lúc này. Vui lòng thử lại sau.</div>
                    <?php endif; ?>
                    <form class="d-flex gap-2 mb-4" method="POST" action="/newsletter/subscribe">
                        <input type="hidden" name="from_path" value="<?= \App\Core\View::e($currentPath) ?>">
                        <input type="email" name="email" required class="form-control form-control-sm bg-transparent border-secondary text-white" placeholder="Email của bạn" style="color:#fff!important">
                        <button class="btn btn-primary btn-sm text-nowrap" type="submit">Đăng ký</button>
                    </form>
                    <h5 class="footer-heading">Chính sách mua hàng</h5>
                    <div class="row g-2">
                        <div class="col-6"><div class="policy-chip"><i class="fas fa-truck"></i> Giao hàng toàn quốc</div></div>
                        <div class="col-6"><div class="policy-chip"><i class="fas fa-certificate"></i> Hàng chính hãng</div></div>
                        <div class="col-6"><div class="policy-chip"><i class="fas fa-undo"></i> Đổi trả 7 ngày</div></div>
                        <div class="col-6"><div class="policy-chip"><i class="fas fa-headset"></i> Hỗ trợ 24/7</div></div>
                    </div>
                </div>
            </div>

            <hr class="border-secondary mt-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center" style="color:#64748b;font-size:0.8rem">
                <span>&copy; <?= date('Y') ?> TechGear. Tất cả quyền được bảo lưu.</span>
                <span class="mt-2 mt-md-0">Dự án học tập — Chuyên ngành Công nghệ thông tin</span>
            </div>
        </div>
    </footer>

    <?php require __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/chatbox.js"></script>
</body>
</html>