<?php
use App\Core\View; // để gọi View::e()
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title ?? 'Phụ kiện') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">PhuKien</a>
    <div class="navbar-nav">
      <a class="nav-link" href="/products">Sản phẩm</a>
      <a class="nav-link" href="/admin">Admin</a>
    </div>
  </div>
</nav>

<main class="container py-4">
  <?php require $viewFile; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
