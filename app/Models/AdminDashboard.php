<?php
namespace App\Models;
use App\Core\DB;

class AdminDashboard {
    private const FINANCIAL_STATUSES = ['approved', 'shipping', 'done'];

    private static function financialStatusSqlList(): string
    {
        return "'" . implode("','", self::FINANCIAL_STATUSES) . "'";
    }

    private static function financialRowsCte(): string
    {
        $statusList = self::financialStatusSqlList();

        return "
            WITH financial_rows AS (
                SELECT
                    o.id AS order_id,
                    o.created_at,
                    COALESCE(oi.product_id, v.product_id) AS product_id,
                    oi.product_name,
                    oi.qty,
                    COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0)::numeric AS selling_price,
                    COALESCE(oi.cost_price, oi.base_price, 0)::numeric AS cost_price,
                    COALESCE(oi.vat_percent, 0)::numeric AS vat_percent,
                    COALESCE(oi.import_tax_percent, 0)::numeric AS import_tax_percent,
                    COALESCE(oi.profit_percent, 0)::numeric AS profit_percent,
                    (
                        CASE
                            WHEN COALESCE(o.subtotal, 0) > 0 THEN
                                LEAST(
                                    COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric,
                                    (COALESCE(o.discount_total, 0)::numeric * COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric)
                                    / NULLIF(COALESCE(o.subtotal, 0)::numeric, 0)
                                )
                            ELSE 0::numeric
                        END
                    ) AS discount_alloc,
                    GREATEST(
                        0::numeric,
                        COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric
                        -
                        CASE
                            WHEN COALESCE(o.subtotal, 0) > 0 THEN
                                LEAST(
                                    COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric,
                                    (COALESCE(o.discount_total, 0)::numeric * COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric)
                                    / NULLIF(COALESCE(o.subtotal, 0)::numeric, 0)
                                )
                            ELSE 0::numeric
                        END
                    ) AS revenue,
                    (
                        COALESCE(oi.profit_amount, ((COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) - COALESCE(oi.cost_price, oi.base_price, 0)) * oi.qty), 0)::numeric
                        -
                        CASE
                            WHEN COALESCE(o.subtotal, 0) > 0 THEN
                                LEAST(
                                    COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric,
                                    (COALESCE(o.discount_total, 0)::numeric * COALESCE(oi.line_total, (COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty), 0)::numeric)
                                    / NULLIF(COALESCE(o.subtotal, 0)::numeric, 0)
                                )
                            ELSE 0::numeric
                        END
                    ) AS profit_amount,
                    (COALESCE(oi.cost_price, oi.base_price, 0) * oi.qty)::numeric AS cost,
                    ((COALESCE(oi.vat_percent, 0) / 100.0) * COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty)::numeric AS vat_tax,
                    ((COALESCE(oi.import_tax_percent, 0) / 100.0) * COALESCE(oi.cost_price, oi.base_price, 0) * oi.qty)::numeric AS import_tax
                FROM orders o
                JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN product_variants v ON v.id = oi.variant_id
                WHERE o.status IN ({$statusList})
            )
        ";
    }

    public static function getData(): array {
        $pdo = DB::conn();

        $stats = $pdo->query(
            self::financialRowsCte() . "
            SELECT
                (SELECT COUNT(*) FROM products) AS total_products,
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM orders) AS total_orders,
                COALESCE(ROUND(SUM(revenue)), 0)::bigint AS total_revenue,
                (
                    SELECT COALESCE(ROUND(SUM(COALESCE(NULLIF(v.base_price, 0), p.cost_price, 0) * v.stock)), 0)::bigint
                    FROM products p
                    JOIN product_variants v ON v.product_id = p.id
                    WHERE v.is_active = TRUE
                ) AS total_cost,
                COALESCE(ROUND(SUM(vat_tax + import_tax)), 0)::bigint AS total_tax,
                COALESCE(ROUND(SUM(profit_amount)), 0)::bigint AS total_profit
            FROM financial_rows
        ")->fetch();

        $timeStats = $pdo->query(
            self::financialRowsCte() . "
            SELECT
                COALESCE(ROUND(SUM(revenue) FILTER (WHERE created_at::date = CURRENT_DATE)), 0)::bigint AS revenue_today,
                COALESCE(ROUND(SUM(revenue) FILTER (WHERE date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE))), 0)::bigint AS revenue_month,
                COALESCE(ROUND(SUM(profit_amount) FILTER (WHERE date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE))), 0)::bigint AS profit_month,
                COALESCE(ROUND(SUM(vat_tax + import_tax) FILTER (WHERE date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE))), 0)::bigint AS total_tax_month,
                (
                    SELECT COUNT(*)
                    FROM orders
                    WHERE status IN (" . self::financialStatusSqlList() . ")
                      AND created_at::date = CURRENT_DATE
                )::bigint AS orders_today
            FROM financial_rows
        ")->fetch();

        $chartStmt = $pdo->prepare(
            self::financialRowsCte() . "
            , months AS (
                SELECT generate_series(1, 12) AS month_num
            )
            SELECT
                months.month_num,
                COALESCE(ROUND(SUM(financial_rows.revenue)), 0)::bigint AS revenue,
                COALESCE(ROUND(SUM(financial_rows.profit_amount)), 0)::bigint AS profit,
                COALESCE(ROUND(SUM(financial_rows.vat_tax + financial_rows.import_tax)), 0)::bigint AS tax
            FROM months
            LEFT JOIN financial_rows
              ON EXTRACT(MONTH FROM financial_rows.created_at) = months.month_num
             AND EXTRACT(YEAR FROM financial_rows.created_at) = :chart_year
            GROUP BY months.month_num
            ORDER BY months.month_num ASC
        ");
        $chartStmt->execute(['chart_year' => (int)date('Y')]);
        $chartRows = $chartStmt->fetchAll();

        $topProducts = $pdo->query(
            self::financialRowsCte() . "
            SELECT
                product_id,
                MIN(product_name) AS product_name,
                COALESCE(SUM(qty), 0)::bigint AS quantity_sold,
                COALESCE(ROUND(SUM(revenue)), 0)::bigint AS revenue,
                COALESCE(ROUND(SUM(profit_amount)), 0)::bigint AS profit
            FROM financial_rows
            GROUP BY product_id
            ORDER BY quantity_sold DESC, revenue DESC, product_id DESC
            LIMIT 5
        ")->fetchAll();

        $recentOrders = $pdo->query(
            "SELECT
                o.id,
                o.status,
                o.total,
                o.created_at,
                COALESCE(NULLIF(oa.full_name, ''), cp.full_name, ep.full_name, split_part(u.email, '@', 1)) AS customer_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN order_addresses oa ON oa.order_id = o.id
             LEFT JOIN customer_profiles cp ON cp.user_id = u.id
             LEFT JOIN employee_profiles ep ON ep.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT 5"
        )->fetchAll();

        $lowStockProducts = $pdo->query(
            "WITH variant_stats AS (
                SELECT
                    v.product_id,
                    COALESCE(SUM(v.stock), 0)::bigint AS stock_total,
                    COALESCE(MIN(v.sale_price), 0)::bigint AS base_price
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
                ORDER BY dc.product_id, dc.created_at DESC, dc.id DESC
             )
             SELECT
                p.id,
                p.name,
                COALESCE(vs.stock_total, 0)::bigint AS stock_total,
                COALESCE(vs.base_price, 0)::bigint AS base_price,
                COALESCE(ac.discount_percent, 0)::int AS discount_percent,
                GREATEST(
                    0,
                    COALESCE(vs.base_price, 0)
                    - FLOOR(COALESCE(vs.base_price, 0) * COALESCE(ac.discount_percent, 0) / 100.0)
                )::bigint AS sale_price
             FROM products p
             LEFT JOIN variant_stats vs ON vs.product_id = p.id
             LEFT JOIN active_campaigns ac ON ac.product_id = p.id
             WHERE p.is_active = TRUE
             GROUP BY p.id, p.name, vs.stock_total, vs.base_price, ac.discount_percent
             HAVING COALESCE(vs.stock_total, 0) < 5
             ORDER BY stock_total ASC, p.name ASC
             LIMIT 10"
        )->fetchAll();

        $labels = [];
        $revenueSeries = [];
        $profitSeries = [];
        $taxSeries = [];
        foreach ($chartRows as $row) {
            $month = (int)($row['month_num'] ?? 0);
            $labels[] = 'T' . $month;
            $revenueSeries[] = (int)($row['revenue'] ?? 0);
            $profitSeries[] = (int)($row['profit'] ?? 0);
            $taxSeries[] = (int)($row['tax'] ?? 0);
        }

        return [
            'stats' => [
                'total_products' => (int)($stats['total_products'] ?? 0),
                'total_users' => (int)($stats['total_users'] ?? 0),
                'total_orders' => (int)($stats['total_orders'] ?? 0),
                'total_revenue' => (int)($stats['total_revenue'] ?? 0),
                'total_cost' => (int)($stats['total_cost'] ?? 0),
                'total_tax' => (int)($stats['total_tax'] ?? 0),
                'total_profit' => (int)($stats['total_profit'] ?? 0),
            ],
            'time_stats' => [
                'revenue_today' => (int)($timeStats['revenue_today'] ?? 0),
                'revenue_month' => (int)($timeStats['revenue_month'] ?? 0),
                'profit_month' => (int)($timeStats['profit_month'] ?? 0),
                'total_tax_month' => (int)($timeStats['total_tax_month'] ?? 0),
                'orders_today' => (int)($timeStats['orders_today'] ?? 0),
            ],
            'chart' => [
                'labels' => $labels,
                'revenue' => $revenueSeries,
                'profit' => $profitSeries,
                'tax' => $taxSeries,
            ],
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStockProducts,
        ];
    }
}