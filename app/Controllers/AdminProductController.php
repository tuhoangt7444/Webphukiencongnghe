<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminProduct;

final class AdminProductController extends Controller {

    public function index(): void {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'category_id' => (int)$this->request->input('category_id', 0),
            'status' => trim((string)$this->request->input('status', '')),
            'min_price' => (int)$this->request->input('min_price', 0),
            'max_price' => (int)$this->request->input('max_price', 0),
        ];
        $page = max(1, (int)$this->request->input('page', 1));
        $result = AdminProduct::list($filters, $page, 10);

        $this->view('admin/products/index', [
            'title' => 'Quản lý sản phẩm',
            'rows' => $result['rows'],
            'stats' => $result['stats'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'categories' => AdminProduct::categories(),
            'status' => trim((string)$this->request->input('status_msg', '')),
        ], 'layouts/admin');
    }

    public function create(): void {
        $this->view('admin/products/create', [
            'title' => 'Thêm sản phẩm mới',
            'categories' => AdminProduct::categories(),
        ], 'layouts/admin');
    }

    public function edit(string $id): void {
        $row = AdminProduct::find((int)$id);
        if (!$row) { $this->response->redirect('/admin/products'); return; }
        
        $this->view('admin/products/edit', [
            'row' => $row,
            'title' => 'Sửa sản phẩm',
            'categories' => AdminProduct::categories(),
        ], 'layouts/admin');
    }

    public function store(): void {
        $this->ensureSession();

        try {
            // Các thông số tính giá
            $costPrice = (int)$this->request->input('cost_price', 0);
            $importTaxPercent = (float)$this->request->input('import_tax_percent', 0);
            $vatPercent = (float)$this->request->input('vat_percent', 0);
            $profitPercent = (float)$this->request->input('profit_percent', 0);
            
            // Tính giá bán bằng PricingCalculator
            $salePrice = AdminProduct::calculatePrice($costPrice, $importTaxPercent, $vatPercent, $profitPercent);
            $categoryId = (int)$this->request->input('category_id', 0);
            $brandName = trim((string)$this->request->input('brand_name', ''));
            $brandId = AdminProduct::resolveBrandId($brandName);

            $data = [
                'category_id' => $categoryId > 0 ? $categoryId : null,
                'brand_id' => $brandId,
                'name' => trim((string)$this->request->input('name')),
                'slug' => trim((string)$this->request->input('slug')),
                'short_description' => trim((string)$this->request->input('short_description')),
                'description' => trim((string)$this->request->input('description')),
                'highlights' => trim((string)$this->request->input('highlights')),
                'technical_specs' => trim((string)$this->request->input('technical_specs')),
                'shipping_info' => trim((string)$this->request->input('shipping_info')),
                'warranty_months' => max(0, (int)$this->request->input('warranty_months', 0)),
                'is_active' => $this->request->input('is_active') == '1',
                'sku' => trim((string)$this->request->input('sku')),
                'stock' => (int)$this->request->input('stock', 0),
                'cost_price' => $costPrice,
                'import_tax_percent' => $importTaxPercent,
                'vat_percent' => $vatPercent,
                'profit_percent' => $profitPercent,
                'base_price' => $costPrice,
                'sale_price' => $salePrice
            ];

            $productId = AdminProduct::createWithDefaultVariant($data);

            $mainImage = $this->uploadSingleImage('main_image');
            $galleryImages = $this->uploadMultipleImages('gallery_images');
            AdminProduct::saveImages($productId, $mainImage, $galleryImages);

            $this->response->redirect('/admin/products?status_msg=created');

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
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Có lỗi upload ảnh: " . $e->getMessage();
            $_SESSION['old'] = $_POST;
            $this->response->redirect('/admin/products/create');
        }
    }

    public function update(string $id): void {
        $this->ensureSession();

        $pid = (int)$id;
        
        // Các thông số tính giá
        $costPrice = (int)$this->request->input('cost_price', 0);
        $importTaxPercent = (float)$this->request->input('import_tax_percent', 0);
        $vatPercent = (float)$this->request->input('vat_percent', 0);
        $profitPercent = (float)$this->request->input('profit_percent', 0);
        
        // Tính giá bán bằng PricingCalculator
        $salePrice = AdminProduct::calculatePrice($costPrice, $importTaxPercent, $vatPercent, $profitPercent);
        $categoryId = (int)$this->request->input('category_id', 0);
        $brandName = trim((string)$this->request->input('brand_name', ''));
        $brandId = AdminProduct::resolveBrandId($brandName);

        $pData = [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'brand_id' => $brandId,
            'name' => trim((string)$this->request->input('name')),
            'slug' => trim((string)$this->request->input('slug')),
            'short_description' => trim((string)$this->request->input('short_description')),
            'description' => trim((string)$this->request->input('description')),
            'highlights' => trim((string)$this->request->input('highlights')),
            'technical_specs' => trim((string)$this->request->input('technical_specs')),
            'shipping_info' => trim((string)$this->request->input('shipping_info')),
            'warranty_months' => max(0, (int)$this->request->input('warranty_months', 0)),
            'is_active' => $this->request->input('is_active') == '1',
            'cost_price' => $costPrice,
            'import_tax_percent' => $importTaxPercent,
            'vat_percent' => $vatPercent,
            'profit_percent' => $profitPercent
        ];
        $vData = [
            'sku' => trim((string)$this->request->input('sku')),
            'base_price' => $costPrice,
            'sale_price' => $salePrice,
            'stock' => (int)$this->request->input('stock', 0)
        ];

        try {
            AdminProduct::update($pid, $pData, $vData);

            $deleteImageIds = $this->request->input('delete_image_ids', []);
            if (!is_array($deleteImageIds)) {
                $deleteImageIds = [];
            }
            AdminProduct::deleteImages($pid, $deleteImageIds);

            $mainImage = $this->uploadSingleImage('main_image');
            $galleryImages = $this->uploadMultipleImages('gallery_images');
            AdminProduct::saveImages($pid, $mainImage, $galleryImages);

            $this->response->redirect('/admin/products?status_msg=updated');
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Cập nhật thất bại: " . $e->getMessage();
            $this->response->redirect('/admin/products/' . $pid . '/edit');
        }
    }

    public function destroy(string $id): void {
        AdminProduct::delete((int)$id);
        $this->response->redirect('/admin/products?status_msg=deleted');
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function uploadSingleImage(string $field): ?string
    {
        if (empty($_FILES[$field]) || !isset($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        return $this->moveUpload(
            (string)$_FILES[$field]['tmp_name'],
            (string)$_FILES[$field]['name']
        );
    }

    private function uploadMultipleImages(string $field): array
    {
        $urls = [];
        if (empty($_FILES[$field]) || !isset($_FILES[$field]['tmp_name']) || !is_array($_FILES[$field]['tmp_name'])) {
            return $urls;
        }

        foreach ($_FILES[$field]['tmp_name'] as $idx => $tmpName) {
            $error = (int)($_FILES[$field]['error'][$idx] ?? UPLOAD_ERR_NO_FILE);
            $originalName = (string)($_FILES[$field]['name'][$idx] ?? '');
            if ($error !== UPLOAD_ERR_OK || $tmpName === '' || $originalName === '') {
                continue;
            }
            $urls[] = $this->moveUpload((string)$tmpName, $originalName);
        }

        return $urls;
    }

    private function moveUpload(string $tmpName, string $originalName): string
    {
        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Định dạng ảnh không hợp lệ. Chỉ hỗ trợ jpg, jpeg, png, webp, gif.');
        }

        $targetDir = dirname(__DIR__, 2) . '/public/uploads/products';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Không thể tạo thư mục upload ảnh.');
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new \RuntimeException('Upload ảnh thất bại.');
        }

        return '/uploads/products/' . $filename;
    }
}