<?php
namespace App\Models;
use App\Core\DB;

final class Product {
    # Lấy tất cả sản phẩm kèm giá từ thấp đến cao và tổng tồn kho
    public static function all(): array 
    {
        $pdo = DB::conn();
        $sql = " SELECT p.id, p.name, p.slug, p.short_description, p.warranty_months,
                c.name AS category_name,
                MIN(v.sale_price) AS price_from,
                MIN(v.base_price) AS base_price_from,
                SUM(v.stock) AS stock_total
                FROM products p
                JOIN product_variants v ON v.product_id = p.id
            LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.is_active = TRUE AND v.is_active = TRUE
            GROUP BY p.id, c.name
                ORDER BY p.id DESC ";
                return $pdo->query($sql)->fetchAll();
    }

    public static function allForPostRelation(): array
    {
        $pdo = DB::conn();
        $sql = "SELECT p.id,
                       p.name,
                       p.slug,
                       p.is_active,
                       c.name AS category_name,
                       MIN(v.sale_price) AS price_from,
                       SUM(COALESCE(v.stock, 0)) AS stock_total
                FROM products p
                LEFT JOIN product_variants v ON v.product_id = p.id
                LEFT JOIN categories c ON c.id = p.category_id
                GROUP BY p.id, c.name
                ORDER BY p.id DESC";

        return $pdo->query($sql)->fetchAll();
    }

    public static function homeCategories(int $limit = 6): array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT c.id, c.name, c.slug, COUNT(DISTINCT p.id) AS product_count
             FROM categories c
             JOIN products p ON p.category_id = c.id AND p.is_active = TRUE
             JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
             GROUP BY c.id, c.name, c.slug
             ORDER BY product_count DESC, c.name ASC
             LIMIT :lim"
        );
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function homeFeatured(int $limit = 8): array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT p.id, p.name, p.slug,
                    c.name AS category_name,
                    MIN(v.sale_price) AS price_from,
                    MIN(v.base_price) AS base_price_from,
                    SUM(v.stock) AS stock_total
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = TRUE AND v.is_active = TRUE
             GROUP BY p.id, c.name
             ORDER BY SUM(v.stock) DESC, p.id DESC
             LIMIT :lim"
        );
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function homeNewest(int $limit = 8): array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT p.id, p.name, p.slug,
                    c.name AS category_name,
                    MIN(v.sale_price) AS price_from,
                    MIN(v.base_price) AS base_price_from,
                    SUM(v.stock) AS stock_total,
                    p.created_at
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = TRUE AND v.is_active = TRUE
             GROUP BY p.id, c.name
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT :lim"
        );
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function homeFlashSale(int $limit = 8): array
    {
        $pdo = DB::conn();

        $tableExists = $pdo->query("SELECT to_regclass('public.product_discount_campaigns') IS NOT NULL")->fetchColumn();
        if (!$tableExists) {
            return [];
        }

        $st = $pdo->prepare(
            "WITH active_campaigns AS (
                SELECT DISTINCT ON (dc.product_id)
                    dc.product_id,
                    dc.discount_percent,
                    dc.start_at,
                    dc.end_at
                FROM product_discount_campaigns dc
                WHERE dc.status = 'active'
                  AND dc.start_at <= NOW()
                  AND dc.end_at >= NOW()
                ORDER BY dc.product_id, dc.created_at DESC, dc.id DESC
             ), variant_stats AS (
                SELECT v.product_id,
                       MIN(v.sale_price) AS price_from,
                       SUM(v.stock) AS stock_total
                FROM product_variants v
                WHERE v.is_active = TRUE
                GROUP BY v.product_id
             ), first_image AS (
                SELECT DISTINCT ON (pi.product_id)
                       pi.product_id,
                       pi.image_url
                FROM product_images pi
                ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
             ), rating_stats AS (
                SELECT r.product_id,
                       ROUND(AVG(r.rating)::numeric, 1) AS avg_rating,
                       COUNT(*) AS review_count
                FROM reviews r
                WHERE r.status = 'visible'
                GROUP BY r.product_id
             )
             SELECT p.id,
                    p.name,
                    p.slug,
                                        GREATEST(
                                                0,
                                                COALESCE(vs.price_from, p.price, 0)
                                                - FLOOR(COALESCE(vs.price_from, p.price, 0) * COALESCE(ac.discount_percent, 0) / 100.0)
                                        )::bigint AS price_from,
                                        COALESCE(vs.price_from, p.price, 0)::bigint AS base_price_from,
                                        COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                    COALESCE(vs.stock_total, 0) AS stock_total,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count,
                    fi.image_url
             FROM products p
                         JOIN active_campaigns ac ON ac.product_id = p.id
             JOIN variant_stats vs ON vs.product_id = p.id
             LEFT JOIN first_image fi ON fi.product_id = p.id
             LEFT JOIN rating_stats rs ON rs.product_id = p.id
             WHERE p.is_active = TRUE
                             AND COALESCE(vs.stock_total, 0) > 0
                             AND COALESCE(ac.discount_percent, 0) > 0
             ORDER BY discount_percent DESC, p.created_at DESC, p.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function homeBestSelling(int $limit = 8): array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "WITH sold AS (
                SELECT COALESCE(oi.product_id, pv.product_id) AS product_id,
                       SUM(oi.qty) AS sold_qty
                FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                     WHERE o.status IN ('approved', 'shipping', 'done')
                GROUP BY COALESCE(oi.product_id, pv.product_id)
             ), variant_stats AS (
                SELECT v.product_id,
                       MIN(v.sale_price) AS price_from,
                       MIN(v.base_price) AS base_price_from,
                       SUM(v.stock) AS stock_total
                FROM product_variants v
                WHERE v.is_active = TRUE
                GROUP BY v.product_id
             ), first_image AS (
                SELECT DISTINCT ON (pi.product_id)
                       pi.product_id,
                       pi.image_url
                FROM product_images pi
                ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
             ), rating_stats AS (
                SELECT r.product_id,
                       ROUND(AVG(r.rating)::numeric, 1) AS avg_rating,
                       COUNT(*) AS review_count
                FROM reviews r
                WHERE r.status = 'visible'
                GROUP BY r.product_id
             ), active_campaigns AS (
                SELECT DISTINCT ON (dc.product_id)
                    dc.product_id,
                    dc.discount_percent
                FROM product_discount_campaigns dc
                WHERE dc.status = 'active'
                  AND dc.start_at <= NOW()
                  AND dc.end_at >= NOW()
                ORDER BY dc.product_id, dc.created_at DESC, dc.id DESC
             )
             SELECT p.id,
                    p.name,
                    p.slug,
                    GREATEST(
                        0,
                        COALESCE(vs.price_from, p.price, 0)
                        - FLOOR(COALESCE(vs.price_from, p.price, 0) * COALESCE(ac.discount_percent, 0) / 100.0)
                    )::bigint AS price_from,
                    COALESCE(vs.price_from, p.price, 0)::bigint AS base_price_from,
                    COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                    COALESCE(vs.stock_total, 0) AS stock_total,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count,
                    COALESCE(s.sold_qty, 0) AS sold_qty,
                    fi.image_url
             FROM sold s
             JOIN products p ON p.id = s.product_id
             LEFT JOIN variant_stats vs ON vs.product_id = p.id
             LEFT JOIN first_image fi ON fi.product_id = p.id
             LEFT JOIN rating_stats rs ON rs.product_id = p.id
             LEFT JOIN active_campaigns ac ON ac.product_id = p.id
             WHERE p.is_active = TRUE
             ORDER BY s.sold_qty DESC, p.created_at DESC, p.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function homeNewestDetailed(int $limit = 8): array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "WITH variant_stats AS (
                SELECT v.product_id,
                       MIN(v.sale_price) AS price_from,
                       MIN(v.base_price) AS base_price_from,
                       SUM(v.stock) AS stock_total
                FROM product_variants v
                WHERE v.is_active = TRUE
                GROUP BY v.product_id
             ), first_image AS (
                SELECT DISTINCT ON (pi.product_id)
                       pi.product_id,
                       pi.image_url
                FROM product_images pi
                ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
             ), rating_stats AS (
                SELECT r.product_id,
                       ROUND(AVG(r.rating)::numeric, 1) AS avg_rating,
                       COUNT(*) AS review_count
                FROM reviews r
                WHERE r.status = 'visible'
                GROUP BY r.product_id
             ), active_campaigns AS (
                SELECT DISTINCT ON (dc.product_id)
                    dc.product_id,
                    dc.discount_percent
                FROM product_discount_campaigns dc
                WHERE dc.status = 'active'
                  AND dc.start_at <= NOW()
                  AND dc.end_at >= NOW()
                ORDER BY dc.product_id, dc.created_at DESC, dc.id DESC
             )
             SELECT p.id,
                    p.name,
                    p.slug,
                    GREATEST(
                        0,
                        COALESCE(vs.price_from, p.price, 0)
                        - FLOOR(COALESCE(vs.price_from, p.price, 0) * COALESCE(ac.discount_percent, 0) / 100.0)
                    )::bigint AS price_from,
                    COALESCE(vs.price_from, p.price, 0)::bigint AS base_price_from,
                    COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                    COALESCE(vs.stock_total, 0) AS stock_total,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count,
                    p.created_at,
                    fi.image_url
             FROM products p
             LEFT JOIN variant_stats vs ON vs.product_id = p.id
             LEFT JOIN first_image fi ON fi.product_id = p.id
             LEFT JOIN rating_stats rs ON rs.product_id = p.id
             LEFT JOIN active_campaigns ac ON ac.product_id = p.id
             WHERE p.is_active = TRUE
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function filter(array $filters): array
    {
        $pdo = DB::conn();

        $conditions = ['p.is_active = TRUE'];
        $params = [];

        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $conditions[] = 'p.name ILIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $category = trim((string)($filters['category'] ?? ''));
        if ($category !== '') {
            $conditions[] = 'c.slug = :category_slug';
            $params['category_slug'] = $category;
        }

        $minPrice = $filters['min_price'];
        if ($minPrice !== null) {
            $conditions[] = 'pv.price_from >= :min_price';
            $params['min_price'] = (int)$minPrice;
        }

        $maxPrice = $filters['max_price'];
        if ($maxPrice !== null) {
            $conditions[] = 'pv.price_from <= :max_price';
            $params['max_price'] = (int)$maxPrice;
        }

        $sql = "SELECT p.id, p.name, p.slug, p.short_description, p.warranty_months,
                       c.name AS category_name,
                       pv.price_from,
                       pv.base_price_from,
                       pv.stock_total
                FROM products p
                JOIN (
                    SELECT v.product_id,
                           MIN(v.sale_price) AS price_from,
                           MIN(v.base_price) AS base_price_from,
                           SUM(v.stock) AS stock_total
                    FROM product_variants v
                    WHERE v.is_active = TRUE
                    GROUP BY v.product_id
                ) pv ON pv.product_id = p.id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY p.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function categoriesForFilter(): array
    {
        $pdo = DB::conn();
        $sql = "SELECT DISTINCT c.id, c.name, c.slug
                FROM categories c
                JOIN products p ON p.category_id = c.id AND p.is_active = TRUE
                ORDER BY c.name ASC";

        return $pdo->query($sql)->fetchAll();
    }

    public static function categoriesForCatalog(): array
    {
        return self::categoriesForFilter();
    }

    public static function listForCatalog(array $filters, int $page = 1, int $perPage = 12): array
    {
        $pdo = DB::conn();

        $page = max(1, $page);
        $perPage = max(1, min(48, $perPage));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = self::buildCatalogWhere($filters);
        $orderSql = self::buildCatalogOrder((string)($filters['sort'] ?? 'newest'));

        $sql = "WITH variant_stats AS (
                    SELECT v.product_id,
                           MIN(v.sale_price) AS price_from,
                           MIN(v.base_price) AS base_price_from,
                           COALESCE(SUM(v.stock), 0) AS stock_total
                    FROM product_variants v
                    WHERE v.is_active = TRUE
                    GROUP BY v.product_id
                ), first_image AS (
                    SELECT DISTINCT ON (pi.product_id)
                           pi.product_id,
                           pi.image_url
                    FROM product_images pi
                    ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
                ), sold_stats AS (
                    SELECT COALESCE(oi.product_id, pv.product_id) AS product_id,
                           COALESCE(SUM(oi.qty), 0) AS sold_qty
                    FROM order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                    WHERE o.status IN ('approved', 'shipping', 'done')
                    GROUP BY COALESCE(oi.product_id, pv.product_id)
                )
                SELECT p.id,
                       p.name,
                       p.slug,
                       p.created_at,
                       c.name AS category_name,
                       COALESCE(vs.price_from, p.price, 0)::bigint AS original_price,
                       CASE
                           WHEN ac.discount_percent IS NOT NULL AND ac.discount_percent > 0
                           THEN GREATEST(0, COALESCE(vs.price_from, p.price, 0)
                                - FLOOR(COALESCE(vs.price_from, p.price, 0)::numeric * ac.discount_percent / 100.0))::bigint
                           ELSE COALESCE(vs.price_from, p.price, 0)::bigint
                       END AS price,
                       COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                       COALESCE(vs.stock_total, 0) AS stock_total,
                       COALESCE(ss.sold_qty, 0) AS sold_qty,
                       COALESCE(fi.image_url, '') AS image
                FROM products p
                  LEFT JOIN variant_stats vs ON vs.product_id = p.id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN first_image fi ON fi.product_id = p.id
                LEFT JOIN sold_stats ss ON ss.product_id = p.id
                LEFT JOIN LATERAL (
                    SELECT discount_percent
                    FROM product_discount_campaigns
                    WHERE product_id = p.id
                      AND status = 'active'
                      AND start_at <= NOW()
                      AND end_at >= NOW()
                    ORDER BY discount_percent DESC
                    LIMIT 1
                ) ac ON TRUE
                {$whereSql}
                ORDER BY {$orderSql}
                LIMIT :limit OFFSET :offset";

        $st = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value);
        }
        $st->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function countForCatalog(array $filters): int
    {
        $pdo = DB::conn();
        [$whereSql, $params] = self::buildCatalogWhere($filters);

        $sql = "WITH variant_stats AS (
                    SELECT v.product_id,
                           MIN(v.sale_price) AS price_from
                    FROM product_variants v
                    WHERE v.is_active = TRUE
                    GROUP BY v.product_id
                )
                SELECT COUNT(*)
                FROM products p
                LEFT JOIN variant_stats vs ON vs.product_id = p.id
                LEFT JOIN categories c ON c.id = p.category_id
                {$whereSql}";

        $st = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value);
        }
        $st->execute();

        return (int)$st->fetchColumn();
    }

    private static function buildCatalogWhere(array $filters): array
    {
        $conditions = ['p.is_active = TRUE'];
        $params = [];

        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $conditions[] = 'p.name ILIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $category = trim((string)($filters['category'] ?? ''));
        if ($category !== '') {
            $conditions[] = 'c.slug = :category_slug';
            $params['category_slug'] = $category;
        }

        $minPrice = $filters['min_price'] ?? null;
        if ($minPrice !== null && is_numeric((string)$minPrice)) {
            $conditions[] = 'COALESCE(vs.price_from, p.price, 0) >= :min_price';
            $params['min_price'] = (int)$minPrice;
        }

        $maxPrice = $filters['max_price'] ?? null;
        if ($maxPrice !== null && is_numeric((string)$maxPrice)) {
            $conditions[] = 'COALESCE(vs.price_from, p.price, 0) <= :max_price';
            $params['max_price'] = (int)$maxPrice;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    private static function buildCatalogOrder(string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'COALESCE(vs.price_from, p.price, 0) ASC, p.id DESC',
            'price_desc' => 'COALESCE(vs.price_from, p.price, 0) DESC, p.id DESC',
            'best_selling' => 'COALESCE(ss.sold_qty, 0) DESC, p.created_at DESC, p.id DESC',
            default => 'p.created_at DESC, p.id DESC',
        };
    }

    # Lấy chi tiết sản phẩm theo id kèm các biến thể
    public static function findWithVariants(int $id): ?array
    {
        $pdo = DB::conn();
    # kiểm tra sản phẩm tồn tại
    $st = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, b.name AS brand_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN brands b ON b.id = p.brand_id
         WHERE p.id = :id
         LIMIT 1"
    );
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

        $st3 = $pdo->prepare(
            "SELECT image_url
             FROM product_images
             WHERE product_id = :pid
             ORDER BY sort_order ASC, id ASC"
        );
        $st3->execute(['pid' => $id]);
        $images = array_values(array_filter(array_map(
            static fn(array $row): string => trim((string)($row['image_url'] ?? '')),
            $st3->fetchAll()
        )));

        $st4 = $pdo->prepare(
            "SELECT COALESCE(AVG(rating)::numeric(3,2), 0) AS avg_rating
             FROM reviews
             WHERE product_id = :pid
               AND status = 'visible'"
        );
        $st4->execute(['pid' => $id]);
        $avgRating = (float)($st4->fetchColumn() ?: 0);

        $product['variants'] = $variants;
        $product['price_from'] = isset($variants[0]['sale_price']) ? (int)$variants[0]['sale_price'] : 0;
        $product['base_price_from'] = isset($variants[0]['base_price']) ? (int)$variants[0]['base_price'] : 0;
        $stockTotal = 0;
        foreach ($variants as $variant) {
            $stockTotal += max(0, (int)($variant['stock'] ?? 0));
        }

        // Keep both keys for compatibility across old/new views.
        $product['stock_total'] = $stockTotal;
        $product['stock'] = $stockTotal;
        $product['images'] = $images;
        $product['image'] = $images[0] ?? '';
        $product['rating'] = $avgRating;

        // Apply active campaign discount
        $campaignDiscount = \App\Models\ProductDiscount::getActiveDiscountForProduct($id);
        $salePrice = (int)($product['price_from'] ?? 0);
        $product['discount_percent'] = $campaignDiscount;
        $product['original_price'] = $salePrice;
        if ($campaignDiscount > 0 && $salePrice > 0) {
            $product['price'] = max(0, (int)floor($salePrice * (100 - $campaignDiscount) / 100));
        } else {
            $product['price'] = $salePrice;
        }

        return $product;
    }

    # Lấy thông tin tóm tắt để đưa vào giỏ hàng
    public static function findForCart(int $id): ?array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT p.id, p.name, p.slug,
                    MIN(v.sale_price) AS price,
                    COALESCE(SUM(v.stock), 0) AS stock_total,
                    (
                        SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             WHERE p.id = :id
               AND p.is_active = TRUE
               AND v.is_active = TRUE
             GROUP BY p.id
             LIMIT 1"
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        $row['original_price'] = (int)$row['price'];
        $campaignDiscount = \App\Models\ProductDiscount::getActiveDiscountForProduct((int)$row['id']);
        if ($campaignDiscount > 0) {
            $row['discount_percent'] = $campaignDiscount;
            $row['price'] = max(0, (int)floor($row['original_price'] * (100 - $campaignDiscount) / 100));
        } else {
            $row['discount_percent'] = 0;
        }

        return $row;
    }

    # Lấy sản phẩm cùng danh mục (sản phẩm liên quan)
    public static function relatedByCategory(int $productId, int $categoryId, int $limit = 8): array
    {
        $pdo = DB::conn();
        
        // Nếu không có categoryId, không lấy sản phẩm liên quan
        if (!$categoryId) {
            return [];
        }

        $st = $pdo->prepare(
                "SELECT p.id, p.name, p.slug, p.short_description,
                    c.name AS category_name,
                    MIN(v.sale_price)::bigint AS original_price,
                    MIN(v.sale_price)::bigint AS price_from,
                    MIN(v.base_price)::bigint AS base_price_from,
                    COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                    CASE
                    WHEN ac.discount_percent IS NOT NULL AND ac.discount_percent > 0
                    THEN GREATEST(0, MIN(v.sale_price)
                         - FLOOR(MIN(v.sale_price)::numeric * ac.discount_percent / 100.0))::bigint
                    ELSE MIN(v.sale_price)::bigint
                    END AS price,
                    SUM(v.stock) AS stock_total,
                    (
                        SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN LATERAL (
                 SELECT discount_percent
                 FROM product_discount_campaigns
                 WHERE product_id = p.id
                   AND status = 'active'
                   AND start_at <= NOW()
                   AND end_at >= NOW()
                 ORDER BY discount_percent DESC
                 LIMIT 1
             ) ac ON TRUE
             WHERE p.category_id = :cat_id
               AND p.id != :prod_id
               AND p.is_active = TRUE
               AND v.is_active = TRUE
             GROUP BY p.id, c.name, ac.discount_percent
             ORDER BY p.id DESC
             LIMIT :lim"
        );
        $st->bindValue(':cat_id', $categoryId, \PDO::PARAM_INT);
        $st->bindValue(':prod_id', $productId, \PDO::PARAM_INT);
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();
        
        return $st->fetchAll();
    }

    public static function findIdBySlug(string $slug): ?int
    {
        $st = DB::conn()->prepare(
            "SELECT id
             FROM products
             WHERE slug = :slug
               AND is_active = TRUE
             LIMIT 1"
        );
        $st->execute(['slug' => trim($slug)]);
        $id = $st->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    public static function suggestForChat(
        string $keyword = '',
        ?int $minPrice = null,
        ?int $maxPrice = null,
        int $limit = 5,
        string $strictTypeKeyword = ''
    ): array
    {
        $pdo = DB::conn();

        $conditions = ['p.is_active = TRUE'];
        $params = [];

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $conditions[] = '(p.name ILIKE :keyword OR c.name ILIKE :keyword OR COALESCE(p.short_description, \'\') ILIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $strictTypeKeyword = trim($strictTypeKeyword);
        if ($strictTypeKeyword !== '') {
            $conditions[] = '(p.name ILIKE :strict_type OR c.name ILIKE :strict_type)';
            $params['strict_type'] = '%' . $strictTypeKeyword . '%';
        }

        if ($minPrice !== null) {
            $conditions[] = 'COALESCE(vs.price_from, p.price, 0) >= :min_price';
            $params['min_price'] = max(0, (int)$minPrice);
        }

        if ($maxPrice !== null) {
            $conditions[] = 'COALESCE(vs.price_from, p.price, 0) <= :max_price';
            $params['max_price'] = max(0, (int)$maxPrice);
        }

        $sql = "WITH variant_stats AS (
                    SELECT v.product_id,
                           MIN(v.sale_price) AS price_from,
                           MIN(v.base_price) AS base_price_from,
                           COALESCE(SUM(v.stock), 0) AS stock_total
                    FROM product_variants v
                    WHERE v.is_active = TRUE
                    GROUP BY v.product_id
                ), active_campaigns AS (
                    SELECT DISTINCT ON (dc.product_id)
                        dc.product_id,
                        dc.discount_percent
                    FROM product_discount_campaigns dc
                    WHERE dc.status = 'active'
                      AND dc.start_at <= NOW()
                      AND dc.end_at >= NOW()
                    ORDER BY dc.product_id, dc.discount_percent DESC
                ), first_image AS (
                    SELECT DISTINCT ON (pi.product_id)
                        pi.product_id,
                        pi.image_url
                    FROM product_images pi
                    ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
                )
                SELECT p.id,
                       p.name,
                       p.slug,
                       c.name AS category_name,
                       COALESCE(vs.stock_total, 0) AS stock_total,
                       COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                       COALESCE(vs.price_from, p.price, 0)::bigint AS original_price,
                       CASE
                           WHEN COALESCE(ac.discount_percent, 0) > 0
                           THEN GREATEST(
                                0,
                                COALESCE(vs.price_from, p.price, 0)
                                - FLOOR(COALESCE(vs.price_from, p.price, 0)::numeric * ac.discount_percent / 100.0)
                           )::bigint
                           ELSE COALESCE(vs.price_from, p.price, 0)::bigint
                       END AS price,
                       COALESCE(fi.image_url, '') AS image
                FROM products p
                LEFT JOIN variant_stats vs ON vs.product_id = p.id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN active_campaigns ac ON ac.product_id = p.id
                LEFT JOIN first_image fi ON fi.product_id = p.id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY COALESCE(vs.stock_total, 0) DESC, p.created_at DESC, p.id DESC
                LIMIT :limit";

        $st = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $st->bindValue(':' . $key, $value, $type);
        }
        $st->bindValue(':limit', max(1, min(10, $limit)), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function findSpecForChat(string $productTerm, string $spec = 'vram'): ?array
    {
        $productTerm = trim($productTerm);
        if ($productTerm === '') {
            return null;
        }

        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT p.id,
                    p.name,
                    p.slug,
                    COALESCE(p.short_description, '') AS short_description,
                    COALESCE(string_agg(DISTINCT (ot.name || ': ' || ov.value), ' | '), '') AS option_text
             FROM products p
             LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
             LEFT JOIN variant_option_values vov ON vov.variant_id = v.id
             LEFT JOIN option_values ov ON ov.id = vov.option_value_id
             LEFT JOIN option_types ot ON ot.id = ov.option_type_id
             WHERE p.is_active = TRUE
               AND (
                    p.name ILIKE :term
                    OR COALESCE(p.short_description, '') ILIKE :term
                    OR p.slug ILIKE :term
               )
             GROUP BY p.id, p.name, p.slug, p.short_description
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT 5"
        );
        $st->execute(['term' => '%' . $productTerm . '%']);
        $rows = $st->fetchAll();

        if ($rows === []) {
            return null;
        }

        foreach ($rows as $row) {
            $haystack = mb_strtolower(
                trim(
                    (string)($row['name'] ?? '')
                    . ' '
                    . (string)($row['short_description'] ?? '')
                    . ' '
                    . (string)($row['option_text'] ?? '')
                ),
                'UTF-8'
            );

            if ($spec === 'vram' && preg_match('/(\d{1,2})\s*gb/u', $haystack, $m)) {
                return [
                    'id' => (int)($row['id'] ?? 0),
                    'name' => (string)($row['name'] ?? ''),
                    'slug' => (string)($row['slug'] ?? ''),
                    'value' => (string)$m[1] . 'GB',
                ];
            }
        }

        return null;
    }

    public static function findDetailedForChat(int $productId): ?array
    {
        $pdo = DB::conn();
        $st = $pdo->prepare(
             "WITH variant_stats AS (
                SELECT v.product_id,
                       MIN(v.sale_price) AS price_from,
                       MIN(v.base_price) AS base_price_from,
                       COALESCE(SUM(v.stock), 0) AS stock_total,
                       COALESCE(string_agg(DISTINCT (ot.name || ': ' || ov.value), ' | '), '') AS option_text
                FROM product_variants v
                LEFT JOIN variant_option_values vov ON vov.variant_id = v.id
                LEFT JOIN option_values ov ON ov.id = vov.option_value_id
                LEFT JOIN option_types ot ON ot.id = ov.option_type_id
                WHERE v.is_active = TRUE
                GROUP BY v.product_id
              ), first_image AS (
              SELECT DISTINCT ON (pi.product_id)
                  pi.product_id,
                  pi.image_url
              FROM product_images pi
              ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
             )
             SELECT p.id,
                    p.name,
                    p.slug,
                    p.short_description,
                    p.description,
                    p.highlights,
                    p.technical_specs,
                    p.shipping_info,
                    p.warranty_months,
                    c.name AS category_name,
                    b.name AS brand_name,
                    COALESCE(vs.price_from, p.price, 0)::bigint AS price,
                    COALESCE(vs.base_price_from, p.price, 0)::bigint AS original_price,
                    COALESCE(vs.stock_total, 0) AS stock_total,
                          COALESCE(vs.option_text, '') AS option_text,
                          COALESCE(fi.image_url, '') AS image
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN variant_stats vs ON vs.product_id = p.id
                      LEFT JOIN first_image fi ON fi.product_id = p.id
             WHERE p.id = :id
               AND p.is_active = TRUE
             LIMIT 1"
        );
        $st->execute(['id' => $productId]);

        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        $discountPercent = \App\Models\ProductDiscount::getActiveDiscountForProduct((int)$row['id']);
        $row['discount_percent'] = $discountPercent;
        if ($discountPercent > 0) {
            $row['price'] = max(0, (int)floor(((int)$row['price']) * (100 - $discountPercent) / 100));
        }

        return $row;
    }

}