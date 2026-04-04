<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class Review
{
    private const STATUSES = ['visible', 'hidden', 'spam'];

    public static function findByUserAndProduct(int $userId, int $productId): ?array
    {
        $st = DB::conn()->prepare(
            "SELECT id,
                    product_id,
                    user_id,
                    rating,
                    comment,
                    status,
                    created_at
             FROM reviews
             WHERE user_id = :user_id
               AND product_id = :product_id
             ORDER BY created_at DESC, id DESC
             LIMIT 1"
        );
        $st->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        $row = $st->fetch();
        return $row ?: null;
    }

    public static function canUserReviewProduct(int $userId, int $productId): bool
    {
        $st = DB::conn()->prepare(
            "SELECT EXISTS(
                SELECT 1
                FROM orders o
                JOIN order_items oi ON oi.order_id = o.id
                WHERE o.user_id = :user_id
                  AND oi.product_id = :product_id
                  AND o.status NOT IN ('rejected', 'cancelled')
            )"
        );
        $st->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return (bool)$st->fetchColumn();
    }

    public static function createByCustomer(int $userId, int $productId, int $rating, string $comment): int
    {
        $st = DB::conn()->prepare(
            "INSERT INTO reviews (product_id, user_id, rating, comment, status)
             VALUES (:product_id, :user_id, :rating, :comment, 'visible')
             RETURNING id"
        );
        $st->execute([
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => max(1, min(5, $rating)),
            'comment' => $comment,
        ]);

        return (int)$st->fetchColumn();
    }

    public static function listByUser(int $userId): array
    {
        $st = DB::conn()->prepare(
            "SELECT r.id,
                    r.product_id,
                    r.rating,
                    r.comment,
                    r.status,
                    r.created_at,
                    p.name AS product_name,
                    COALESCE(p.slug, '') AS product_slug
             FROM reviews r
             JOIN products p ON p.id = r.product_id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC, r.id DESC"
        );
        $st->execute(['user_id' => $userId]);

        return $st->fetchAll();
    }

    public static function latestVisible(int $limit = 6): array
    {
        $st = DB::conn()->prepare(
            "SELECT r.rating,
                    r.product_id,
                    r.comment,
                    r.created_at,
                    p.name AS product_name,
                    COALESCE(p.slug, '') AS product_slug,
                    COALESCE(cp.full_name, split_part(u.email, '@', 1)) AS customer_name
             FROM reviews r
             JOIN products p ON p.id = r.product_id
             JOIN users u ON u.id = r.user_id
             LEFT JOIN customer_profiles cp ON cp.user_id = u.id
             WHERE r.status = 'visible'
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $pdo = DB::conn();

        $q = trim((string)($filters['q'] ?? ''));
        $rating = (int)($filters['rating'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(p.name ILIKE :q OR COALESCE(cp.full_name, split_part(u.email, '@', 1)) ILIKE :q)";
            $params['q'] = '%' . $q . '%';
        }

        if ($rating >= 1 && $rating <= 5) {
            $where[] = 'r.rating = :rating';
            $params['rating'] = $rating;
        }

        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'r.status = :status';
            $params['status'] = $status;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $fromSql = "FROM reviews r
                    JOIN products p ON p.id = r.product_id
                    JOIN users u ON u.id = r.user_id
                    LEFT JOIN customer_profiles cp ON cp.user_id = u.id";

        $countSt = $pdo->prepare("SELECT COUNT(*) {$fromSql} {$whereSql}");
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

        $listSql = "SELECT r.id,
                           r.product_id,
                           r.user_id,
                           r.rating,
                           r.comment,
                           r.status,
                           r.created_at,
                           p.name AS product_name,
                           COALESCE(cp.full_name, split_part(u.email, '@', 1)) AS customer_name,
                           u.email AS customer_email
                    {$fromSql}
                    {$whereSql}
                    ORDER BY r.created_at DESC, r.id DESC
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
            'avg_by_product' => self::averageByProduct(8),
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
        $sql = "SELECT
                    COUNT(*) AS total_reviews,
                    COUNT(*) FILTER (WHERE rating = 5) AS star_5,
                    COUNT(*) FILTER (WHERE rating = 4) AS star_4,
                    COUNT(*) FILTER (WHERE rating IN (1, 2)) AS low_star
                FROM reviews";

        $row = DB::conn()->query($sql)->fetch() ?: [];

        return [
            'total_reviews' => (int)($row['total_reviews'] ?? 0),
            'star_5' => (int)($row['star_5'] ?? 0),
            'star_4' => (int)($row['star_4'] ?? 0),
            'low_star' => (int)($row['low_star'] ?? 0),
        ];
    }

    public static function averageByProduct(int $limit = 8): array
    {
        $st = DB::conn()->prepare(
            "SELECT p.id AS product_id,
                    p.name AS product_name,
                    AVG(r.rating)::numeric(3,2) AS avg_rating,
                    COUNT(*)::int AS total_reviews
             FROM reviews r
             JOIN products p ON p.id = r.product_id
             GROUP BY p.id, p.name
             ORDER BY avg_rating DESC, total_reviews DESC, p.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = DB::conn()->prepare(
            "SELECT r.id,
                    r.product_id,
                    r.user_id,
                    r.rating,
                    r.comment,
                    r.status,
                    r.created_at,
                    p.name AS product_name,
                    COALESCE(cp.full_name, split_part(u.email, '@', 1)) AS customer_name,
                    u.email AS customer_email
             FROM reviews r
             JOIN products p ON p.id = r.product_id
             JOIN users u ON u.id = r.user_id
             LEFT JOIN customer_profiles cp ON cp.user_id = u.id
             WHERE r.id = :id
             LIMIT 1"
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $st = DB::conn()->prepare('UPDATE reviews SET status = :status WHERE id = :id');
        $st->execute(['id' => $id, 'status' => $status]);

        return $st->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $row = self::find($id);
        if (!$row) {
            return false;
        }

        if ((string)$row['status'] !== 'spam') {
            return false;
        }

        $st = DB::conn()->prepare('DELETE FROM reviews WHERE id = :id');
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    public static function visibleByProduct(int $productId, int $limit = 10): array
    {
        $st = DB::conn()->prepare(
            "SELECT r.id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    COALESCE(cp.full_name, split_part(u.email, '@', 1)) AS customer_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             LEFT JOIN customer_profiles cp ON cp.user_id = u.id
             WHERE r.product_id = :product_id
               AND r.status = 'visible'
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function visibleAverageByProduct(int $productId): array
    {
        $st = DB::conn()->prepare(
            "SELECT COALESCE(AVG(rating)::numeric(3,2), 0) AS avg_rating,
                    COUNT(*)::int AS total_reviews
             FROM reviews
             WHERE product_id = :product_id
               AND status = 'visible'"
        );
        $st->execute(['product_id' => $productId]);
        $row = $st->fetch() ?: [];

        return [
            'avg_rating' => (float)($row['avg_rating'] ?? 0),
            'total_reviews' => (int)($row['total_reviews'] ?? 0),
        ];
    }
}
