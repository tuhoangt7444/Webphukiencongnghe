<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class Post
{
    private static function tableExists(): bool
    {
        $st = DB::conn()->query("SELECT to_regclass('public.posts')");
        return (string)$st->fetchColumn() !== '';
    }

    private static function relatedTableExists(): bool
    {
        $pdo = DB::conn();
        $st = $pdo->query("SELECT to_regclass('public.post_related_products')");
        $exists = (string)$st->fetchColumn() !== '';
        if ($exists) {
            return true;
        }

        // Try to bootstrap relation table so admin feature can work without manual migration.
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS post_related_products (
                    id BIGSERIAL PRIMARY KEY,
                    post_id BIGINT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    sort_order INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                    UNIQUE (post_id, product_id)
                )"
            );
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_post_related_products_post ON post_related_products (post_id, sort_order, id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_post_related_products_product ON post_related_products (product_id)");
        } catch (\Throwable $e) {
            return false;
        }

        $st = $pdo->query("SELECT to_regclass('public.post_related_products')");
        return (string)$st->fetchColumn() !== '';
    }

    public static function latestPublished(int $limit = 3): array
    {
        if (!self::tableExists()) {
            return [];
        }

        $st = DB::conn()->prepare(
            "SELECT id, title, slug, excerpt, cover_image, status,
                    COALESCE(published_at, created_at) AS posted_at
             FROM posts
             WHERE status = 'published'
             ORDER BY COALESCE(published_at, created_at) DESC, id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        if (!self::tableExists()) {
            return null;
        }

        $st = DB::conn()->prepare(
            "SELECT id, title, slug, excerpt, content, cover_image, status,
                    COALESCE(published_at, created_at) AS posted_at
             FROM posts
             WHERE slug = :slug
               AND status = 'published'
             LIMIT 1"
        );
        $st->execute(['slug' => trim($slug)]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function isAvailable(): bool
    {
        return self::tableExists();
    }

    public static function relatedFeatureReady(): bool
    {
        return self::tableExists() && self::relatedTableExists();
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        if (!self::tableExists()) {
            return [
                'rows' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => 1,
                    'per_page' => max(1, $perPage),
                    'total_pages' => 1,
                ],
            ];
        }

        $pdo = DB::conn();
        $q = trim((string)($filters['q'] ?? ''));
        $status = trim((string)($filters['status'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(title ILIKE :q OR slug ILIKE :q OR COALESCE(excerpt, \'\') ILIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        if (in_array($status, ['draft', 'published', 'hidden'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM posts {$whereSql}");
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

        $st = $pdo->prepare(
            "SELECT id, title, slug, excerpt, cover_image, status, published_at, created_at, updated_at
             FROM posts
             {$whereSql}
             ORDER BY COALESCE(published_at, created_at) DESC, id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return [
            'rows' => $st->fetchAll(),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public static function find(int $id): ?array
    {
        if (!self::tableExists()) {
            return null;
        }

        $st = DB::conn()->prepare(
            'SELECT id, title, slug, excerpt, content, cover_image, status, published_at, created_at, updated_at
             FROM posts
             WHERE id = :id
             LIMIT 1'
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function slugExists(string $slug, int $exceptId = 0): bool
    {
        if (!self::tableExists()) {
            return false;
        }

        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        if ($exceptId > 0) {
            $st = DB::conn()->prepare('SELECT 1 FROM posts WHERE slug = :slug AND id <> :id LIMIT 1');
            $st->execute(['slug' => $slug, 'id' => $exceptId]);
        } else {
            $st = DB::conn()->prepare('SELECT 1 FROM posts WHERE slug = :slug LIMIT 1');
            $st->execute(['slug' => $slug]);
        }

        return (bool)$st->fetchColumn();
    }

    public static function create(array $data): int
    {
        $st = DB::conn()->prepare(
            'INSERT INTO posts (title, slug, excerpt, content, cover_image, status, published_at)
             VALUES (:title, :slug, :excerpt, :content, :cover_image, :status, :published_at)
             RETURNING id'
        );
        $st->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            'content' => $data['content'],
            'cover_image' => $data['cover_image'] !== '' ? $data['cover_image'] : null,
            'status' => $data['status'],
            'published_at' => $data['published_at'],
        ]);

        return (int)$st->fetchColumn();
    }

    public static function update(int $id, array $data): bool
    {
        $st = DB::conn()->prepare(
            'UPDATE posts
             SET title = :title,
                 slug = :slug,
                 excerpt = :excerpt,
                 content = :content,
                 cover_image = :cover_image,
                 status = :status,
                 published_at = :published_at,
                 updated_at = now()
             WHERE id = :id'
        );
        $st->execute([
            'id' => $id,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            'content' => $data['content'],
            'cover_image' => $data['cover_image'] !== '' ? $data['cover_image'] : null,
            'status' => $data['status'],
            'published_at' => $data['published_at'],
        ]);

        return $st->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare('DELETE FROM posts WHERE id = :id');
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    public static function relatedProductIds(int $postId): array
    {
        if (!self::tableExists() || !self::relatedTableExists()) {
            return [];
        }

        $st = DB::conn()->prepare(
            'SELECT product_id
             FROM post_related_products
             WHERE post_id = :post_id
             ORDER BY sort_order ASC, id ASC'
        );
        $st->execute(['post_id' => $postId]);

        return array_map('intval', array_column($st->fetchAll(), 'product_id'));
    }

    public static function syncRelatedProducts(int $postId, array $productIds): void
    {
        if (!self::tableExists() || !self::relatedTableExists()) {
            return;
        }

        $pdo = DB::conn();
        $cleanIds = [];
        foreach ($productIds as $pid) {
            $id = (int)$pid;
            if ($id > 0) {
                $cleanIds[] = $id;
            }
        }

        $cleanIds = array_values(array_unique($cleanIds));

        $pdo->beginTransaction();
        try {
            $deleteSt = $pdo->prepare('DELETE FROM post_related_products WHERE post_id = :post_id');
            $deleteSt->execute(['post_id' => $postId]);

            if ($cleanIds !== []) {
                $insertSt = $pdo->prepare(
                    'INSERT INTO post_related_products (post_id, product_id, sort_order)
                     VALUES (:post_id, :product_id, :sort_order)'
                );

                foreach ($cleanIds as $idx => $productId) {
                    $insertSt->execute([
                        'post_id' => $postId,
                        'product_id' => $productId,
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function relatedProducts(int $postId, int $limit = 4): array
    {
        if (!self::tableExists() || !self::relatedTableExists()) {
            return [];
        }

        $st = DB::conn()->prepare(
            "WITH first_image AS (
                SELECT DISTINCT ON (pi.product_id)
                       pi.product_id,
                       pi.image_url
                FROM product_images pi
                ORDER BY pi.product_id, pi.sort_order ASC, pi.id ASC
            )
            SELECT p.id,
                   p.name,
                   p.slug,
                   COALESCE(MIN(v.sale_price), 0)::bigint AS price_from,
                   COALESCE(fi.image_url, '') AS image_url,
                   rp.sort_order
            FROM post_related_products rp
            JOIN products p ON p.id = rp.product_id
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
            LEFT JOIN first_image fi ON fi.product_id = p.id
            WHERE rp.post_id = :post_id
              AND p.is_active = TRUE
            GROUP BY p.id, fi.image_url, rp.sort_order
            ORDER BY rp.sort_order ASC, p.id DESC
            LIMIT :limit"
        );
        $st->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public static function toggleVisibility(int $id): bool
    {
        if (!self::tableExists()) {
            return false;
        }

        $st = DB::conn()->prepare(
            "UPDATE posts
             SET status = CASE
                            WHEN status = 'hidden' THEN 'published'
                            ELSE 'hidden'
                          END,
                 published_at = CASE
                                  WHEN status = 'hidden' THEN COALESCE(published_at, now())
                                  ELSE published_at
                                END,
                 updated_at = now()
             WHERE id = :id"
        );
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }
}
