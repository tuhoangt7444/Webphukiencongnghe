<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $title ?? 'Admin' ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
          darkMode: "class",
          theme: { extend: { colors: { "primary": "#1152d4", "background-dark": "#101622" }, fontFamily: { "display": ["Space Grotesk", "sans-serif"] } } }
        }
    </script>
    <style>
        .active-nav { background-color: rgba(17, 82, 212, 0.15); border-left: 3px solid #1152d4; color: #1152d4 !important; }
    </style>
</head>
<body class="bg-[#f6f6f8] dark:bg-[#101622] text-slate-100 font-display">
<div class="flex min-h-screen">
    <!-- SIDEBAR BẮT ĐẦU Ở ĐÂY -->
    <aside class="w-64 flex-shrink-0 bg-white dark:bg-[#161b28] border-r border-slate-800 flex flex-col">
        <div class="p-6 flex items-center gap-3">
            <div class="p-2 rounded-lg flex items-center justify-center">
            <img src="/images/logo.png"
                alt="TúTech Logo"
                class="h-8 w-auto">
        </div>
            <div class="dark:text-white text-slate-900 font-bold text-lg">TuTech</div>
        </div>
        <nav class="flex-1 px-3 mt-4 space-y-1">
            <a class="flex items-center gap-3 px-4 py-3 rounded-lg active-nav" href="/admin">
                <span class="text-sm font-semibold">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-400 hover:bg-slate-800" href="/admin/products">
                <span class="text-sm font-medium">Products</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-400 hover:bg-slate-800" href="#">
                <span class="text-sm font-medium">Orders</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-400 hover:bg-slate-800" href="#">
                <span class="text-sm font-medium">Settings</span>
            </a>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <a href="/logout" class="flex items-center gap-3 p-2 text-slate-400 hover:text-white">
                <span class="material-symbols-outlined">logout</span>
                <span class="text-sm">Logout</span>
            </a>
        </div>
    </aside>
    <!-- SIDEBAR KẾT THÚC -->

    <!-- NỘI DUNG CHÍNH -->
    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 flex items-center justify-between px-8 bg-white dark:bg-[#161b28] border-b border-slate-800">
            <div class="text-slate-400 text-sm">TuTech Admin / <?= $title ?></div>
        </header>

        <div class="p-8 overflow-y-auto">
            <!-- ĐÂY LÀ CHỖ HIỂN THỊ FILE dashboard.php HOẶC index.php -->
            <?php require $viewFile; ?>
        </div>
    </main>
</div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentArea = document.querySelector('main .p-8');

    document.addEventListener('click', function(e) {
        // Tìm thẻ <a> gần nhất
        const link = e.target.closest('a');
        if (!link) return;

        const url = link.getAttribute('href');
        
        // Bỏ qua các link không cần AJAX (logout, xóa, link ngoài, hoặc nút không có href)
        if (!url || url === '#' || url.startsWith('http') || url.includes('logout') || link.closest('form')) return;

        e.preventDefault();

        // Hiện hiệu ứng tải (tùy chọn)
        contentArea.style.opacity = '0.5';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('main .p-8');

                if (newContent) {
                    // Nếu tìm thấy vùng nội dung mới, thay thế nó
                    contentArea.innerHTML = newContent.innerHTML;
                    window.history.pushState({path: url}, '', url);
                    
                    // Cuộn lên đầu trang
                    window.scrollTo(0, 0);
                } else {
                    // Nếu không tìm thấy vùng content (LỖI LAYOUT), load lại trang truyền thống
                    window.location.href = url;
                }
                contentArea.style.opacity = '1';
            })
            .catch(err => {
                // Nếu Fetch lỗi (Server sập), load lại trang truyền thống để hiện lỗi PHP
                window.location.href = url;
            });
    });

    // Xử lý nút Back của trình duyệt
    window.addEventListener('popstate', function() {
        location.reload();
    });
});
</script>
</html>