<?php
namespace App\Models;

use App\Core\DB;

final class AdminProduct {
    
    public static function list(): array 
    {
        $pdo = DB::conn();
        // Chúng ta cần SELECT thêm p.slug và JOIN với bảng variants để lấy giá/kho
        $sql = "
            SELECT 
                p.id, 
                p.name, 
                p.slug, 
                p.is_active, 
                p.created_at,
                v.sku,
                v.sale_price,
                v.stock
            FROM products p
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.combination_key = 'default'
            ORDER BY p.id DESC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = DB::conn();
        $st = $pdo->prepare("
            SELECT p.*, v.sku, v.base_price, v.sale_price, v.stock 
            FROM products p
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.combination_key = 'default'
            WHERE p.id = :id LIMIT 1
        ");
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    // Logic tính giá bán dựa trên chi phí hệ thống
    public static function calculateFinalPrice(int $basePrice): int {
        $pdo = DB::conn();
        $st = $pdo->query("SELECT * FROM pricing_settings ORDER BY id DESC LIMIT 1");
        $s = $st->fetch();

        if (!$s) return (int)($basePrice * 1.2); // Mặc định cộng 20% nếu chưa có cài đặt

        $totalPct = (float)$s['rent_pct'] + (float)$s['labor_pct'] + (float)$s['tax_pct'] + (float)$s['other_pct'];
        return (int)ceil($basePrice * (1 + $totalPct));
    }

    public static function createWithDefaultVariant(array $data): int {
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("INSERT INTO products(name, slug, description, is_active) VALUES (:n, :s, :d, :a) RETURNING id");
            $st->execute([
                'n' => $data['name'], 's' => $data['slug'] ?: null, 
                'd' => $data['description'] ?: null, 'a' => $data['is_active'] ? 'true' : 'false'
            ]);
            $productId = (int)$st->fetchColumn();

            $st2 = $pdo->prepare("INSERT INTO product_variants(product_id, sku, combination_key, base_price, sale_price, stock, is_active) 
                                  VALUES (:pid, :sku, 'default', :bp, :sp, :stock, TRUE)");
            $st2->execute([
                'pid' => $productId, 'sku' => $data['sku'], 'bp' => $data['base_price'], 
                'sp' => $data['sale_price'], 'stock' => $data['stock']
            ]);
            $pdo->commit();
            return $productId;
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    public static function update(int $id, array $pData, array $vData): void {
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("UPDATE products SET name=:n, slug=:s, description=:d, is_active=:a WHERE id=:id");
            $st->execute(['id'=>$id, 'n'=>$pData['name'], 's'=>$pData['slug'], 'd'=>$pData['description'], 'a'=>$pData['is_active']?'true':'false']);

            $st2 = $pdo->prepare("UPDATE product_variants SET sku=:sku, base_price=:bp, sale_price=:sp, stock=:stock 
                                  WHERE product_id=:pid AND combination_key='default'");
            $st2->execute(['pid'=>$id, 'sku'=>$vData['sku'], 'bp'=>$vData['base_price'], 'sp'=>$vData['sale_price'], 'stock'=>$vData['stock']]);
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    public static function delete(int $id): void {
        DB::conn()->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
}