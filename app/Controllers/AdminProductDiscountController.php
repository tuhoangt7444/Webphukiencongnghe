<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProductDiscount;

final class AdminProductDiscountController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int)$this->request->input('page', 1));
        $result = ProductDiscount::adminList($page, 12);

        $this->view('admin/product_discounts/index', [
            'title' => 'Giảm giá sản phẩm',
            'rows' => $result['rows'],
            'pagination' => $result['pagination'],
            'stats' => $result['stats'],
            'suggestions' => ProductDiscount::recommendations(12),
            'allProducts' => ProductDiscount::allActiveProducts(),
            'status' => trim((string)$this->request->input('status', '')),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $result = ProductDiscount::create([
            'product_id' => $this->request->input('product_id', 0),
            'discount_percent' => $this->request->input('discount_percent', 0),
            'start_at' => $this->request->input('start_at', ''),
            'end_at' => $this->request->input('end_at', ''),
        ]);

        if (($result['ok'] ?? false) === true) {
            $this->response->redirect('/admin/product-discounts?status=created');
            return;
        }

        $error = (string)($result['error'] ?? 'failed');
        $map = [
            'not-found' => 'not-found',
            'invalid-percent' => 'invalid-percent',
            'invalid-time' => 'invalid-time',
            'invalid-time-range' => 'invalid-time-range',
            'over-profit' => 'over-profit',
            'failed' => 'failed',
        ];

        $this->response->redirect('/admin/product-discounts?status=' . ($map[$error] ?? 'failed'));
    }

    public function toggle(string $id): void
    {
        $result = ProductDiscount::toggle((int)$id);
        if ($result === 'not-found') {
            $this->response->redirect('/admin/product-discounts?status=not-found');
            return;
        }

        $this->response->redirect('/admin/product-discounts?status=toggled');
    }

    public function destroy(string $id): void
    {
        if (!ProductDiscount::delete((int)$id)) {
            $this->response->redirect('/admin/product-discounts?status=not-found');
            return;
        }

        $this->response->redirect('/admin/product-discounts?status=deleted');
    }
}
