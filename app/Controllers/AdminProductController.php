<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminProduct;

final class AdminProductController extends Controller {
    
    public function index(): void {
        $rows = AdminProduct::list();
        // CHỈ GỌI 1 LẦN với tham số layout admin
        $this->view('admin/products/index', [
            'title' => 'Quản lý sản phẩm',
            'rows' => $rows
        ], 'layouts/admin'); 
    }

    public function create(): void {
        $this->view('admin/products/create', [
            'title' => 'Thêm sản phẩm mới'
        ], 'layouts/simple'); // Sử dụng layout đơn giản
    }

    public function edit(string $id): void {
        $row = AdminProduct::find((int)$id);
        if (!$row) { $this->response->redirect('/admin/products'); return; }
        
        $this->view('admin/products/edit', [
            'row' => $row, 
            'title' => 'Sửa sản phẩm'
        ], 'layouts/simple'); // Sử dụng layout đơn giản
    }

    public function store(): void {
        try {
            $basePrice = (int)$this->request->input('base_price', 0);
            $salePrice = AdminProduct::calculateFinalPrice($basePrice);

            $data = [
                'name' => trim((string)$this->request->input('name')),
                'slug' => trim((string)$this->request->input('slug')),
                'description' => trim((string)$this->request->input('description')),
                'is_active' => $this->request->input('is_active') == '1',
                'sku' => trim((string)$this->request->input('sku')),
                'stock' => (int)$this->request->input('stock', 0),
                'base_price' => $basePrice,
                'sale_price' => $salePrice
            ];

            AdminProduct::createWithDefaultVariant($data);
            
            $this->response->redirect('/admin/products');

        } catch (\PDOException $e) {
            if ($e->getCode() == '23505') {
                $msg = "Lỗi: Dữ liệu này đã tồn tại (trùng Slug hoặc SKU).";
                if (strpos($e->getMessage(), 'products_slug_key') !== false) {
                    $msg = "Đường dẫn (Slug) này đã được sử dụng.";
                } elseif (strpos($e->getMessage(), 'product_variants_sku_key') !== false) {
                    $msg = "Mã SKU này đã tồn tại.";
                }
                
                $_SESSION['error'] = $msg;
                $_SESSION['old'] = $_POST; 
                
                $this->response->redirect('/admin/products/create');
                return;
            }

            $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
            $this->response->redirect('/admin/products/create');
        }
    }

    public function update(string $id): void {
        $pid = (int)$id;
        $basePrice = (int)$this->request->input('base_price', 0);
        $salePrice = AdminProduct::calculateFinalPrice($basePrice);

        $pData = [
            'name' => trim((string)$this->request->input('name')),
            'slug' => trim((string)$this->request->input('slug')),
            'description' => trim((string)$this->request->input('description')),
            'is_active' => $this->request->input('is_active') == '1'
        ];
        $vData = [
            'sku' => trim((string)$this->request->input('sku')),
            'base_price' => $basePrice,
            'sale_price' => $salePrice,
            'stock' => (int)$this->request->input('stock', 0)
        ];

        AdminProduct::update($pid, $pData, $vData);
        $this->response->redirect('/admin/products');
    }

    public function destroy(string $id): void {
        AdminProduct::delete((int)$id);
        $this->response->redirect('/admin/products');
    }
}