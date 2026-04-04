<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class ProductDiscount
{
    private const FINANCIAL_STATUSES = ['approved', 'shipping', 'done'];

    public static function adminList(int $page = 1, int $perPage = 12): array
    {
        $pdo = DB::conn();

        $countSt = $pdo->query('SELECT COUNT(*) FROM product_discount_campaigns');
        $total = (int)$countSt->fetchColumn();

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT
                dc.id,
                dc.product_id,
                dc.discount_percent,
                dc.start_at,
                dc.end_at,
                dc.status,
                dc.created_at,
                p.name AS product_name,
                COALESCE(v.base_price, 0)::bigint AS base_price,
                COALESCE(v.sale_price, 0)::bigint AS sale_price,
                GREATEST(0, COALESCE(v.sale_price, 0) - COALESCE(v.base_price, 0))::bigint AS max_discount_amount
             FROM product_discount_campaigns dc
             JOIN products p ON p.id = dc.product_id
             LEFT JOIN LATERAL (
                 SELECT vv.base_price, vv.sale_price
                 FROM product_variants vv
                 WHERE vv.product_id = p.id AND vv.is_active = TRUE
                 ORDER BY (vv.combination_key = 'default') DESC, vv.id ASC
                 LIMIT 1
             ) v ON TRUE
             ORDER BY dc.created_at DESC, dc.id DESC
             LIMIT :limit OFFSET :offset"
        );
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return [
            'rows' => $st->fetchAll() ?: [],
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
            'stats' => self::stats(),
        ];
    }

    public static function stats(): array
    {
        $row = DB::conn()->query(
            "SELECT
                COUNT(*)::bigint AS total,
                COUNT(*) FILTER (WHERE status = 'active')::bigint AS active_count,
                COUNT(*) FILTER (WHERE status = 'disabled')::bigint AS disabled_count,
                COUNT(*) FILTER (
                    WHERE status = 'active'
                      AND start_at <= NOW()
                      AND end_at >= NOW()
                )::bigint AS running_count
             FROM product_discount_campaigns"
        )->fetch() ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'active_count' => (int)($row['active_count'] ?? 0),
            'disabled_count' => (int)($row['disabled_count'] ?? 0),
            'running_count' => (int)($row['running_count'] ?? 0),
        ];
    }

    public static function recommendations(int $limit = 12): array
    {
        $pdo = DB::conn();
        $statusesSql = "'" . implode("','", self::FINANCIAL_STATUSES) . "'";

        $st = $pdo->prepare(
            "WITH sold_stats AS (
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
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
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
            $row['recommended_percent'] = max(1, min((int)$row['max_discount_percent'], $recommendedPercent));
        }
        unset($row);

        return $rows;
    }

    public static function create(array $input): array
    {
        $productId = (int)($input['product_id'] ?? 0);
        $percent = (int)($input['discount_percent'] ?? 0);
        $startAt = trim((string)($input['start_at'] ?? ''));
        $endAt = trim((string)($input['end_at'] ?? ''));

        if ($productId <= 0) {
            return ['ok' => false, 'error' => 'not-found'];
        }
        if ($percent <= 0 || $percent > 90) {
            return ['ok' => false, 'error' => 'invalid-percent'];
        }

        $normalizedStart = self::normalizeDateTime($startAt);
        $normalizedEnd = self::normalizeDateTime($endAt);
        if ($normalizedStart === '' || $normalizedEnd === '') {
            return ['ok' => false, 'error' => 'invalid-time'];
        }
        if (strtotime($normalizedStart) >= strtotime($normalizedEnd)) {
            return ['ok' => false, 'error' => 'invalid-time-range'];
        }

        $pdo = DB::conn();
        $stVariant = $pdo->prepare(
            "SELECT base_price, sale_price
             FROM product_variants
             WHERE product_id = :pid
               AND is_active = TRUE
             ORDER BY (combination_key = 'default') DESC, id ASC
             LIMIT 1"
        );
        $stVariant->execute(['pid' => $productId]);
        $variant = $stVariant->fetch();
        if (!$variant) {
            return ['ok' => false, 'error' => 'not-found'];
        }

        $basePrice = (int)($variant['base_price'] ?? 0);
        $salePrice = (int)($variant['sale_price'] ?? 0);
        if ($salePrice <= 0) {
            return ['ok' => false, 'error' => 'not-found'];
        }

        $discountAmount = (int)floor(($salePrice * $percent) / 100);
        $maxDiscount = max(0, $salePrice - $basePrice);
        if ($discountAmount > $maxDiscount) {
            return ['ok' => false, 'error' => 'over-profit'];
        }

        $st = $pdo->prepare(
            "INSERT INTO product_discount_campaigns
                (product_id, discount_percent, start_at, end_at, status)
             VALUES
                (:product_id, :discount_percent, :start_at, :end_at, 'active')"
        );
        $st->execute([
            'product_id' => $productId,
            'discount_percent' => $percent,
            'start_at' => $normalizedStart,
            'end_at' => $normalizedEnd,
        ]);

        return ['ok' => true];
    }

    public static function getActiveDiscountForProduct(int $productId): int
    {
        $st = DB::conn()->prepare(
            "SELECT discount_percent
             FROM product_discount_campaigns
             WHERE product_id = :pid
               AND status = 'active'
               AND start_at <= NOW()
               AND end_at >= NOW()
             ORDER BY discount_percent DESC
             LIMIT 1"
        );
        $st->execute(['pid' => $productId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public static function allActiveProducts(): array
    {
        $st = DB::conn()->query(
            "SELECT
                p.id,
                p.name,
                COALESCE(v.base_price, 0)::bigint AS base_price,
                COALESCE(v.sale_price, 0)::bigint AS sale_price,
                GREATEST(0, COALESCE(v.sale_price, 0) - COALESCE(v.base_price, 0))::bigint AS max_discount_amount
             FROM products p
             LEFT JOIN LATERAL (
                 SELECT vv.base_price, vv.sale_price
                 FROM product_variants vv
                 WHERE vv.product_id = p.id AND vv.is_active = TRUE
                 ORDER BY (vv.combination_key = 'default') DESC, vv.id ASC
                 LIMIT 1
             ) v ON TRUE
             WHERE p.is_active = TRUE
             ORDER BY p.name ASC"
        );
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $salePrice = (int)($row['sale_price'] ?? 0);
            $maxDiscountAmount = (int)($row['max_discount_amount'] ?? 0);
            $maxPercent = $salePrice > 0
                ? (int)floor(($maxDiscountAmount * 100) / $salePrice)
                : 0;
            $row['max_discount_percent'] = max(0, min(90, $maxPercent));
        }
        unset($row);
        return $rows;
    }

    public static function toggle(int $id): string
    {
        $row = self::find($id);
        if (!$row) {
            return 'not-found';
        }

        $next = ((string)($row['status'] ?? 'active') === 'active') ? 'disabled' : 'active';
        $st = DB::conn()->prepare('UPDATE product_discount_campaigns SET status = :status WHERE id = :id');
        $st->execute([
            'status' => $next,
            'id' => $id,
        ]);

        return $next;
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare('DELETE FROM product_discount_campaigns WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->rowCount() > 0;
    }

    public static function find(int $id): ?array
    {
        $st = DB::conn()->prepare('SELECT id, product_id, discount_percent, start_at, end_at, status FROM product_discount_campaigns WHERE id = :id LIMIT 1');
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    private static function normalizeDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $formats = ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        return '';
    }
}
