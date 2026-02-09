<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminProduct;

final class AdminProductController extends Controller
{
    #hiển thị danh sách sản phẩm
    public function index(): void
    {
        $rows = AdminProduct::list();
        $this->view('admin/products/index', [
            'title' => 'Admin - Products',
            'rows' => $rows
        ]);
    }
    #hiển thị form tạo sản phẩm mới
    public function create(): void
    {
        $this->view('admin/products/create', [
            'title' => 'Tạo sản phẩm mới'
        ]);
    }
    #xử lý tạo sản phẩm mới
    public function store(): void
    {
        # trim cắt bỏ khoảng trắng đầu cuối
        $name = trim((string)$this->request->input('name', ''));
        if ($name === '') {
            $this->response->send("Tên sản phẩm không được rỗng", 400);
            return;
        }
        $data = [
            'categogy_id' => $this->request->input('category_id'),
            'brand_id' => $this->request->input('brand_id'),
            'product_line_id' => $this->request->input('product_line_id'),
            'name' => $name,
            'slug' => trim((string)$this->request->input('slug', '')),
            'description' => trim((string)$this->request->input('description', '')),
            'is_active' => $this->request->input('is_active') ? 1 : 0,

            # variant mặc định
            'sku' => trim((string)$this->request->input('sku', '')),
            'base_price' => (int)$this->request->input('base_price', 0),
            'sale_price' => (int)$this->request->input('sale_price', 0),
            'stock' => (int)$this->request->input('stock', 0),
        ];

        $id = AdminProduct::createWithDefaultVariant($data);
        $this->response->redirect('/admin/products/' . $id . '/edit');
    }
    #hiển thị form sửa sản phẩm
    public function edit(string $id): void
    {
        $row = AdminProduct::find((int)$id);
        if (!$row) {
            $this->response->send("404 Not Found", 404);
            return;
        }

        $this->view('admin/products/edit', [
            'title' => 'Sửa sản phẩm',
            'row' => $row
        ]);
    }
    #xử lý cập nhật sản phẩm
    public function update(string $id): void
    {
        $pid = (int)$id;
        $row = AdminProduct::find($pid);
        if (!$row) {
            $this->response->send("404 Not Found", 404);
            return;
        }

        $name = trim((string)$this->request->input('name', ''));
        if ($name === '') {
            $this->response->send("Tên sản phẩm không được rỗng", 400);
            return;
        }

        $data = [
            'category_id' => $this->request->input('category_id'),
            'brand_id' => $this->request->input('brand_id'),
            'product_line_id' => $this->request->input('product_line_id'),
            'name' => $name,
            'slug' => trim((string)$this->request->input('slug', '')),
            'description' => trim((string)$this->request->input('description', '')),
            'is_active' => $this->request->input('is_active') ? 1 : 0,
        ];

        AdminProduct::update($pid, $data);
        $this->response->redirect('/admin/products/' . $pid . '/edit');
    }
    #xử lý xóa sản phẩm
    public function destroy(string $id): void
    {
        AdminProduct::delete((int)$id);
        $this->response->redirect('/admin/products');
    }
}