<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?= View::e($title ?? '') ?></h1>
  <a class="btn btn-primary" href="/admin/products/create">+ Tạo sản phẩm</a>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tên</th>
        <th>Active</th>
        <th>Ngày tạo</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach (($rows ?? []) as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= View::e($r['name'] ?? '') ?></td>
        <td><?= ((bool)$r['is_active']) ? 'Yes' : 'No' ?></td>
        <td><?= View::e($r['created_at'] ?? '') ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/products/<?= (int)$r['id'] ?>/edit">Sửa</a>

          <form action="/admin/products/<?= (int)$r['id'] ?>/delete" method="post" class="d-inline"
                onsubmit="return confirm('Xoá sản phẩm này?');">
            <button class="btn btn-sm btn-outline-danger">Xoá</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
