<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class AdminInventory
{
    public static function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $pdo = DB::conn();

        $q = trim((string)($filters['q'] ?? ''));
        $categoryId = (int)($filters['category_id'] ?? 0);
        $stockRange = trim((string)($filters['stock_range'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = 'p.name ILIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($categoryId > 0) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $stockExpr = "COALESCE((
            SELECT SUM(v.stock)
            FROM product_variants v
            WHERE v.product_id = p.id
              AND v.is_active = TRUE
        ), 0)";

        if ($stockRange === 'out') {
            $where[] = $stockExpr . ' = 0';
        } elseif ($stockRange === '1-5') {
            $where[] = $stockExpr . ' BETWEEN 1 AND 5';
        } elseif ($stockRange === '6-10') {
            $where[] = $stockExpr . ' BETWEEN 6 AND 10';
        } elseif ($stockRange === '11-20') {
            $where[] = $stockExpr . ' BETWEEN 11 AND 20';
        } elseif ($stockRange === '21-50') {
            $where[] = $stockExpr . ' BETWEEN 21 AND 50';
        } elseif ($stockRange === '51+') {
            $where[] = $stockExpr . ' >= 51';
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSql = "SELECT COUNT(*)
                     FROM products p
                     {$whereSql}";
        $countSt = $pdo->prepare($countSql);
        foreach ($params as $k => $v) {
            $countSt->bindValue(':' . $k, $v);
        }
        $countSt->execute();
        $total = (int)$countSt->fetchColumn();

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $listSql = "SELECT p.id,
                           p.name,
                           COALESCE(c.name, 'Chưa phân loại') AS category_name,
                           COALESCE(p.price, 0) AS price,
                           COALESCE(stock.stock_total, 0) AS stock,
                           p.created_at
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN LATERAL (
                        SELECT COALESCE(SUM(v.stock), 0) AS stock_total
                        FROM product_variants v
                        WHERE v.product_id = p.id
                          AND v.is_active = TRUE
                    ) stock ON TRUE
                    {$whereSql}
                    ORDER BY p.id DESC
                    LIMIT :limit OFFSET :offset";

        $st = $pdo->prepare($listSql);
        foreach ($params as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return [
            'rows' => $st->fetchAll(),
            'stats' => self::stats(),
            'low_stock' => self::lowStockItems(),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public static function stats(): array
    {
        $sql = "WITH stock_per_product AS (
                    SELECT p.id,
                           COALESCE(SUM(v.stock), 0) AS stock_total
                    FROM products p
                    LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
                    GROUP BY p.id
                )
                SELECT
                    COUNT(*) AS total_products,
                    COUNT(*) FILTER (WHERE stock_total > 10) AS in_stock,
                    COUNT(*) FILTER (WHERE stock_total BETWEEN 1 AND 10) AS low_stock,
                    COUNT(*) FILTER (WHERE stock_total = 0) AS out_of_stock
                FROM stock_per_product";

        $row = DB::conn()->query($sql)->fetch() ?: [];

        return [
            'total_products' => (int)($row['total_products'] ?? 0),
            'in_stock' => (int)($row['in_stock'] ?? 0),
            'low_stock' => (int)($row['low_stock'] ?? 0),
            'out_of_stock' => (int)($row['out_of_stock'] ?? 0),
        ];
    }

    public static function lowStockItems(int $limit = 8): array
    {
        $st = DB::conn()->prepare(
            "SELECT p.id, p.name, COALESCE(SUM(v.stock), 0) AS stock
             FROM products p
             LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
             GROUP BY p.id, p.name
             HAVING COALESCE(SUM(v.stock), 0) < 5
             ORDER BY stock ASC, p.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function categories(): array
    {
        return DB::conn()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
    }

    public static function find(int $productId): ?array
    {
        $st = DB::conn()->prepare(
            "SELECT p.id,
                    p.name,
                    COALESCE(c.name, 'Chưa phân loại') AS category_name,
                    COALESCE(p.price, 0) AS price,
                    COALESCE(stock.stock_total, 0) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN LATERAL (
                SELECT COALESCE(SUM(v.stock), 0) AS stock_total
                FROM product_variants v
                WHERE v.product_id = p.id
                  AND v.is_active = TRUE
             ) stock ON TRUE
             WHERE p.id = :id
             LIMIT 1"
        );
        $st->execute(['id' => $productId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function importStock(int $productId, int $quantity, string $note = ''): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Số lượng nhập phải lớn hơn 0.');
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $variantSt = $pdo->prepare(
                "SELECT id, stock
                 FROM product_variants
                 WHERE product_id = :product_id
                   AND is_active = TRUE
                 ORDER BY CASE WHEN combination_key = 'default' THEN 0 ELSE 1 END, id ASC
                 LIMIT 1
                 FOR UPDATE"
            );
            $variantSt->execute(['product_id' => $productId]);
            $variant = $variantSt->fetch();

            if (!$variant) {
                throw new \RuntimeException('Không tìm thấy biến thể sản phẩm để nhập kho.');
            }

            $updateSt = $pdo->prepare('UPDATE product_variants SET stock = stock + :qty WHERE id = :id');
            $updateSt->execute([
                'qty' => $quantity,
                'id' => (int)$variant['id'],
            ]);

            InventoryLog::create($productId, $quantity, 'import', $note, $pdo);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
