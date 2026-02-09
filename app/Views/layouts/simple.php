<!DOCTYPE html>
<html class="dark" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $title ?? 'TechGear Admin' ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: "Space Grotesk", sans-serif; }
    </style>
</head>
<body class="bg-[#101622] text-slate-100">
    <!-- Nội dung form sẽ nằm ở đây -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            <?php require $viewFile; ?>
        </div>
    </div>
</body>
</html>