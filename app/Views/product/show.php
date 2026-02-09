<?php use App\Core\View; $p = $product ?? []; ?>

<h1 class="mb-3"><?= View::e($p['name'] ?? '') ?></h1>
<p class="text-muted"><?= View::e($p['description'] ?? '') ?></p>

<h4 class="mt-4">Biến thể</h4>
<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead>
      <tr>
        <th>SKU</th>
        <th>Option</th>
        <th>Giá gốc</th>
        <th>Giá bán</th>
        <th>Tồn kho</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach (($p['variants'] ?? []) as $v): ?>
      <tr>
        <td><?= View::e($v['sku'] ?? '') ?></td>
        <td><?= View::e($v['options_text'] ?? '') ?></td>
        <td><?= number_format((int)$v['base_price']) ?>đ</td>
        <td><b><?= number_format((int)$v['sale_price']) ?>đ</b></td>
        <td><?= (int)$v['stock'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
