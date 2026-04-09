<?php
namespace App\Models;

use App\Core\DB;
use App\Core\PricingCalculator;
use App\Models\InventoryLog;
use App\Models\Voucher;

final class Order
{
    public static function createFromCart(int $userId, array $cartItems, array $customerInfo, string $customerNote = '', ?int $userVoucherId = null): int
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $subtotal = 0;
            $lines = [];

            foreach ($cartItems as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = max(1, (int)($item['qty'] ?? 1));
                $cartUnitPrice = (int)($item['price'] ?? 0);
                $cartOriginalPrice = (int)($item['original_price'] ?? 0);
                $cartDiscountPercent = max(0, min(90, (int)($item['discount_percent'] ?? 0)));
                $cartDiscountPct = round($cartDiscountPercent / 100, 4);

                $st = $pdo->prepare(
                    "SELECT p.id AS product_id,
                        p.name AS product_name,
                        COALESCE(p.cost_price, 0) AS cost_price,
                        COALESCE(p.vat_percent, 0) AS vat_percent,
                        COALESCE(p.import_tax_percent, 0) AS import_tax_percent,
                        COALESCE(p.profit_percent, 0) AS profit_percent,
                        COALESCE(p.price, 0) AS product_price,
                            v.id AS variant_id,
                            COALESCE(v.sku, '') AS sku,
                        v.combination_key,
                            v.base_price,
                            v.sale_price
                     FROM products p
                     JOIN product_variants v ON v.product_id = p.id
                     WHERE p.id = :pid
                       AND p.is_active = TRUE
                       AND v.is_active = TRUE
                                         ORDER BY
                                                CASE WHEN v.combination_key = 'default' THEN 0 ELSE 1 END,
                                                v.id ASC
                     LIMIT 1"
                );
                $st->execute(['pid' => $productId]);
                $row = $st->fetch();

                if (!$row) {
                    throw new \RuntimeException('San pham khong hop le de dat hang.');
                }

                $costPrice = (int)($row['cost_price'] ?? 0);
                $vatPercent = (float)($row['vat_percent'] ?? 0);
                $importTaxPercent = (float)($row['import_tax_percent'] ?? 0);
                $profitPercent = (float)($row['profit_percent'] ?? 0);
                $sellingPrice = (int)($row['product_price'] ?? 0);

                if ($sellingPrice <= 0) {
                    $sellingPrice = PricingCalculator::calculate(
                        $costPrice,
                        PricingCalculator::percentToDecimal($importTaxPercent),
                        PricingCalculator::percentToDecimal($vatPercent),
                        PricingCalculator::percentToDecimal($profitPercent)
                    );
                }

                if ($sellingPrice <= 0) {
                    $sellingPrice = (int)($row['sale_price'] ?? 0);
                }

                $originalPrice = $cartOriginalPrice > 0 ? $cartOriginalPrice : $sellingPrice;
                $unitPrice = $cartUnitPrice > 0 ? $cartUnitPrice : $sellingPrice;

                # Keep order data consistent: unit price should not exceed original selling price.
                if ($unitPrice > $originalPrice && $originalPrice > 0) {
                    $unitPrice = $originalPrice;
                }

                $lineTotal = $unitPrice * $qty;
                $profitAmount = max(0, ($unitPrice - $costPrice) * $qty);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id' => (int)$row['product_id'],
                    'variant_id' => (int)$row['variant_id'],
                    'product_name' => (string)$row['product_name'],
                    'variant_name' => (string)($row['combination_key'] ?? 'Mac dinh'),
                    'sku' => (string)$row['sku'],
                    'base_price' => (int)$row['base_price'],
                    'sale_price' => (int)$row['sale_price'],
                    'cost_price' => $costPrice,
                    'selling_price' => $originalPrice,
                    'vat_percent' => $vatPercent,
                    'import_tax_percent' => $importTaxPercent,
                    'profit_percent' => $profitPercent,
                    'profit_amount' => $profitAmount,
                    'discount_pct' => $cartDiscountPct,
                    'unit_price' => $unitPrice,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            $shippingFee = 0;
            $discountTotal = 0;
            $appliedUserVoucherId = null;

            if (($userVoucherId ?? 0) > 0) {
                $validation = Voucher::validateUserVoucherForCheckout($userId, (int)$userVoucherId, $cartItems, $pdo);
                if (($validation['valid'] ?? false) !== true) {
                    throw new \RuntimeException((string)($validation['error'] ?? 'voucher:not-found'));
                }

                $voucherData = $validation['voucher'] ?? null;
                $discountTotal = (int)($validation['discount_total'] ?? 0);
                $appliedUserVoucherId = (int)($validation['user_voucher_id'] ?? 0);

                if (is_array($voucherData)) {
                    $voucherLabel = 'Phiếu ' . (string)($voucherData['code'] ?? '');
                    $voucherNote = '[' . $voucherLabel . ' - giảm ' . number_format($discountTotal) . 'đ]';
                    $customerNote = trim($voucherNote . ' ' . $customerNote);
                }
            }

            $total = $subtotal - $discountTotal + $shippingFee;

            $stOrder = $pdo->prepare(
                "INSERT INTO orders (user_id, status, subtotal, discount_total, shipping_fee, total, customer_note)
                 VALUES (:user_id, :status, :subtotal, :discount_total, :shipping_fee, :total, :customer_note)
                 RETURNING id"
            );
            $stOrder->execute([
                'user_id' => $userId,
                'status' => 'pending_approval',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'customer_note' => $customerNote !== '' ? $customerNote : null,
            ]);
            $orderId = (int)$stOrder->fetchColumn();

            $stAddress = $pdo->prepare(
                "INSERT INTO order_addresses (
                    order_id, full_name, phone, address_line, ward, district, city
                 ) VALUES (
                    :order_id, :full_name, :phone, :address_line, :ward, :district, :city
                 )"
            );
            $stAddress->execute([
                'order_id' => $orderId,
                'full_name' => $customerInfo['full_name'],
                'phone' => $customerInfo['phone'],
                'address_line' => $customerInfo['address_line'],
                'ward' => $customerInfo['ward'] !== '' ? $customerInfo['ward'] : null,
                'district' => $customerInfo['district'],
                'city' => $customerInfo['city'],
            ]);

            $stItem = $pdo->prepare(
                "INSERT INTO order_items (
                    order_id, product_id, variant_id, product_name, variant_name, sku,
                    base_price, sale_price, cost_price, selling_price,
                    vat_percent, import_tax_percent, profit_percent, profit_amount,
                    discount_pct, unit_price, qty, line_total
                 ) VALUES (
                    :order_id, :product_id, :variant_id, :product_name, :variant_name, :sku,
                    :base_price, :sale_price, :cost_price, :selling_price,
                    :vat_percent, :import_tax_percent, :profit_percent, :profit_amount,
                    :discount_pct, :unit_price, :qty, :line_total
                 )"
            );

            foreach ($lines as $line) {
                $stItem->execute([
                    'order_id' => $orderId,
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'product_name' => $line['product_name'],
                    'variant_name' => $line['variant_name'],
                    'sku' => $line['sku'],
                    'base_price' => $line['base_price'],
                    'sale_price' => $line['sale_price'],
                    'cost_price' => $line['cost_price'],
                    'selling_price' => $line['selling_price'],
                    'vat_percent' => $line['vat_percent'],
                    'import_tax_percent' => $line['import_tax_percent'],
                    'profit_percent' => $line['profit_percent'],
                    'profit_amount' => $line['profit_amount'],
                    'discount_pct' => $line['discount_pct'],
                    'unit_price' => $line['unit_price'],
                    'qty' => $line['qty'],
                    'line_total' => $line['line_total'],
                ]);

                $stStock = $pdo->prepare(
                    "UPDATE product_variants
                     SET stock = stock - :qty
                     WHERE id = :variant_id
                       AND is_active = TRUE
                       AND stock >= :qty"
                );
                $stStock->execute([
                    'variant_id' => $line['variant_id'],
                    'qty' => $line['qty'],
                ]);

                if ($stStock->rowCount() === 0) {
                    throw new \RuntimeException('stock:insufficient');
                }

                InventoryLog::create(
                    (int)$line['product_id'],
                    (int)$line['qty'],
                    'export',
                    'Xuat kho cho don #' . $orderId,
                    $pdo
                );
            }

            $stApproval = $pdo->prepare(
                "INSERT INTO order_approvals (order_id, request_type, requested_by, decision)
                 VALUES (:order_id, 'approve_order', :requested_by, 'pending')"
            );
            $stApproval->execute([
                'order_id' => $orderId,
                'requested_by' => $userId,
            ]);

            if ($appliedUserVoucherId !== null && $appliedUserVoucherId > 0) {
                Voucher::markUserVoucherAsUsed($appliedUserVoucherId, $pdo);
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function listByUser(int $userId): array
    {
        $sql = "SELECT o.id,
                       o.status,
                       o.created_at,
                       COALESCE(o.subtotal, 0) AS subtotal,
                       COALESCE(o.discount_total, 0) AS discount_total,
                       COALESCE(o.shipping_fee, 0) AS shipping_fee,
                       COALESCE(o.total, 0) AS total_amount,
                       COALESCE(o.customer_note, '') AS customer_note,
                       COALESCE(pay.method_code, 'cod') AS payment_method_code,
                       COALESCE(pay.method_name, 'COD') AS payment_method_name
                FROM orders o
                LEFT JOIN LATERAL (
                    SELECT pm.code AS method_code, pm.name AS method_name
                    FROM payments p
                    JOIN payment_methods pm ON pm.id = p.method_id
                    WHERE p.order_id = o.id
                    ORDER BY p.id DESC
                    LIMIT 1
                ) pay ON TRUE
                WHERE o.user_id = :uid
                ORDER BY o.created_at DESC
                LIMIT 30";

        $st = DB::conn()->prepare($sql);
        $st->execute(['uid' => $userId]);
        return $st->fetchAll();
    }

    public static function listDetailedByUser(int $userId): array
    {
        $orders = self::listByUser($userId);
        if (empty($orders)) {
            return [];
        }

        $orderIds = array_values(array_filter(array_map(static fn($order) => (int)($order['id'] ?? 0), $orders)));
        if (empty($orderIds)) {
            return $orders;
        }

        $placeholders = [];
        $params = ['uid' => $userId];

        foreach ($orderIds as $index => $orderId) {
            $key = 'order_id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $orderId;
        }

        $sql = "SELECT oi.order_id,
                       oi.product_id,
                       COALESCE(oi.product_name, p.name, 'Sản phẩm') AS product_name,
                       COALESCE(p.slug, '') AS product_slug,
                       oi.qty,
                       COALESCE(oi.selling_price, oi.unit_price, 0) AS original_unit_price,
                       COALESCE(oi.discount_pct, 0) AS discount_pct,
                       oi.unit_price,
                       oi.line_total,
                       r.id AS review_id,
                       r.rating AS review_rating,
                       r.status AS review_status
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                LEFT JOIN products p ON p.id = oi.product_id
                LEFT JOIN reviews r ON r.user_id = o.user_id AND r.product_id = oi.product_id
                WHERE o.user_id = :uid
                  AND oi.order_id IN (" . implode(', ', $placeholders) . ")
                ORDER BY oi.order_id DESC, oi.id ASC";

        $st = DB::conn()->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value, \PDO::PARAM_INT);
        }
        $st->execute();
        $rows = $st->fetchAll();

        $statusMap = [];
        foreach ($orders as $order) {
            $statusMap[(int)($order['id'] ?? 0)] = (string)($order['status'] ?? '');
        }

        $itemsByOrder = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $discountRaw = (float)($row['discount_pct'] ?? 0);
            $discountPercent = $discountRaw > 0 && $discountRaw <= 1
                ? (int)round($discountRaw * 100)
                : (int)round($discountRaw);
            $itemsByOrder[$orderId][] = [
                'product_id' => (int)($row['product_id'] ?? 0),
                'product_name' => (string)($row['product_name'] ?? 'Sản phẩm'),
                'product_slug' => (string)($row['product_slug'] ?? ''),
                'qty' => (int)($row['qty'] ?? 0),
                'original_unit_price' => (int)($row['original_unit_price'] ?? 0),
                'discount_pct' => max(0, min(90, $discountPercent)),
                'unit_price' => (int)($row['unit_price'] ?? 0),
                'line_total' => (int)($row['line_total'] ?? 0),
                'review_id' => isset($row['review_id']) ? (int)$row['review_id'] : null,
                'review_rating' => isset($row['review_rating']) ? (int)$row['review_rating'] : null,
                'review_status' => (string)($row['review_status'] ?? ''),
                'can_review' => !in_array($statusMap[$orderId] ?? '', ['rejected', 'cancelled'], true) && empty($row['review_id']),
            ];
        }

        foreach ($orders as &$order) {
            $orderId = (int)($order['id'] ?? 0);
            $order['items'] = $itemsByOrder[$orderId] ?? [];
        }
        unset($order);

        return $orders;
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $pdo = DB::conn();

        $q = trim((string)($filters['q'] ?? ''));
        $status = trim((string)($filters['status'] ?? ''));
        $paymentMethod = trim((string)($filters['payment_method'] ?? ''));
        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        $dateTo = trim((string)($filters['date_to'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(CAST(o.id AS TEXT) ILIKE :q OR COALESCE(oa.full_name, \'\') ILIKE :q OR COALESCE(oa.phone, \'\') ILIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        if ($status !== '') {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }

        if ($paymentMethod !== '') {
            $where[] = 'COALESCE(pay.method_code, \'\') = :payment_method';
            $params['payment_method'] = $paymentMethod;
        }

        if ($dateFrom !== '') {
            $where[] = 'o.created_at::date >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'o.created_at::date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $fromSql = "FROM orders o
                    JOIN users u ON u.id = o.user_id
                    LEFT JOIN order_addresses oa ON oa.order_id = o.id
                    LEFT JOIN LATERAL (
                        SELECT pm.code AS method_code, pm.name AS method_name
                        FROM payments p
                        JOIN payment_methods pm ON pm.id = p.method_id
                        WHERE p.order_id = o.id
                        ORDER BY p.id DESC
                        LIMIT 1
                    ) pay ON TRUE";

        $countSt = $pdo->prepare("SELECT COUNT(*) {$fromSql} {$whereSql}");
        foreach ($params as $key => $value) {
            $countSt->bindValue(':' . $key, $value);
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

        $listSql = "SELECT o.id,
                           o.status,
                           o.created_at,
                           o.total,
                           COALESCE(o.discount_total, 0) AS discount_total,
                           COALESCE(o.customer_note, '') AS customer_note,
                                                     COALESCE(campaigns.campaign_discount_total, 0) AS campaign_discount_total,
                                                     EXISTS(
                                                             SELECT 1
                                                             FROM order_items oi
                                                             WHERE oi.order_id = o.id
                                                                 AND COALESCE(oi.discount_pct, 0) > 0
                                                     ) AS has_campaign_discount,
                           u.email,
                           COALESCE(oa.full_name, '') AS full_name,
                           COALESCE(oa.phone, '') AS phone,
                              COALESCE(pay.method_code, 'cod') AS payment_method_code,
                           COALESCE(pay.method_name, 'COD') AS payment_method_name
                    {$fromSql}
                          LEFT JOIN LATERAL (
                           SELECT COALESCE(SUM(GREATEST(0, COALESCE(oi.selling_price, oi.unit_price, 0) - COALESCE(oi.unit_price, 0)) * COALESCE(oi.qty, 0)), 0) AS campaign_discount_total
                           FROM order_items oi
                           WHERE oi.order_id = o.id
                             AND COALESCE(oi.discount_pct, 0) > 0
                          ) campaigns ON TRUE
                    {$whereSql}
                    ORDER BY o.created_at DESC, o.id DESC
                    LIMIT :limit OFFSET :offset";

        $st = $pdo->prepare($listSql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value);
        }
        $st->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $st->execute();

        $stats = self::adminQuickStats();

        return [
            'rows' => $st->fetchAll(),
            'stats' => $stats,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public static function adminQuickStats(): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN o.created_at::date = CURRENT_DATE THEN 1 ELSE 0 END) AS today_orders,
                    SUM(CASE WHEN o.status = 'pending_approval' THEN 1 ELSE 0 END) AS pending_orders,
                    SUM(CASE WHEN o.status = 'shipping' THEN 1 ELSE 0 END) AS shipping_orders,
                    SUM(CASE WHEN o.status = 'done' THEN 1 ELSE 0 END) AS done_orders
                FROM orders o";

        $row = DB::conn()->query($sql)->fetch();

        return [
            'today_orders' => (int)($row['today_orders'] ?? 0),
            'pending_orders' => (int)($row['pending_orders'] ?? 0),
            'shipping_orders' => (int)($row['shipping_orders'] ?? 0),
            'done_orders' => (int)($row['done_orders'] ?? 0),
        ];
    }

    public static function adminPaymentMethods(): array
    {
        $sql = "SELECT code, name FROM payment_methods WHERE is_active = TRUE ORDER BY name ASC";
        return DB::conn()->query($sql)->fetchAll();
    }

    public static function adminFind(int $orderId): ?array
    {
        $sql = "SELECT o.id,
                       o.status,
                       o.created_at,
                       o.updated_at,
                       o.total,
                       o.subtotal,
                       o.discount_total,
                   COALESCE(campaigns.campaign_discount_total, 0) AS campaign_discount_total,
                       o.shipping_fee,
                       o.customer_note,
                       u.email,
                       COALESCE(oa.full_name, '') AS full_name,
                       COALESCE(oa.phone, '') AS phone,
                       COALESCE(oa.address_line, '') AS address_line,
                       COALESCE(oa.ward, '') AS ward,
                       COALESCE(oa.district, '') AS district,
                       COALESCE(oa.city, '') AS city,
                      COALESCE(pay.method_code, 'cod') AS payment_method_code,
                       COALESCE(pay.method_name, 'COD') AS payment_method_name
                FROM orders o
                JOIN users u ON u.id = o.user_id
                LEFT JOIN order_addresses oa ON oa.order_id = o.id
                  LEFT JOIN LATERAL (
                      SELECT COALESCE(SUM(GREATEST(0, COALESCE(oi.selling_price, oi.unit_price, 0) - COALESCE(oi.unit_price, 0)) * COALESCE(oi.qty, 0)), 0) AS campaign_discount_total
                      FROM order_items oi
                      WHERE oi.order_id = o.id
                     AND COALESCE(oi.discount_pct, 0) > 0
                  ) campaigns ON TRUE
                LEFT JOIN LATERAL (
                    SELECT pm.code AS method_code, pm.name AS method_name
                    FROM payments p
                    JOIN payment_methods pm ON pm.id = p.method_id
                    WHERE p.order_id = o.id
                    ORDER BY p.id DESC
                    LIMIT 1
                ) pay ON TRUE
                WHERE o.id = :id
                LIMIT 1";

        $st = DB::conn()->prepare($sql);
        $st->execute(['id' => $orderId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function adminItems(int $orderId): array
    {
        $sql = "SELECT product_name,
                       variant_name,
                       sku,
                       COALESCE(selling_price, unit_price) AS selling_price,
                   COALESCE(unit_price, 0) AS unit_price,
                   COALESCE(discount_pct, 0) AS discount_pct,
                   GREATEST(0, COALESCE(selling_price, unit_price, 0) - COALESCE(unit_price, 0)) * COALESCE(qty, 0) AS campaign_discount_amount,
                       qty,
                       line_total
                FROM order_items
                WHERE order_id = :id
                ORDER BY id ASC";

        $st = DB::conn()->prepare($sql);
        $st->execute(['id' => $orderId]);
        return $st->fetchAll();
    }

    public static function adminUpdateStatus(int $orderId, string $newStatus, int $changedBy): void
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $stOld = $pdo->prepare("SELECT status FROM orders WHERE id = :id LIMIT 1");
            $stOld->execute(['id' => $orderId]);
            $oldStatus = (string)$stOld->fetchColumn();
            if ($oldStatus === '') {
                $pdo->rollBack();
                return;
            }

            $st = $pdo->prepare("UPDATE orders SET status = :status, updated_at = now() WHERE id = :id");
            $st->execute([
                'id' => $orderId,
                'status' => $newStatus,
            ]);

            $stHis = $pdo->prepare(
                "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
                 VALUES (:order_id, :old_status, :new_status, :changed_by, :note)"
            );
            $stHis->execute([
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $changedBy > 0 ? $changedBy : null,
                'note' => 'Updated by admin dashboard',
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function adminCancel(int $orderId, int $changedBy, bool $restoreStock = false): void
    {
        if (!$restoreStock) {
            self::adminUpdateStatus($orderId, 'cancelled', $changedBy);
            return;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $stOrder = $pdo->prepare(
                "SELECT status
                 FROM orders
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $stOrder->execute(['id' => $orderId]);
            $oldStatus = (string)$stOrder->fetchColumn();
            if ($oldStatus === '') {
                $pdo->rollBack();
                return;
            }

            if ($oldStatus === 'cancelled' || $oldStatus === 'rejected') {
                $pdo->commit();
                return;
            }

            $stItems = $pdo->prepare(
                "SELECT product_id, variant_id, qty
                 FROM order_items
                 WHERE order_id = :order_id"
            );
            $stItems->execute(['order_id' => $orderId]);
            $items = $stItems->fetchAll() ?: [];

            $stStock = $pdo->prepare(
                "UPDATE product_variants
                 SET stock = stock + :qty
                 WHERE id = :variant_id"
            );

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $qty = max(0, (int)($item['qty'] ?? 0));
                $productId = (int)($item['product_id'] ?? 0);
                if ($variantId <= 0 || $qty <= 0) {
                    continue;
                }

                $stStock->execute([
                    'qty' => $qty,
                    'variant_id' => $variantId,
                ]);

                InventoryLog::create(
                    $productId,
                    $qty,
                    'import',
                    'Hoan kho khi admin huy don #' . $orderId,
                    $pdo
                );
            }

            $stOrderUpdate = $pdo->prepare(
                "UPDATE orders
                 SET status = 'cancelled', updated_at = now()
                 WHERE id = :id"
            );
            $stOrderUpdate->execute(['id' => $orderId]);

            $stHistory = $pdo->prepare(
                "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
                 VALUES (:order_id, :old_status, 'cancelled', :changed_by, :note)"
            );
            $stHistory->execute([
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'changed_by' => $changedBy > 0 ? $changedBy : null,
                'note' => 'Admin huy don va hoan kho',
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function approve(int $orderId, int $adminUserId): void
    {
        self::adminUpdateStatus($orderId, 'approved', $adminUserId);
    }

    public static function reject(int $orderId, int $adminUserId): void
    {
        self::adminUpdateStatus($orderId, 'cancelled', $adminUserId);
    }

    public static function markShipping(int $orderId): void
    {
        self::adminUpdateStatus($orderId, 'shipping', 0);
    }

    /**
     * Allow customer to cancel own order if within 10 minutes of creation
     * @throws \RuntimeException if order cannot be cancelled or doesn't belong to user
     */
    public static function userCancel(int $orderId, int $userId): array
    {
        $pdo = DB::conn();

        $order = $pdo->prepare(
            "SELECT o.id, o.user_id, o.status, o.created_at,
                    COALESCE(pay.method_code, 'cod') AS payment_method
             FROM orders o
             LEFT JOIN LATERAL (
                SELECT pm.code AS method_code
                FROM payments p
                JOIN payment_methods pm ON pm.id = p.method_id
                WHERE p.order_id = o.id
                ORDER BY p.id DESC
                LIMIT 1
             ) pay ON TRUE
             WHERE o.id = :order_id"
        );
        $order->execute(['order_id' => $orderId]);
        $orderData = $order->fetch();

        if (!$orderData) {
            throw new \RuntimeException('order:not-found');
        }

        if ((int)$orderData['user_id'] !== $userId) {
            throw new \RuntimeException('order:not-yours');
        }

        $status = (string)($orderData['status'] ?? '');
        if (in_array($status, ['cancelled', 'done', 'shipping', 'rejected'], true)) {
            throw new \RuntimeException('order:cannot-cancel');
        }

        $createdAt = $orderData['created_at'] ?? '';
        if ($createdAt !== '') {
            try {
                $createdDateTime = new \DateTime($createdAt);
                $currentDateTime = new \DateTime('now');
                $totalSeconds = max(0, $currentDateTime->getTimestamp() - $createdDateTime->getTimestamp());

                if ($totalSeconds >= 600) { // 600 seconds = 10 minutes
                    throw new \RuntimeException('order:timeout');
                }
            } catch (\RuntimeException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new \RuntimeException('order:timeout');
            }
        }

        $paymentMethod = (string)($orderData['payment_method'] ?? 'cod');
        
        $pdo->beginTransaction();
        try {
            $stItems = $pdo->prepare(
                "SELECT product_id, variant_id, qty
                 FROM order_items
                 WHERE order_id = :order_id"
            );
            $stItems->execute(['order_id' => $orderId]);
            $items = $stItems->fetchAll() ?: [];

            $stStock = $pdo->prepare(
                "UPDATE product_variants
                 SET stock = stock + :qty
                 WHERE id = :variant_id"
            );

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $qty = max(0, (int)($item['qty'] ?? 0));
                $productId = (int)($item['product_id'] ?? 0);

                if ($variantId <= 0 || $qty <= 0) {
                    continue;
                }

                $stStock->execute([
                    'qty' => $qty,
                    'variant_id' => $variantId,
                ]);

                InventoryLog::create(
                    $productId,
                    $qty,
                    'import',
                    'Hoan kho khi khach huy don #' . $orderId,
                    $pdo
                );
            }

            $st = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = now() WHERE id = :id");
            $st->execute(['id' => $orderId]);

            $st2 = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
                                 VALUES (:order_id, :old_status, 'cancelled', :changed_by, :note)");
            $st2->execute([
                'order_id' => $orderId,
                'old_status' => $status,
                'changed_by' => $userId > 0 ? $userId : null,
                'note' => 'Khách hàng hủy trong 10 phút đầu',
            ]);

            $pdo->commit();

            return [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'is_bank_transfer' => $paymentMethod === 'bank',
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function cancelFailedBankTransfer(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $stOrder = $pdo->prepare(
                "SELECT id, status
                 FROM orders
                 WHERE id = :id
                 FOR UPDATE"
            );
            $stOrder->execute(['id' => $orderId]);
            $order = $stOrder->fetch();
            if (!$order) {
                $pdo->rollBack();
                return;
            }

            $oldStatus = (string)($order['status'] ?? '');
            if ($oldStatus === 'cancelled' || $oldStatus === 'rejected') {
                $pdo->commit();
                return;
            }

            $stPayment = $pdo->prepare(
                "SELECT p.status
                 FROM payments p
                 JOIN payment_methods pm ON pm.id = p.method_id
                 WHERE p.order_id = :order_id
                   AND pm.code = 'bank'
                 ORDER BY p.id DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stPayment->execute(['order_id' => $orderId]);
            $paymentStatus = (string)$stPayment->fetchColumn();
            if ($paymentStatus === 'paid') {
                $pdo->commit();
                return;
            }

            $stItems = $pdo->prepare(
                "SELECT product_id, variant_id, qty
                 FROM order_items
                 WHERE order_id = :order_id"
            );
            $stItems->execute(['order_id' => $orderId]);
            $items = $stItems->fetchAll() ?: [];

            $stRestock = $pdo->prepare(
                "UPDATE product_variants
                 SET stock = stock + :qty
                 WHERE id = :variant_id"
            );

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $qty = max(0, (int)($item['qty'] ?? 0));
                $productId = (int)($item['product_id'] ?? 0);
                if ($variantId <= 0 || $qty <= 0) {
                    continue;
                }

                $stRestock->execute([
                    'qty' => $qty,
                    'variant_id' => $variantId,
                ]);

                InventoryLog::create(
                    $productId,
                    $qty,
                    'import',
                    'Hoan kho do thanh toan that bai don #' . $orderId,
                    $pdo
                );
            }

            $stCancel = $pdo->prepare(
                "UPDATE orders
                 SET status = 'cancelled', updated_at = now()
                 WHERE id = :id"
            );
            $stCancel->execute(['id' => $orderId]);

            $stHistory = $pdo->prepare(
                "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
                 VALUES (:order_id, :old_status, 'cancelled', NULL, :note)"
            );
            $stHistory->execute([
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'note' => 'Auto cancel do thanh toan VNPAY that bai',
            ]);

            $stFailPayment = $pdo->prepare(
                "UPDATE payments
                 SET status = 'failed'
                 WHERE order_id = :order_id
                   AND method_id IN (
                       SELECT id FROM payment_methods WHERE code = 'bank'
                   )"
            );
            $stFailPayment->execute(['order_id' => $orderId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function ensurePayment(int $orderId, string $methodCode, int $amount): void
    {
        $method = self::findPaymentMethodByCode($methodCode);
        if ($method === null) {
            return;
        }

        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT id
             FROM payments
             WHERE order_id = :order_id AND method_id = :method_id
             ORDER BY id DESC
             LIMIT 1"
        );
        $st->execute([
            'order_id' => $orderId,
            'method_id' => (int)$method['id'],
        ]);

        $paymentId = $st->fetchColumn();
        if ($paymentId) {
            $up = $pdo->prepare(
                "UPDATE payments
                 SET amount = :amount
                 WHERE id = :id"
            );
            $up->execute([
                'amount' => max(0, $amount),
                'id' => (int)$paymentId,
            ]);
            return;
        }

        $ins = $pdo->prepare(
            "INSERT INTO payments(order_id, method_id, amount, status)
             VALUES (:order_id, :method_id, :amount, 'pending')"
        );
        $ins->execute([
            'order_id' => $orderId,
            'method_id' => (int)$method['id'],
            'amount' => max(0, $amount),
        ]);
    }

    public static function markPaymentStatus(int $orderId, string $methodCode, string $status): void
    {
        $allowed = ['pending', 'paid', 'failed', 'refunded', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return;
        }

        $method = self::findPaymentMethodByCode($methodCode);
        if ($method === null) {
            return;
        }

        $pdo = DB::conn();
        $st = $pdo->prepare(
            "UPDATE payments
             SET status = :status,
                 paid_at = CASE WHEN :status = 'paid' THEN now() ELSE paid_at END
             WHERE order_id = :order_id AND method_id = :method_id"
        );
        $st->execute([
            'status' => $status,
            'order_id' => $orderId,
            'method_id' => (int)$method['id'],
        ]);
    }

    public static function findOrderByIdAndUser(int $orderId, int $userId): ?array
    {
        $st = DB::conn()->prepare(
            "SELECT id, user_id, total, status
             FROM orders
             WHERE id = :id AND user_id = :user_id
             LIMIT 1"
        );
        $st->execute([
            'id' => $orderId,
            'user_id' => $userId,
        ]);

        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    private static function findPaymentMethodByCode(string $code): ?array
    {
        $st = DB::conn()->prepare(
            "SELECT id, code, name
             FROM payment_methods
             WHERE code = :code
               AND is_active = TRUE
             LIMIT 1"
        );
        $st->execute(['code' => trim($code)]);
        $row = $st->fetch();

        return is_array($row) ? $row : null;
    }
}
