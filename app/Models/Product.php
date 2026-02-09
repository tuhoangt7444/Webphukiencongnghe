<?php
namespace App\Models;
use App\Core\DB;

final class Product {
    # Lấy tất cả sản phẩm kèm giá từ thấp đến cao và tổng tồn kho
    public static function all(): array 
    {
        $pdo = DB::conn();
        $sql = " SELECT p.id, p.name, p.slug, MIN(v.sale_price) AS price_from, SUM(v.stock) AS stock_total
                FROM products p
                JOIN product_variants v ON v.product_id = p.id
                WHERE p.is_active = TRUE AND v.is_active = TRUE
                GROUP BY p.id
                ORDER BY p.id DESC ";
                return $pdo->query($sql)->fetchAll();
    }
    # Lấy chi tiết sản phẩm theo id kèm các biến thể
    public static function findWithVariants(int $id): ?array
    {
        $pdo = DB::conn();
    # kiểm tra sản phẩm tồn tại
    $st = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $st->execute(['id' => $id]);
    $product = $st->fetch();
    if(!$product) return null;
    $st2 = $pdo->prepare("SELECT v.id AS variant_id, v.sku, v.base_price, v.sale_price, v.stock,
        COALESCE(string_agg(ot.name || ': ' || ov.value, ' | ' ORDER BY ot.id), '') AS options_text
            FROM product_variants v
            LEFT JOIN variant_option_values vov ON vov.variant_id = v.id
            LEFT JOIN option_values ov ON ov.id = vov.option_value_id
            LEFT JOIN option_types ot ON ot.id = ov.option_type_id
            WHERE v.product_id = :pid AND v.is_active = TRUE
            GROUP BY v.id
            ORDER BY v.id ASC
        ");
        $st2->execute(['pid' => $id]);
        $variants = $st2->fetchAll();

        $product['variants'] = $variants;
        return $product;
    }

}