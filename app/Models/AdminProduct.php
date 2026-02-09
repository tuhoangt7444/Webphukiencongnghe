<?php
namespace App\Models;
use App\Core\DB;
  final class AdminProduct {
    #lấy danh sách sản phẩm
    public static function list(): array 
    {
        $pdo = DB::conn();
        return $pdo->query("
        SELECT p.id, p.name, p.is_active, p.created_at
        FROM products p
        ORDER BY p.id DESC")->fetchAll();
    }
    #tìm sản phẩm theo id
    public static function find(int $id): ?array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare("SELECT * FROM products WHERE id=:id LIMIT 1");
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }
    #tạo sản phẩm mới kèm variant mặc định
    public static function createWithDefaultVariant(array $data): int
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $st = $pdo->prepare("
                INSERT INTO products(category_id, brand_id, product_line_id, name, slug, description, is_active)
                VALUES (:category_id, :brand_id, :product_line_id, :name, :slug, :description, :is_active)
                RETURNING id
            ");
            $st->execute([
                'category_id' => $data['category_id'] ?: null,
                'brand_id' => $data['brand_id'] ?: null,
                'product_line_id' => $data['product_line_id'] ?: null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?: null,
                'description' => $data['description'] ?: null,
                'is_active' => $data['is_active'] ? true : false,
            ]);
            $productId = (int)$st->fetchColumn();

            // Tạo 1 variant mặc định
            $st2 = $pdo->prepare("
                INSERT INTO product_variants(product_id, sku, combination_key, base_price, sale_price, stock, is_active)
                VALUES (:pid, :sku, :ck, :base_price, :sale_price, :stock, TRUE)
            ");
            $st2->execute([
                'pid' => $productId,
                'sku' => $data['sku'] ?: null,
                'ck' => 'default',
                'base_price' => (int)$data['base_price'],
                'sale_price' => (int)$data['sale_price'],
                'stock' => (int)$data['stock'],
            ]);

            $pdo->commit();
            return $productId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    #cập nhật sản phẩm
    public static function update(int $id, array $data): void
    {
        $pdo = DB::conn();
        $st = $pdo->prepare("
            UPDATE products
            SET category_id=:category_id,
                brand_id=:brand_id,
                product_line_id=:product_line_id,
                name=:name,
                slug=:slug,
                description=:description,
                is_active=:is_active
            WHERE id=:id
        ");
        $st->execute([
            'id' => $id,
            'category_id' => $data['category_id'] ?: null,
            'brand_id' => $data['brand_id'] ?: null,
            'product_line_id' => $data['product_line_id'] ?: null,
            'name' => $data['name'],
            'slug' => $data['slug'] ?: null,
            'description' => $data['description'] ?: null,
            'is_active' => $data['is_active'] ? true : false,
        ]);
    }
    # xóa sản phẩm
    public static function delete(int $id): void
    {
        $pdo = DB::conn();
        $st = $pdo->prepare("DELETE FROM products WHERE id=:id");
        $st->execute(['id' => $id]);
    }
}