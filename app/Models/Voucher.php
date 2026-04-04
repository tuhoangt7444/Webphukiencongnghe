<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class Voucher
{
    private const STATUSES = ['active', 'disabled', 'expired'];
    private const CUSTOMER_TYPES = ['all', 'new', 'low', 'mid', 'vip'];
    private static bool $schemaEnsured = false;

    public static function customerTypeOptions(): array
    {
        return [
            'all' => 'Tất cả khách hàng',
            'new' => 'Khách mới',
            'low' => 'Chi tiêu thấp (<= 10 triệu)',
            'mid' => 'Chi tiêu vừa (10 - 20 triệu)',
            'vip' => 'Khách VIP (> 20 triệu)',
        ];
    }

    public static function categoriesForVoucher(): array
    {
        self::ensureSchema();
        return DB::conn()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll() ?: [];
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        self::ensureSchema();
        self::refreshExpiredStatuses();

        $pdo = DB::conn();
        $q = trim((string)($filters['q'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(v.name ILIKE :q OR v.code ILIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM vouchers v {$whereSql}");
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

        $listSt = $pdo->prepare(
              "SELECT v.id,
                    v.name,
                    v.code,
                    v.discount_amount,
                    v.start_date,
                    v.end_date,
                    v.quantity,
                    v.status,
                    v.created_at,
                    v.apply_category_id,
                    v.customer_type,
                    c.name AS apply_category_name
               FROM vouchers v
               LEFT JOIN categories c ON c.id = v.apply_category_id
             {$whereSql}
               ORDER BY v.created_at DESC, v.id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $listSt->bindValue(':' . $key, $value);
        }

        $listSt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listSt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listSt->execute();

        return [
            'rows' => $listSt->fetchAll(),
            'stats' => self::stats(),
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
        self::ensureSchema();
        self::refreshExpiredStatuses();

        $sql = "SELECT
                    COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE status = 'active') AS active_count,
                    COUNT(*) FILTER (WHERE status = 'disabled') AS disabled_count,
                    COUNT(*) FILTER (WHERE status = 'expired') AS expired_count
                FROM vouchers";

        $row = DB::conn()->query($sql)->fetch() ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'active_count' => (int)($row['active_count'] ?? 0),
            'disabled_count' => (int)($row['disabled_count'] ?? 0),
            'expired_count' => (int)($row['expired_count'] ?? 0),
        ];
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();
        self::refreshExpiredStatusesForId($id);

        $st = DB::conn()->prepare(
            'SELECT v.id,
                    v.name,
                    v.code,
                    v.discount_amount,
                    v.start_date,
                    v.end_date,
                    v.quantity,
                    v.status,
                    v.created_at,
                    v.apply_category_id,
                    v.customer_type,
                    c.name AS apply_category_name
             FROM vouchers v
             LEFT JOIN categories c ON c.id = v.apply_category_id
             WHERE v.id = :id
             LIMIT 1'
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        self::ensureSchema();
        $st = DB::conn()->prepare(
            "INSERT INTO vouchers (name, code, discount_amount, start_date, end_date, quantity, status, apply_category_id, customer_type)
             VALUES (:name, :code, :discount_amount, :start_date, :end_date, :quantity, :status, :apply_category_id, :customer_type)
             RETURNING id"
        );

        $st->execute([
            'name' => $data['name'],
            'code' => strtoupper((string)$data['code']),
            'discount_amount' => $data['discount_amount'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'quantity' => $data['quantity'],
            'status' => $data['status'],
            'apply_category_id' => $data['apply_category_id'],
            'customer_type' => $data['customer_type'],
        ]);

        return (int)$st->fetchColumn();
    }

    public static function update(int $id, array $data): void
    {
        self::ensureSchema();
        $st = DB::conn()->prepare(
            "UPDATE vouchers
             SET name = :name,
                 code = :code,
                 discount_amount = :discount_amount,
                 start_date = :start_date,
                 end_date = :end_date,
                 quantity = :quantity,
                 status = :status,
                 apply_category_id = :apply_category_id,
                 customer_type = :customer_type
             WHERE id = :id"
        );

        $st->execute([
            'id' => $id,
            'name' => $data['name'],
            'code' => strtoupper((string)$data['code']),
            'discount_amount' => $data['discount_amount'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'quantity' => $data['quantity'],
            'status' => $data['status'],
            'apply_category_id' => $data['apply_category_id'],
            'customer_type' => $data['customer_type'],
        ]);

        self::refreshExpiredStatusesForId($id);
    }

    public static function toggleStatus(int $id): string
    {
        self::refreshExpiredStatusesForId($id);

        $row = self::find($id);
        if (!$row) {
            return 'not-found';
        }

        if ((string)$row['status'] === 'expired') {
            return 'expired';
        }

        $nextStatus = ((string)$row['status'] === 'active') ? 'disabled' : 'active';
        $st = DB::conn()->prepare('UPDATE vouchers SET status = :status WHERE id = :id');
        $st->execute([
            'id' => $id,
            'status' => $nextStatus,
        ]);

        return $nextStatus;
    }

    public static function delete(int $id): bool
    {
        $stUsed = DB::conn()->prepare('SELECT COUNT(*) FROM user_vouchers WHERE voucher_id = :id');
        $stUsed->execute(['id' => $id]);
        if ((int)$stUsed->fetchColumn() > 0) {
            return false;
        }

        $st = DB::conn()->prepare('DELETE FROM vouchers WHERE id = :id');
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    public static function listPublicAvailable(int $limit = 6): array
    {
                self::ensureSchema();
        self::refreshExpiredStatuses();

                $userType = 'guest';
                if (session_status() === PHP_SESSION_NONE) {
                        @session_start();
                }
                $userId = (int)($_SESSION['user_id'] ?? 0);
                if ($userId > 0) {
                        $userType = self::resolveUserCustomerType($userId);
                }

        $st = DB::conn()->prepare(
                        "SELECT v.id,
                                        v.name,
                                        v.code,
                                        v.discount_amount,
                                        v.start_date,
                                        v.end_date,
                                        v.quantity,
                                        v.customer_type,
                                        v.apply_category_id,
                                        c.name AS apply_category_name
                         FROM vouchers v
                         LEFT JOIN categories c ON c.id = v.apply_category_id
                         WHERE v.status = 'active'
                             AND v.start_date <= CURRENT_DATE
                             AND v.end_date >= CURRENT_DATE
                             AND v.quantity > 0
                             AND (COALESCE(v.customer_type, 'all') = 'all' OR COALESCE(v.customer_type, 'all') = :user_type)
                         ORDER BY v.created_at DESC, v.id DESC
             LIMIT :limit"
        );
                $st->bindValue(':user_type', $userType, PDO::PARAM_STR);
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll() ?: [];
    }

    public static function claimForUser(int $userId, int $voucherId): string
    {
        self::ensureSchema();
        if ($userId <= 0 || $voucherId <= 0) {
            return 'invalid';
        }

        self::refreshExpiredStatusesForId($voucherId);

        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $stExists = $pdo->prepare(
                'SELECT id, used FROM user_vouchers WHERE user_id = :user_id AND voucher_id = :voucher_id LIMIT 1'
            );
            $stExists->execute([
                'user_id' => $userId,
                'voucher_id' => $voucherId,
            ]);

            $claimed = $stExists->fetch();
            if ($claimed) {
                $pdo->commit();
                return 'already-claimed';
            }

                        $stVoucher = $pdo->prepare(
                                "SELECT id, COALESCE(customer_type, 'all') AS customer_type
                 FROM vouchers
                 WHERE id = :id
                   AND status = 'active'
                   AND start_date <= CURRENT_DATE
                   AND end_date >= CURRENT_DATE
                   AND quantity > 0
                 FOR UPDATE"
            );
            $stVoucher->execute(['id' => $voucherId]);
            $voucher = $stVoucher->fetch();

            if (!$voucher) {
                $pdo->rollBack();
                return 'unavailable';
            }

            $userType = self::resolveUserCustomerType($userId, $pdo);
            $voucherCustomerType = (string)($voucher['customer_type'] ?? 'all');
            if ($voucherCustomerType !== 'all' && $voucherCustomerType !== $userType) {
                $pdo->rollBack();
                return 'not-eligible';
            }

            $stInsert = $pdo->prepare(
                'INSERT INTO user_vouchers (user_id, voucher_id, used) VALUES (:user_id, :voucher_id, FALSE)'
            );
            $stInsert->execute([
                'user_id' => $userId,
                'voucher_id' => $voucherId,
            ]);

            $stUpdateQty = $pdo->prepare(
                'UPDATE vouchers SET quantity = quantity - 1 WHERE id = :id AND quantity > 0'
            );
            $stUpdateQty->execute(['id' => $voucherId]);

            if ($stUpdateQty->rowCount() === 0) {
                $pdo->rollBack();
                return 'unavailable';
            }

            $pdo->commit();
            return 'claimed';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return 'failed';
        }
    }

    /**
     * Return all voucher IDs the user has ever claimed (regardless of used/expired status).
     * Used on homepage to always show "Đã lấy" once a voucher was claimed.
     */
    public static function allClaimedVoucherIdsByUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $st = DB::conn()->prepare(
            'SELECT voucher_id FROM user_vouchers WHERE user_id = :user_id'
        );
        $st->execute(['user_id' => $userId]);
        $rows = $st->fetchAll(\PDO::FETCH_COLUMN, 0);

        return array_values(array_unique(array_filter(array_map('intval', $rows ?: []))));
    }

    public static function claimedByUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        self::ensureSchema();
        self::refreshExpiredStatuses();

        $st = DB::conn()->prepare(
            "SELECT uv.id AS user_voucher_id,
                    uv.used,
                    uv.created_at AS claimed_at,
                    v.id AS voucher_id,
                    v.name,
                    v.code,
                    v.discount_amount,
                    v.start_date,
                    v.end_date,
                    v.status,
                    v.customer_type,
                    v.apply_category_id
             FROM user_vouchers uv
             JOIN vouchers v ON v.id = uv.voucher_id
             WHERE uv.user_id = :user_id
               AND uv.used = FALSE
               AND v.status = 'active'
               AND v.start_date <= CURRENT_DATE
               AND v.end_date >= CURRENT_DATE
             ORDER BY uv.created_at DESC"
        );
        $st->execute(['user_id' => $userId]);

        return $st->fetchAll() ?: [];
    }

    public static function validateUserVoucherForCheckout(int $userId, int $userVoucherId, array $cartItems, ?PDO $pdo = null): array
    {
        self::ensureSchema();
        if ($userVoucherId <= 0) {
            return ['valid' => false, 'error' => 'voucher:empty'];
        }

        $normalized = self::normalizeCheckoutItems($cartItems);

        if (count($normalized) !== 1) {
            return ['valid' => false, 'error' => 'voucher:single-product-only'];
        }

        $item = $normalized[0];
        if ($item['qty'] > 2) {
            return ['valid' => false, 'error' => 'voucher:quantity-limit'];
        }

        $conn = $pdo ?? DB::conn();

        $st = $conn->prepare(
            "SELECT uv.id AS user_voucher_id,
                    uv.used,
                    v.id AS voucher_id,
                    v.name,
                    v.code,
                    v.discount_amount,
                    v.start_date,
                    v.end_date,
                          v.status,
                          v.apply_category_id,
                          COALESCE(v.customer_type, 'all') AS customer_type
             FROM user_vouchers uv
             JOIN vouchers v ON v.id = uv.voucher_id
             WHERE uv.id = :user_voucher_id
               AND uv.user_id = :user_id
             LIMIT 1"
        );
        $st->execute([
            'user_voucher_id' => $userVoucherId,
            'user_id' => $userId,
        ]);

        $row = $st->fetch();
        if (!$row) {
            return ['valid' => false, 'error' => 'voucher:not-found'];
        }

        if ((bool)$row['used']) {
            return ['valid' => false, 'error' => 'voucher:used'];
        }

        if ((string)$row['status'] !== 'active') {
            return ['valid' => false, 'error' => 'voucher:disabled'];
        }

        $today = date('Y-m-d');
        if ((string)$row['start_date'] > $today) {
            return ['valid' => false, 'error' => 'voucher:not-started'];
        }

        if ((string)$row['end_date'] < $today) {
            return ['valid' => false, 'error' => 'voucher:expired'];
        }

        $userType = self::resolveUserCustomerType($userId, $conn);
        $voucherCustomerType = (string)($row['customer_type'] ?? 'all');
        if ($voucherCustomerType !== 'all' && $voucherCustomerType !== $userType) {
            return ['valid' => false, 'error' => 'voucher:customer-type-mismatch'];
        }

        $stProduct = $conn->prepare(
                        "SELECT COALESCE(price, 0) AS price,
                                        COALESCE(cost_price, 0) AS cost_price,
                                        COALESCE(category_id, 0) AS category_id
             FROM products
             WHERE id = :id
               AND is_active = TRUE
             LIMIT 1"
        );
        $stProduct->execute(['id' => $item['product_id']]);
        $product = $stProduct->fetch();

        if (!$product) {
            return ['valid' => false, 'error' => 'voucher:product-not-found'];
        }

        $applyCategoryId = (int)($row['apply_category_id'] ?? 0);
        if ($applyCategoryId > 0 && (int)($product['category_id'] ?? 0) !== $applyCategoryId) {
            return ['valid' => false, 'error' => 'voucher:category-mismatch'];
        }

        if (self::hasActiveDiscountCampaignForProduct((int)$item['product_id'], $conn)) {
            return ['valid' => false, 'error' => 'voucher:product-discount-active'];
        }

        $sellingPrice = (int)($product['price'] ?? 0);
        if ($sellingPrice <= 0) {
            $sellingPrice = $item['unit_price'];
        }

        $costPrice = max(0, (int)($product['cost_price'] ?? 0));
        $lineTotal = max(0, $sellingPrice * $item['qty']);
        $profitLimit = max(0, ($sellingPrice - $costPrice) * $item['qty']);
        $discount = max(0, (int)($row['discount_amount'] ?? 0));

        if ($discount <= 0) {
            return ['valid' => false, 'error' => 'voucher:invalid-value'];
        }

        if ($discount > $profitLimit) {
            return ['valid' => false, 'error' => 'voucher:profit-exceeded'];
        }

        if ($discount > $lineTotal) {
            $discount = $lineTotal;
        }

        return [
            'valid' => true,
            'voucher' => $row,
            'user_voucher_id' => (int)$row['user_voucher_id'],
            'discount_total' => $discount,
            'line_total' => $lineTotal,
            'profit_limit' => $profitLimit,
        ];
    }

    public static function markUserVoucherAsUsed(int $userVoucherId, ?PDO $pdo = null): void
    {
        $conn = $pdo ?? DB::conn();

        $st = $conn->prepare(
            'UPDATE user_vouchers SET used = TRUE WHERE id = :id AND used = FALSE'
        );
        $st->execute(['id' => $userVoucherId]);

        if ($st->rowCount() === 0) {
            throw new \RuntimeException('voucher:used');
        }
    }

    public static function normalizePayload(array $input): array
    {
        self::ensureSchema();

        $name = trim((string)($input['name'] ?? ''));
        $code = strtoupper(trim((string)($input['code'] ?? '')));
        $discountAmount = (int)($input['discount_amount'] ?? 0);
        $startDate = trim((string)($input['start_date'] ?? ''));
        $endDate = trim((string)($input['end_date'] ?? ''));
        $quantity = max(0, (int)($input['quantity'] ?? 0));
        $status = trim((string)($input['status'] ?? 'active'));
        $applyCategoryId = max(0, (int)($input['apply_category_id'] ?? 0));
        $customerType = trim((string)($input['customer_type'] ?? 'all'));

        if ($name === '') {
            throw new \InvalidArgumentException('Tên phiếu giảm giá không được để trống.');
        }

        if ($code === '') {
            throw new \InvalidArgumentException('Mã phiếu không được để trống.');
        }

        if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) {
            throw new \InvalidArgumentException('Mã phiếu chỉ gồm A-Z, 0-9, gạch dưới hoặc gạch ngang (3-40 ký tự).');
        }

        if ($discountAmount <= 0) {
            throw new \InvalidArgumentException('Giá trị giảm phải lớn hơn 0.');
        }

        if (!self::isDateString($startDate) || !self::isDateString($endDate)) {
            throw new \InvalidArgumentException('Ngày bắt đầu hoặc ngày kết thúc không hợp lệ.');
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc.');
        }

        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Trạng thái không hợp lệ.');
        }

        if (!in_array($customerType, self::CUSTOMER_TYPES, true)) {
            throw new \InvalidArgumentException('Loại khách hàng áp dụng không hợp lệ.');
        }

        if ($applyCategoryId > 0 && !self::categoryExists($applyCategoryId)) {
            throw new \InvalidArgumentException('Danh mục áp dụng không tồn tại.');
        }

        if ($endDate < date('Y-m-d')) {
            $status = 'expired';
        }

        return [
            'name' => $name,
            'code' => $code,
            'discount_amount' => $discountAmount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'quantity' => $quantity,
            'status' => $status,
            'apply_category_id' => $applyCategoryId > 0 ? $applyCategoryId : null,
            'customer_type' => $customerType,
        ];
    }

    public static function refreshExpiredStatuses(): void
    {
        DB::conn()->exec(
            "UPDATE vouchers
             SET status = 'expired'
             WHERE status <> 'expired'
               AND end_date < CURRENT_DATE"
        );
    }

    private static function refreshExpiredStatusesForId(int $id): void
    {
        $st = DB::conn()->prepare(
            "UPDATE vouchers
             SET status = 'expired'
             WHERE id = :id
               AND status <> 'expired'
               AND end_date < CURRENT_DATE"
        );
        $st->execute(['id' => $id]);
    }

    private static function isDateString(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y);
    }

    private static function normalizeCheckoutItems(array $cartItems): array
    {
        $items = [];
        foreach ($cartItems as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $qty = max(1, (int)($item['qty'] ?? 1));
            $unitPrice = max(0, (int)($item['price'] ?? 0));

            $items[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
            ];
        }

        return $items;
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }

        $pdo = DB::conn();
        $pdo->exec('ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS apply_category_id BIGINT NULL');
        $pdo->exec("ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS customer_type TEXT NOT NULL DEFAULT 'all'");

        self::$schemaEnsured = true;
    }

    private static function categoryExists(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            return false;
        }

        $st = DB::conn()->prepare('SELECT 1 FROM categories WHERE id = :id LIMIT 1');
        $st->execute(['id' => $categoryId]);
        return (bool)$st->fetchColumn();
    }

    private static function resolveUserCustomerType(int $userId, ?PDO $pdo = null): string
    {
        if ($userId <= 0) {
            return 'new';
        }

        $conn = $pdo ?? DB::conn();
        $st = $conn->prepare(
            "SELECT COUNT(*)::int AS order_count,
                    COALESCE(SUM(o.total), 0)::bigint AS total_spent
             FROM orders o
             WHERE o.user_id = :user_id
               AND o.status = ANY(CAST(:spent_statuses AS text[]))"
        );
        $st->execute([
            'user_id' => $userId,
            'spent_statuses' => '{approved,shipping,done}',
        ]);
        $row = $st->fetch() ?: [];

        $orderCount = (int)($row['order_count'] ?? 0);
        $totalSpent = (int)($row['total_spent'] ?? 0);

        if ($orderCount <= 0) {
            return 'new';
        }

        if ($totalSpent > 20000000) {
            return 'vip';
        }

        if ($totalSpent > 10000000) {
            return 'mid';
        }

        return 'low';
    }

    private static function hasActiveDiscountCampaignForProduct(int $productId, PDO $pdo): bool
    {
        if ($productId <= 0) {
            return false;
        }

        $tableExists = $pdo->query("SELECT to_regclass('public.product_discount_campaigns') IS NOT NULL")->fetchColumn();
        if (!$tableExists) {
            return false;
        }

        $st = $pdo->prepare(
            "SELECT 1
             FROM product_discount_campaigns
             WHERE product_id = :product_id
               AND status = 'active'
               AND start_at <= NOW()
               AND end_at >= NOW()
             LIMIT 1"
        );
        $st->execute(['product_id' => $productId]);

        return (bool)$st->fetchColumn();
    }
}
