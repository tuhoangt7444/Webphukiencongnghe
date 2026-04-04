<?php
namespace App\Models;

use App\Core\DB;

final class AdminUser
{
    private const SPENT_STATUSES = ['approved', 'shipping', 'done'];

    public static function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $pdo = DB::conn();
        $statuses = self::SPENT_STATUSES;

        $q = trim((string)($filters['q'] ?? ''));
        $customerType = trim((string)($filters['customer_type'] ?? ''));
        $allowedTypes = ['privileged', 'vip', 'low', 'mid', 'new'];
        if (!in_array($customerType, $allowedTypes, true)) {
            $customerType = '';
        }

        $where = ["1=1"];
        $params = [];

        if ($q !== '') {
            $where[] = "(
                COALESCE(cp.full_name, split_part(u.email, '@', 1)) ILIKE :q
                OR u.email ILIKE :q
                OR COALESCE(cp.phone, '') ILIKE :q
            )";
            $params['q'] = '%' . $q . '%';
        }

        if ($customerType === 'privileged') {
            $where[] = "COALESCE(r.code, '') <> 'customer'";
        } elseif ($customerType === 'vip') {
            $where[] = "COALESCE(r.code, '') = 'customer' AND COALESCE(spent.total_spent, 0) > 20000000";
        } elseif ($customerType === 'low') {
            $where[] = "COALESCE(r.code, '') = 'customer' AND COALESCE(spent.order_count, 0) > 0 AND COALESCE(spent.total_spent, 0) <= 10000000";
        } elseif ($customerType === 'mid') {
            $where[] = "COALESCE(r.code, '') = 'customer' AND COALESCE(spent.total_spent, 0) > 10000000 AND COALESCE(spent.total_spent, 0) <= 20000000";
        } elseif ($customerType === 'new') {
            $where[] = "COALESCE(r.code, '') = 'customer' AND COALESCE(spent.order_count, 0) = 0";
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countSql = "SELECT COUNT(*)
                     FROM users u
                     LEFT JOIN roles r ON r.id = u.role_id
                     LEFT JOIN customer_profiles cp ON cp.user_id = u.id
                     LEFT JOIN LATERAL (
                        SELECT COUNT(*)::int AS order_count,
                               COALESCE(SUM(o.total), 0)::bigint AS total_spent
                        FROM orders o
                        WHERE o.user_id = u.id
                          AND o.status = ANY(CAST(:spent_statuses AS text[]))
                     ) spent ON TRUE
                     {$whereSql}";

        $countSt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countSt->bindValue(':' . $key, $value);
        }
        $countSt->bindValue(':spent_statuses', '{' . implode(',', $statuses) . '}', \PDO::PARAM_STR);
        $countSt->execute();
        $total = (int)$countSt->fetchColumn();

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $listSql = "SELECT u.id,
                           u.email,
                           u.status,
                           u.created_at,
                           COALESCE(r.code, '') AS role_code,
                           COALESCE(r.name, '') AS role_name,
                           COALESCE(cp.full_name, split_part(u.email, '@', 1)) AS full_name,
                           COALESCE(cp.phone, '') AS phone,
                           COALESCE(cp.full_address, CONCAT_WS(', ', cp.address_line, cp.ward, cp.district, cp.city), '') AS address,
                           COALESCE(spent.total_spent, 0) AS total_spent,
                           COALESCE(spent.order_count, 0) AS order_count,
                           CASE
                             WHEN COALESCE(spent.order_count, 0) = 0 THEN 'new'
                             WHEN COALESCE(spent.total_spent, 0) > 20000000 THEN 'vip'
                             ELSE 'regular'
                           END AS customer_type
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN customer_profiles cp ON cp.user_id = u.id
                    LEFT JOIN LATERAL (
                        SELECT COUNT(*)::int AS order_count,
                               COALESCE(SUM(o.total), 0)::bigint AS total_spent
                        FROM orders o
                        WHERE o.user_id = u.id
                          AND o.status = ANY(CAST(:spent_statuses AS text[]))
                    ) spent ON TRUE
                    {$whereSql}
                    ORDER BY
                        CASE WHEN COALESCE(r.code, '') <> 'customer' THEN 0 ELSE 1 END ASC,
                        CASE
                            WHEN COALESCE(r.code, '') = 'customer' AND COALESCE(spent.total_spent, 0) > 20000000 THEN 0
                            ELSE 1
                        END ASC,
                        u.created_at DESC,
                        u.id DESC
                    LIMIT :limit OFFSET :offset";

        $st = $pdo->prepare($listSql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value);
        }
        $st->bindValue(':spent_statuses', '{' . implode(',', $statuses) . '}', \PDO::PARAM_STR);
        $st->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $st->execute();

        return [
            'rows' => $st->fetchAll(),
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
        $statuses = self::SPENT_STATUSES;

        $sql = "SELECT
                    COUNT(*) FILTER (WHERE COALESCE(r.code, '') = 'customer') AS total_customers,
                    COUNT(*) FILTER (WHERE COALESCE(r.code, '') = 'customer' AND u.created_at::date = CURRENT_DATE) AS new_today,
                    COUNT(*) FILTER (WHERE COALESCE(r.code, '') = 'customer' AND u.status = 'banned') AS blocked_customers,
                    COUNT(*) FILTER (
                        WHERE COALESCE(r.code, '') = 'customer'
                          AND EXISTS (
                              SELECT 1 FROM orders o
                              WHERE o.user_id = u.id
                                AND o.status = ANY(CAST(:spent_statuses AS text[]))
                          )
                    ) AS purchased_customers
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id";

        $st = DB::conn()->prepare($sql);
        $st->bindValue(':spent_statuses', '{' . implode(',', $statuses) . '}', \PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch() ?: [];

        return [
            'total_customers' => (int)($row['total_customers'] ?? 0),
            'new_today' => (int)($row['new_today'] ?? 0),
            'purchased_customers' => (int)($row['purchased_customers'] ?? 0),
            'blocked_customers' => (int)($row['blocked_customers'] ?? 0),
        ];
    }

    public static function segmentStats(): array
    {
        $statuses = self::SPENT_STATUSES;
        $sql = "SELECT
                    COUNT(*) FILTER (
                        WHERE COALESCE(r.code, '') = 'customer'
                          AND (COALESCE(spent.order_count, 0) = 0)
                    ) AS new_segment,
                    COUNT(*) FILTER (
                        WHERE COALESCE(r.code, '') = 'customer'
                          AND COALESCE(spent.order_count, 0) > 0
                          AND COALESCE(spent.total_spent, 0) <= 10000000
                    ) AS low_spend,
                    COUNT(*) FILTER (
                        WHERE COALESCE(r.code, '') = 'customer'
                          AND COALESCE(spent.total_spent, 0) > 10000000
                          AND COALESCE(spent.total_spent, 0) <= 20000000
                    ) AS mid_spend,
                    COUNT(*) FILTER (
                        WHERE COALESCE(r.code, '') = 'customer'
                          AND COALESCE(spent.total_spent, 0) > 20000000
                    ) AS vip_segment,
                    COALESCE(SUM(spent.total_spent) FILTER (WHERE COALESCE(r.code, '') = 'customer'), 0)::bigint AS total_revenue
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN LATERAL (
                    SELECT COALESCE(COUNT(*)::int, 0) AS order_count,
                           COALESCE(SUM(o.total), 0)::bigint AS total_spent
                    FROM orders o
                    WHERE o.user_id = u.id
                      AND o.status = ANY(CAST(:spent_statuses AS text[]))
                ) spent ON TRUE";
        $st = DB::conn()->prepare($sql);
        $st->bindValue(':spent_statuses', '{' . implode(',', $statuses) . '}', \PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch() ?: [];
        return [
            'new_segment' => (int)($row['new_segment'] ?? 0),
            'low_spend' => (int)($row['low_spend'] ?? 0),
            'mid_spend' => (int)($row['mid_spend'] ?? 0),
            'vip_segment' => (int)($row['vip_segment'] ?? 0),
            'total_revenue' => (int)($row['total_revenue'] ?? 0),
        ];
    }

    public static function find(int $id): ?array
    {
        $statuses = self::SPENT_STATUSES;

        $sql = "SELECT u.id,
                       u.email,
                       u.status,
                       u.created_at,
                       COALESCE(r.id, 0) AS role_id,
                       COALESCE(r.code, '') AS role_code,
                       COALESCE(r.name, '') AS role_name,
                       COALESCE(cp.full_name, split_part(u.email, '@', 1)) AS full_name,
                       COALESCE(cp.phone, '') AS phone,
                       COALESCE(cp.address_line, '') AS address_line,
                       COALESCE(cp.ward, '') AS ward,
                       COALESCE(cp.district, '') AS district,
                       COALESCE(cp.city, '') AS city,
                       COALESCE(cp.full_address, CONCAT_WS(', ', cp.address_line, cp.ward, cp.district, cp.city), '') AS address,
                       COALESCE(spent.total_spent, 0) AS total_spent,
                       COALESCE(spent.order_count, 0) AS order_count,
                       CASE
                         WHEN COALESCE(spent.order_count, 0) = 0 THEN 'new'
                         WHEN COALESCE(spent.total_spent, 0) > 20000000 THEN 'vip'
                         ELSE 'regular'
                       END AS customer_type
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN customer_profiles cp ON cp.user_id = u.id
                LEFT JOIN LATERAL (
                    SELECT COUNT(*)::int AS order_count,
                           COALESCE(SUM(o.total), 0)::bigint AS total_spent
                    FROM orders o
                    WHERE o.user_id = u.id
                      AND o.status = ANY(CAST(:spent_statuses AS text[]))
                ) spent ON TRUE
                WHERE u.id = :id
                LIMIT 1";

        $st = DB::conn()->prepare($sql);
        $st->bindValue(':spent_statuses', '{' . implode(',', $statuses) . '}', \PDO::PARAM_STR);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function orderHistory(int $userId): array
    {
        $sql = "SELECT id, created_at, total, status
                FROM orders
                WHERE user_id = :user_id
                ORDER BY created_at DESC, id DESC";

        $st = DB::conn()->prepare($sql);
        $st->execute(['user_id' => $userId]);
        return $st->fetchAll();
    }

    public static function updateProfile(int $id, array $data): void
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $stUser = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
            $stUser->execute([
                'id' => $id,
                'email' => $data['email'],
            ]);

            $fullAddress = trim((string)$data['address']);

            $stProfile = $pdo->prepare(
                "INSERT INTO customer_profiles (
                    user_id, full_name, phone, address_line, ward, district, city, full_address, created_at, updated_at
                 ) VALUES (
                    :user_id, :full_name, :phone, :address_line, :ward, :district, :city, :full_address, now(), now()
                 )
                 ON CONFLICT (user_id)
                 DO UPDATE SET
                    full_name = EXCLUDED.full_name,
                    phone = EXCLUDED.phone,
                    address_line = EXCLUDED.address_line,
                    ward = EXCLUDED.ward,
                    district = EXCLUDED.district,
                    city = EXCLUDED.city,
                    full_address = EXCLUDED.full_address,
                    updated_at = now()"
            );
            $stProfile->execute([
                'user_id' => $id,
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'address_line' => $fullAddress,
                'ward' => '',
                'district' => '',
                'city' => '',
                'full_address' => $fullAddress,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function hasOrders(int $id): bool
    {
        $st = DB::conn()->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :id");
        $st->execute(['id' => $id]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function deleteOrBlock(int $id): string
    {
        if (self::hasOrders($id)) {
            self::setBlocked($id, true);
            return 'blocked';
        }

        $st = DB::conn()->prepare("DELETE FROM users WHERE id = :id");
        $st->execute(['id' => $id]);
        return 'deleted';
    }

    public static function toggleStatus(int $id): void
    {
        $st = DB::conn()->prepare(
            "UPDATE users
             SET status = CASE WHEN status = 'active' THEN 'banned' ELSE 'active' END
             WHERE id = :id"
        );
        $st->execute(['id' => $id]);
    }

    public static function setBlocked(int $id, bool $blocked): void
    {
        $st = DB::conn()->prepare("UPDATE users SET status = :status WHERE id = :id");
        $st->execute([
            'id' => $id,
            'status' => $blocked ? 'banned' : 'active',
        ]);
    }
}
