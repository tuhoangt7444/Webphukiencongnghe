<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
$pageTitle = $title ?? 'Quản trị hệ thống';

$roleCode = (string)($_SESSION['user_role_code'] ?? '');
$isSuperAdmin = $roleCode === 'admin';
$userPermissions = $_SESSION['admin_permissions'] ?? [];
if (!is_array($userPermissions)) {
    $userPermissions = [];
}

$menu = [
    ['label' => 'Dashboard', 'href' => '/admin', 'icon' => 'fa-gauge-high', 'permission' => 'admin.dashboard', 'active' => $currentPath === '/admin'],
    ['label' => 'Quản lý sản phẩm', 'href' => '/admin/products', 'icon' => 'fa-box-open', 'permission' => 'admin.products', 'active' => str_starts_with($currentPath, '/admin/products')],
    ['label' => 'Quản lý đơn hàng', 'href' => '/admin/orders', 'icon' => 'fa-cart-shopping', 'permission' => 'admin.orders', 'active' => str_starts_with($currentPath, '/admin/orders')],
    ['label' => 'Quản lý người dùng', 'href' => '/admin/users', 'icon' => 'fa-users', 'permission' => 'admin.users', 'active' => str_starts_with($currentPath, '/admin/users')],
    ['label' => 'Giảm giá sản phẩm', 'href' => '/admin/product-discounts', 'icon' => 'fa-tags', 'permission' => 'admin.product_discounts', 'active' => str_starts_with($currentPath, '/admin/product-discounts')],
    ['label' => 'Quản lý danh mục', 'href' => '/admin/categories', 'icon' => 'fa-folder-tree', 'permission' => 'admin.categories', 'active' => str_starts_with($currentPath, '/admin/categories')],
    ['label' => 'Quản lý phiếu giảm giá', 'href' => '/admin/vouchers', 'icon' => 'fa-ticket', 'permission' => 'admin.vouchers', 'active' => str_starts_with($currentPath, '/admin/vouchers')],
    ['label' => 'Quản lý tồn kho', 'href' => '/admin/inventory', 'icon' => 'fa-warehouse', 'permission' => 'admin.inventory', 'active' => str_starts_with($currentPath, '/admin/inventory')],
    ['label' => 'Quản lý đánh giá', 'href' => '/admin/reviews', 'icon' => 'fa-star', 'permission' => 'admin.reviews', 'active' => str_starts_with($currentPath, '/admin/reviews')],
    ['label' => 'Quản lý banner', 'href' => '/admin/banners', 'icon' => 'fa-images', 'permission' => 'admin.banners', 'active' => str_starts_with($currentPath, '/admin/banners')],
    ['label' => 'Quản lý bài viết', 'href' => '/admin/posts', 'icon' => 'fa-newspaper', 'permission' => 'admin.posts', 'active' => str_starts_with($currentPath, '/admin/posts')],
    ['label' => 'Quản lý liên hệ', 'href' => '/admin/contacts', 'icon' => 'fa-envelope-open-text', 'permission' => 'admin.contacts', 'active' => str_starts_with($currentPath, '/admin/contacts')],
    ['label' => 'Nhận ưu đãi', 'href' => '/admin/newsletters', 'icon' => 'fa-bullhorn', 'permission' => 'admin.newsletters', 'active' => str_starts_with($currentPath, '/admin/newsletters')],
    ['label' => 'Phân quyền', 'href' => '/admin/roles', 'icon' => 'fa-user-shield', 'permission' => 'admin.roles', 'active' => str_starts_with($currentPath, '/admin/roles')],
];

$menu = array_values(array_filter($menu, static function (array $item) use ($isSuperAdmin, $userPermissions): bool {
    if ($isSuperAdmin) {
        return true;
    }

    $permission = (string)($item['permission'] ?? '');
    return $permission !== '' && in_array($permission, $userPermissions, true);
}));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?= \App\Core\View::e($pageTitle) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; }
        .admin-shell { min-height: 100vh; }
        .admin-sidebar {
            width: 270px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            z-index: 1030;
            overflow-y: auto;
        }
        .admin-main {
            margin-left: 270px;
            min-height: 100vh;
        }
        .admin-brand {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .admin-brand .title { font-weight: 800; letter-spacing: .02em; }
        .menu-link {
            color: #cbd5e1;
            border-radius: 10px;
            padding: .7rem .8rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .65rem;
            font-weight: 600;
            font-size: .92rem;
        }
        .menu-link:hover { background: rgba(59,130,246,.18); color: #fff; }
        .menu-link.active {
            background: rgba(59,130,246,.32);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(147,197,253,.35);
        }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid #e2e8f0;
        }
        @media (max-width: 991.98px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform .25s ease; }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-main { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside id="adminSidebar" class="admin-sidebar">
        <div class="admin-brand d-flex align-items-center gap-2">
            <span class="badge text-bg-primary"><i class="fa-solid fa-microchip"></i></span>
            <div>
                <div class="title">TechGear Admin</div>
                <small class="text-secondary">Management panel</small>
            </div>
        </div>
        <nav class="p-3 d-grid gap-2">
            <?php foreach ($menu as $item): ?>
                <a class="menu-link <?= $item['active'] ? 'active' : '' ?>" href="<?= \App\Core\View::e($item['href']) ?>">
                    <i class="fa-solid <?= \App\Core\View::e($item['icon']) ?>"></i>
                    <span><?= \App\Core\View::e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="p-3 mt-auto border-top border-secondary border-opacity-25">
            <a href="/logout" class="btn btn-outline-light w-100">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Đăng xuất
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="topbar px-3 px-lg-4 py-2">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <button id="sidebarToggle" class="btn btn-outline-secondary d-lg-none" type="button">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <div class="small text-muted text-uppercase">Khu vực quản trị</div>
                        <div class="fw-bold"><?= \App\Core\View::e($pageTitle) ?></div>
                    </div>
                </div>
                <a href="/" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-house me-1"></i>Về trang chủ</a>
            </div>
        </header>

        <main class="p-3 p-lg-4">
            <?php require $viewFile; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });
})();
</script>
</body>
</html>