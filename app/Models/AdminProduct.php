<?php
namespace App\Models;

use App\Core\DB;
use App\Core\PricingCalculator;

final class AdminProduct {
    private const FINANCIAL_STATUSES = ['approved', 'shipping', 'done'];

    public static function list(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $pdo = DB::conn();

        $q = trim((string)($filters['q'] ?? ''));
        $categoryId = (int)($filters['category_id'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));
        $minPrice = (int)($filters['min_price'] ?? 0);
        $maxPrice = (int)($filters['max_price'] ?? 0);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(p.name ILIKE :q OR EXISTS (SELECT 1 FROM product_variants vv WHERE vv.product_id = p.id AND vv.sku ILIKE :q))';
            $params['q'] = '%' . $q . '%';
        }

        if ($categoryId > 0) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($status === 'in_stock') {
            $where[] = 'COALESCE(stock.stock_total, 0) > 0';
        } elseif ($status === 'out_of_stock') {
            $where[] = 'COALESCE(stock.stock_total, 0) <= 0';
        } elseif ($status === 'hidden') {
            $where[] = 'p.is_active = FALSE';
        }

        if ($minPrice > 0) {
            $where[] = 'COALESCE(v.sale_price, p.price, 0) >= :min_price';
            $params['min_price'] = $minPrice;
        }

        if ($maxPrice > 0) {
            $where[] = 'COALESCE(v.sale_price, p.price, 0) <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $stats = $pdo->query(
            "WITH s AS (
                SELECT p.id,
                       p.created_at,
                       COALESCE(SUM(v.stock), 0) AS stock_total,
                       p.is_active
                FROM products p
                LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
                GROUP BY p.id, p.created_at, p.is_active
            )
            SELECT
                COUNT(*)::bigint AS total_products,
                COUNT(*) FILTER (WHERE stock_total > 0)::bigint AS in_stock_products,
                COUNT(*) FILTER (WHERE stock_total <= 0)::bigint AS out_of_stock_products,
                COUNT(*) FILTER (WHERE date_trunc('month', created_at) = date_trunc('month', now()))::bigint AS new_this_month
            FROM s"
        )->fetch();

        $countSql = "
            SELECT COUNT(*)
            FROM products p
            LEFT JOIN LATERAL (
                SELECT COALESCE(SUM(vv.stock), 0) AS stock_total
                FROM product_variants vv
                WHERE vv.product_id = p.id AND vv.is_active = TRUE
            ) stock ON TRUE
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.combination_key = 'default'
            {$whereSql}
        ";

        $countSt = $pdo->prepare($countSql);
        $countSt->execute($params);
        $total = (int)$countSt->fetchColumn();

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                p.id,
                p.name,
                p.slug,
                p.category_id,
                p.brand_id,
                b.name AS brand_name,
                c.name AS category_name,
                COALESCE(img.image_url, '') AS image_url,
                p.warranty_months,
                p.short_description,
                p.is_active,
                p.created_at,
                p.cost_price,
                p.import_tax_percent,
                p.vat_percent,
                p.profit_percent,
                p.price,
                v.sku,
                v.base_price,
                v.sale_price,
                COALESCE(stock.stock_total, 0) AS stock_total
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.combination_key = 'default'
            LEFT JOIN LATERAL (
                SELECT COALESCE(SUM(vv.stock), 0) AS stock_total
                FROM product_variants vv
                WHERE vv.product_id = p.id AND vv.is_active = TRUE
            ) stock ON TRUE
            LEFT JOIN LATERAL (
                SELECT pi.image_url
                FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY pi.sort_order ASC, pi.id ASC
                LIMIT 1
            ) img ON TRUE
            {$whereSql}
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $st = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value);
        }
        $st->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $st->execute();

        return [
            'rows' => $st->fetchAll(),
            'stats' => [
                'total_products' => (int)($stats['total_products'] ?? 0),
                'in_stock_products' => (int)($stats['in_stock_products'] ?? 0),
                'out_of_stock_products' => (int)($stats['out_of_stock_products'] ?? 0),
                'new_this_month' => (int)($stats['new_this_month'] ?? 0),
            ],
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public static function find(int $id): ?array {
        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, b.name AS brand_name,
                    v.sku, v.base_price, v.sale_price, v.stock,
                    COALESCE(stock.stock_total, 0) AS stock_total
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN product_variants v ON v.product_id = p.id AND v.combination_key = 'default'
             LEFT JOIN LATERAL (
                 SELECT COALESCE(SUM(vv.stock), 0) AS stock_total
                 FROM product_variants vv
                 WHERE vv.product_id = p.id AND vv.is_active = TRUE
             ) stock ON TRUE
             WHERE p.id = :id
             LIMIT 1"
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        $row['images'] = self::images($id);
        return $row;
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

    /**
     * Tính giá bán dựa trên công thức: price = cost_price × (1 + import_tax + vat + profit)
     * @param int $costPrice Giá gốc
     * @param float $importTaxPercent % thuế nhập khẩu (0-100)
     * @param float $vatPercent % VAT (0-100)
     * @param float $profitPercent % lợi nhuận (0-100)
     * @return int Giá bán cuối cùng
     */
    public static function calculatePrice(
        int $costPrice,
        float $importTaxPercent = 0,
        float $vatPercent = 0,
        float $profitPercent = 0
    ): int {
        return PricingCalculator::calculate(
            $costPrice,
            PricingCalculator::percentToDecimal($importTaxPercent),
            PricingCalculator::percentToDecimal($vatPercent),
            PricingCalculator::percentToDecimal($profitPercent)
        );
    }

    public static function createWithDefaultVariant(array $data): int {
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // Tính giá bán từ giá gốc và các %
            $costPrice = (int)$data['cost_price'];
            $importTaxPercent = (float)($data['import_tax_percent'] ?? 0);
            $vatPercent = (float)($data['vat_percent'] ?? 0);
            $profitPercent = (float)($data['profit_percent'] ?? 0);
            $finalPrice = self::calculatePrice($costPrice, $importTaxPercent, $vatPercent, $profitPercent);

            $st = $pdo->prepare(
                "INSERT INTO products(
                    category_id, brand_id, name, slug, short_description, description, highlights,
                    technical_specs, shipping_info, warranty_months, is_active,
                    cost_price, import_tax_percent, vat_percent, profit_percent, price
                ) VALUES (
                    :category_id, :brand_id, :name, :slug, :short_description, :description, :highlights,
                    :technical_specs, :shipping_info, :warranty_months, :is_active,
                    :cost_price, :import_tax_percent, :vat_percent, :profit_percent, :price
                ) RETURNING id"
            );
            $st->execute([
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'],
                'name' => $data['name'],
                'slug' => $data['slug'] ?: null,
                'short_description' => $data['short_description'] ?: null,
                'description' => $data['description'] ?: null,
                'highlights' => $data['highlights'] ?: null,
                'technical_specs' => $data['technical_specs'] ?: null,
                'shipping_info' => $data['shipping_info'] ?: null,
                'warranty_months' => $data['warranty_months'],
                'is_active' => $data['is_active'] ? 'true' : 'false',
                'cost_price' => $costPrice,
                'import_tax_percent' => $importTaxPercent,
                'vat_percent' => $vatPercent,
                'profit_percent' => $profitPercent,
                'price' => $finalPrice
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
            // Tính giá bán từ giá gốc và các %
            $costPrice = (int)$pData['cost_price'];
            $importTaxPercent = (float)($pData['import_tax_percent'] ?? 0);
            $vatPercent = (float)($pData['vat_percent'] ?? 0);
            $profitPercent = (float)($pData['profit_percent'] ?? 0);
            $finalPrice = self::calculatePrice($costPrice, $importTaxPercent, $vatPercent, $profitPercent);

            $st = $pdo->prepare(
                "UPDATE products
                 SET category_id = :category_id,
                     brand_id = :brand_id,
                     name = :name,
                     slug = :slug,
                     short_description = :short_description,
                     description = :description,
                     highlights = :highlights,
                     technical_specs = :technical_specs,
                     shipping_info = :shipping_info,
                     warranty_months = :warranty_months,
                     is_active = :is_active,
                     cost_price = :cost_price,
                     import_tax_percent = :import_tax_percent,
                     vat_percent = :vat_percent,
                     profit_percent = :profit_percent,
                     price = :price
                 WHERE id = :id"
            );
            $st->execute([
                'id' => $id,
                'category_id' => $pData['category_id'],
                'brand_id' => $pData['brand_id'],
                'name' => $pData['name'],
                'slug' => $pData['slug'],
                'short_description' => $pData['short_description'],
                'description' => $pData['description'],
                'highlights' => $pData['highlights'],
                'technical_specs' => $pData['technical_specs'],
                'shipping_info' => $pData['shipping_info'],
                'warranty_months' => $pData['warranty_months'],
                'is_active' => $pData['is_active'] ? 'true' : 'false',
                'cost_price' => $costPrice,
                'import_tax_percent' => $importTaxPercent,
                'vat_percent' => $vatPercent,
                'profit_percent' => $profitPercent,
                'price' => $finalPrice
            ]);

            $variantId = null;

            $findDefault = $pdo->prepare(
                "SELECT id
                 FROM product_variants
                 WHERE product_id = :pid
                   AND combination_key = 'default'
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $findDefault->execute(['pid' => $id]);
            $variantId = $findDefault->fetchColumn();

            if (!$variantId) {
                $findAny = $pdo->prepare(
                    "SELECT id
                     FROM product_variants
                     WHERE product_id = :pid
                     ORDER BY is_active DESC, id ASC
                     LIMIT 1"
                );
                $findAny->execute(['pid' => $id]);
                $variantId = $findAny->fetchColumn();
            }

            if ($variantId) {
                $st2 = $pdo->prepare(
                    "UPDATE product_variants
                     SET sku = :sku,
                         base_price = :bp,
                         sale_price = :sp,
                         stock = :stock,
                         is_active = TRUE
                     WHERE id = :variant_id"
                );
                $st2->execute([
                    'variant_id' => (int)$variantId,
                    'sku' => $vData['sku'],
                    'bp' => $vData['base_price'],
                    'sp' => $vData['sale_price'],
                    'stock' => max(0, (int)$vData['stock']),
                ]);
            } else {
                // Product does not have any variant yet, create a default one.
                $insertDefault = $pdo->prepare(
                    "INSERT INTO product_variants (product_id, sku, combination_key, base_price, sale_price, stock, is_active)
                     VALUES (:pid, :sku, 'default', :bp, :sp, :stock, TRUE)"
                );
                $insertDefault->execute([
                    'pid' => $id,
                    'sku' => $vData['sku'],
                    'bp' => $vData['base_price'],
                    'sp' => $vData['sale_price'],
                    'stock' => max(0, (int)$vData['stock']),
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    public static function categories(): array
    {
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        return DB::conn()->query($sql)->fetchAll();
    }

    public static function discountRecommendations(int $limit = 12): array
    {
        $pdo = DB::conn();
        $statusesSql = "'" . implode("','", self::FINANCIAL_STATUSES) . "'";

        $sql = "
            WITH sold_stats AS (
                SELECT
                    COALESCE(oi.product_id, pv.product_id) AS product_id,
                    MAX(o.created_at) AS last_sold_at,
                    COALESCE(SUM(oi.qty), 0)::bigint AS sold_total
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                WHERE o.status IN ({$statusesSql})
                GROUP BY COALESCE(oi.product_id, pv.product_id)
            ), default_variant AS (
                SELECT DISTINCT ON (v.product_id)
                    v.product_id,
                    v.id AS variant_id,
                    v.base_price,
                    v.sale_price,
                    v.stock
                FROM product_variants v
                WHERE v.is_active = TRUE
                ORDER BY v.product_id, (v.combination_key = 'default') DESC, v.id ASC
            )
            SELECT
                p.id,
                p.name,
                dv.variant_id,
                COALESCE(dv.base_price, 0)::bigint AS base_price,
                COALESCE(dv.sale_price, 0)::bigint AS sale_price,
                COALESCE(dv.stock, 0)::bigint AS stock,
                ss.last_sold_at,
                COALESCE(ss.sold_total, 0)::bigint AS sold_total,
                CASE
                    WHEN ss.last_sold_at IS NULL THEN 9999
                    ELSE GREATEST(0, EXTRACT(DAY FROM NOW() - ss.last_sold_at)::int)
                END AS days_unsold,
                GREATEST(0, COALESCE(dv.sale_price, 0) - COALESCE(dv.base_price, 0))::bigint AS max_discount_amount
            FROM products p
            JOIN default_variant dv ON dv.product_id = p.id
            LEFT JOIN sold_stats ss ON ss.product_id = p.id
            WHERE p.is_active = TRUE
              AND COALESCE(dv.stock, 0) > 0
              AND COALESCE(dv.sale_price, 0) > COALESCE(dv.base_price, 0)
            ORDER BY days_unsold DESC, sold_total ASC, p.created_at ASC
            LIMIT :limit
        ";

        $st = $pdo->prepare($sql);
        $st->bindValue(':limit', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll() ?: [];

        foreach ($rows as &$row) {
            $salePrice = (int)($row['sale_price'] ?? 0);
            $maxDiscountAmount = (int)($row['max_discount_amount'] ?? 0);
            $maxPercent = $salePrice > 0
                ? (int)floor(($maxDiscountAmount * 100) / $salePrice)
                : 0;

            $daysUnsold = (int)($row['days_unsold'] ?? 0);
            $recommendedPercent = 5;
            if ($daysUnsold >= 180) {
                $recommendedPercent = 20;
            } elseif ($daysUnsold >= 120) {
                $recommendedPercent = 15;
            } elseif ($daysUnsold >= 60) {
                $recommendedPercent = 10;
            }

            $row['max_discount_percent'] = max(0, min(90, $maxPercent));
            $row['recommended_percent'] = max(0, min((int)$row['max_discount_percent'], $recommendedPercent));
        }
        unset($row);

        return $rows;
    }

    public static function applyDiscountPercent(int $productId, int $percent): array
    {
        if ($productId <= 0) {
            return ['ok' => false, 'error' => 'not-found'];
        }

        if ($percent <= 0 || $percent > 90) {
            return ['ok' => false, 'error' => 'invalid-percent'];
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                "SELECT id, base_price, sale_price
                 FROM product_variants
                 WHERE product_id = :pid
                   AND is_active = TRUE
                 ORDER BY (combination_key = 'default') DESC, id ASC
                 FOR UPDATE"
            );
            $st->execute(['pid' => $productId]);
            $variants = $st->fetchAll() ?: [];
            if (empty($variants)) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'not-found'];
            }

            $update = $pdo->prepare(
                "UPDATE product_variants
                 SET sale_price = :sale_price
                 WHERE id = :id"
            );

            foreach ($variants as $variant) {
                $variantId = (int)($variant['id'] ?? 0);
                $basePrice = (int)($variant['base_price'] ?? 0);
                $salePrice = (int)($variant['sale_price'] ?? 0);

                if ($salePrice <= 0 || $variantId <= 0) {
                    continue;
                }

                $requestedDiscount = (int)floor(($salePrice * $percent) / 100);
                $maxDiscount = max(0, $salePrice - $basePrice);

                if ($requestedDiscount <= 0) {
                    continue;
                }

                if ($requestedDiscount > $maxDiscount) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => 'over-profit'];
                }

                $newSalePrice = $salePrice - $requestedDiscount;
                if ($newSalePrice < $basePrice) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => 'over-profit'];
                }

                $update->execute([
                    'sale_price' => $newSalePrice,
                    'id' => $variantId,
                ]);
            }

            $pdo->commit();
            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'failed'];
        }
    }

    public static function brands(): array
    {
        $sql = "SELECT id, name FROM brands ORDER BY name ASC";
        return DB::conn()->query($sql)->fetchAll();
    }

    public static function resolveBrandId(string $brandName): ?int
    {
        $name = trim($brandName);
        if ($name === '') {
            return null;
        }

        $pdo = DB::conn();

        $find = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $find->execute(['name' => $name]);
        $existingId = $find->fetchColumn();
        if ($existingId) {
            return (int)$existingId;
        }

        $baseSlug = self::slugifyBrandName($name);
        $slug = self::uniqueBrandSlug($baseSlug, $pdo);

        try {
            $insert = $pdo->prepare(
                "INSERT INTO brands(name, slug, created_at)
                 VALUES (:name, :slug, now())
                 RETURNING id"
            );
            $insert->execute([
                'name' => $name,
                'slug' => $slug,
            ]);
            return (int)$insert->fetchColumn();
        } catch (\PDOException $e) {
            // In case another request creates the same brand concurrently.
            $find->execute(['name' => $name]);
            $retryId = $find->fetchColumn();
            if ($retryId) {
                return (int)$retryId;
            }
            throw $e;
        }
    }

    private static function slugifyBrandName(string $value): string
    {
        $slug = mb_strtolower(trim($value), 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if (is_string($ascii) && $ascii !== '') {
            $slug = mb_strtolower($ascii, 'UTF-8');
        }

        $slug = str_replace(['đ', 'Đ'], 'd', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug) ?? '';
        $slug = preg_replace('/\s+/', '-', trim($slug)) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';

        return $slug !== '' ? $slug : 'thuong-hieu';
    }

    private static function uniqueBrandSlug(string $baseSlug, \PDO $pdo): string
    {
        $slug = $baseSlug;
        $suffix = 2;
        $check = $pdo->prepare("SELECT 1 FROM brands WHERE slug = :slug LIMIT 1");

        while (true) {
            $check->execute(['slug' => $slug]);
            if (!$check->fetchColumn()) {
                return $slug;
            }
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
    }

    public static function images(int $productId): array
    {
        $st = DB::conn()->prepare(
            "SELECT id, image_url, sort_order
             FROM product_images
             WHERE product_id = :pid
             ORDER BY sort_order ASC, id ASC"
        );
        $st->execute(['pid' => $productId]);
        return $st->fetchAll();
    }

    public static function deleteImages(int $productId, array $imageIds): void
    {
        if ($productId <= 0 || $imageIds === []) {
            return;
        }

        $ids = [];
        foreach ($imageIds as $id) {
            $intId = (int)$id;
            if ($intId > 0) {
                $ids[] = $intId;
            }
        }

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return;
        }

        $pdo = DB::conn();
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM product_images
                WHERE product_id = ?
                  AND id IN (" . $placeholders . ")";

        $params = array_merge([$productId], $ids);
        $st = $pdo->prepare($sql);
        $st->execute($params);
    }

    public static function saveImages(int $productId, ?string $mainImageUrl, array $galleryImageUrls = []): void
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            if ($mainImageUrl !== null && $mainImageUrl !== '') {
                $stMain = $pdo->prepare(
                    "SELECT id
                     FROM product_images
                     WHERE product_id = :pid AND sort_order = 0
                     ORDER BY id ASC
                     LIMIT 1"
                );
                $stMain->execute(['pid' => $productId]);
                $mainId = $stMain->fetchColumn();

                if ($mainId) {
                    $upMain = $pdo->prepare("UPDATE product_images SET image_url = :url WHERE id = :id");
                    $upMain->execute(['url' => $mainImageUrl, 'id' => (int)$mainId]);
                } else {
                    $inMain = $pdo->prepare(
                        "INSERT INTO product_images(product_id, image_url, sort_order)
                         VALUES (:pid, :url, 0)"
                    );
                    $inMain->execute(['pid' => $productId, 'url' => $mainImageUrl]);
                }
            }

            if (!empty($galleryImageUrls)) {
                $maxSortSt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = :pid");
                $maxSortSt->execute(['pid' => $productId]);
                $sort = (int)$maxSortSt->fetchColumn();

                $ins = $pdo->prepare(
                    "INSERT INTO product_images(product_id, image_url, sort_order)
                     VALUES (:pid, :url, :sort_order)"
                );

                foreach ($galleryImageUrls as $url) {
                    if ($url === '') {
                        continue;
                    }
                    $sort++;
                    $ins->execute([
                        'pid' => $productId,
                        'url' => $url,
                        'sort_order' => $sort,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void {
        DB::conn()->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
}