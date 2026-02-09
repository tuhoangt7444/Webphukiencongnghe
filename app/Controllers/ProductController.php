<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(): void
    {
        $products = Product::all();

        $this->view('product/index', [
            'title' => 'Sản phẩm',
            'products' => $products
        ]);
    }

    public function show(string $id): void
    {
        $product = Product::findWithVariants((int)$id);
        if (!$product) {
            $this->response->send('Sản phẩm không tồn tại', 404);
            return;
        }
        $this->view('product/show', [
            'title' => 'Chi tiết sản phẩm',
            'product' => $product
        ]);
    }

    
}
