<?php
namespace App\Models;

use App\Core\DB;

final class Report
{
    private const FINANCIAL_STATUSES = ['approved', 'shipping', 'done'];

    public static function buildFilters(array $input): array
    {
        $rangeType = strtolower(trim((string)($input['range_type'] ?? 'month')));
        if (!in_array($rangeType, ['day', 'month', 'year', 'custom'], true)) {
            $rangeType = 'month';
        }

        $day = trim((string)($input['day'] ?? ''));
        $month = trim((string)($input['month'] ?? ''));
        $year = (int)($input['year'] ?? 0);
        $startDate = trim((string)($input['start_date'] ?? ''));
        $endDate = trim((string)($input['end_date'] ?? ''));

        if ($day === '') {
            $day = date('Y-m-d');
        }
        if ($month === '') {
            $month = date('Y-m');
        }
        if ($year <= 0) {
            $year = (int)date('Y');
        }
        if ($startDate === '') {
            $startDate = date('Y-m-01');
        }
        if ($endDate === '') {
            $endDate = date('Y-m-d');
        }

        if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        return [
            'range_type' => $rangeType,
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public static function fetchExportRows(array $filters): array
    {
        $pdo = DB::conn();
        $statusesSql = "'" . implode("','", self::FINANCIAL_STATUSES) . "'";

        $whereTime = '';
        $params = [];
        switch ($filters['range_type']) {
            case 'day':
                $whereTime = 'AND oi.created_at::date = :day_date';
                $params['day_date'] = $filters['day'];
                break;
            case 'year':
                $whereTime = 'AND EXTRACT(YEAR FROM oi.created_at) = :year_value';
                $params['year_value'] = $filters['year'];
                break;
            case 'custom':
                $whereTime = 'AND oi.created_at::date BETWEEN :start_date AND :end_date';
                $params['start_date'] = $filters['start_date'];
                $params['end_date'] = $filters['end_date'];
                break;
            case 'month':
            default:
                $whereTime = "AND to_char(oi.created_at, 'YYYY-MM') = :month_value";
                $params['month_value'] = $filters['month'];
                break;
        }

        $sql = "
            SELECT
                oi.created_at AS sale_datetime,
                o.id AS order_id,
                oi.product_name,
                oi.qty AS quantity,
                COALESCE(oi.cost_price, oi.base_price, 0) AS cost_price,
                COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) AS selling_price,
                COALESCE(oi.vat_percent, 0) AS vat_percent,
                ((COALESCE(oi.vat_percent, 0) / 100.0) * COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) * oi.qty) AS vat_tax,
                COALESCE(oi.import_tax_percent, 0) AS import_tax_percent,
                ((COALESCE(oi.import_tax_percent, 0) / 100.0) * COALESCE(oi.cost_price, oi.base_price, 0) * oi.qty) AS import_tax,
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
                ) AS discount_amount,
                (
                    COALESCE(
                        oi.profit_amount,
                        ((COALESCE(oi.selling_price, oi.unit_price, oi.sale_price, 0) - COALESCE(oi.cost_price, oi.base_price, 0)) * oi.qty)
                    )::numeric
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
                COALESCE(oi.profit_percent, 0) AS profit_percent,
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
                ) AS product_revenue
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.status IN ({$statusesSql})
              {$whereTime}
            ORDER BY oi.created_at ASC, o.id ASC, oi.id ASC
        ";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        $totals = [
            'total_revenue' => 0,
            'total_discount' => 0,
            'total_cost' => 0,
            'total_tax' => 0,
            'total_profit' => 0,
        ];

        foreach ($rows as $row) {
            $revenue = (int)($row['product_revenue'] ?? 0);
            $discount = (int)round((float)($row['discount_amount'] ?? 0));
            $cost = (int)($row['cost_price'] ?? 0) * (int)($row['quantity'] ?? 0);
            $tax = (int)round((float)($row['vat_tax'] ?? 0) + (float)($row['import_tax'] ?? 0));
            $profit = (int)($row['profit_amount'] ?? 0);

            $totals['total_revenue'] += $revenue;
            $totals['total_discount'] += $discount;
            $totals['total_cost'] += $cost;
            $totals['total_tax'] += $tax;
            $totals['total_profit'] += $profit;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
