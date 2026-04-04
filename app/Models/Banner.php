<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class Banner
{
    private const POSITIONS = ['home_slider', 'category_banner', 'promo_banner', 'sidebar_banner'];

    public static function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $pdo = DB::conn();

        $position = trim((string)($filters['position'] ?? ''));
        $q = trim((string)($filters['q'] ?? ''));

        $where = [];
        $params = [];

        if (in_array($position, self::POSITIONS, true)) {
            $where[] = 'position = :position';
            $params['position'] = $position;
        }

        if ($q !== '') {
            $where[] = "(title ILIKE :q OR COALESCE(link, '') ILIKE :q)";
            $params['q'] = '%' . $q . '%';
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM banners {$whereSql}");
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
            "SELECT id, title, image, link, position, status, created_at
             FROM banners
             {$whereSql}
             ORDER BY created_at DESC, id DESC
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
        $st = DB::conn()->prepare('SELECT id, title, image, link, position, status, created_at FROM banners WHERE id = :id LIMIT 1');
        $st->execute(['id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $st = DB::conn()->prepare(
            "INSERT INTO banners (title, image, link, position, status)
             VALUES (:title, :image, :link, :position, :status)
             RETURNING id"
        );
        $st->execute([
            'title' => $data['title'],
            'image' => $data['image'],
            'link' => $data['link'] !== '' ? $data['link'] : null,
            'position' => $data['position'],
            'status' => $data['status'],
        ]);

        return (int)$st->fetchColumn();
    }

    public static function update(int $id, array $data): void
    {
        $st = DB::conn()->prepare(
            "UPDATE banners
             SET title = :title,
                 image = :image,
                 link = :link,
                 position = :position,
                 status = :status
             WHERE id = :id"
        );
        $st->execute([
            'id' => $id,
            'title' => $data['title'],
            'image' => $data['image'],
            'link' => $data['link'] !== '' ? $data['link'] : null,
            'position' => $data['position'],
            'status' => $data['status'],
        ]);
    }

    public static function toggle(int $id): bool
    {
        $st = DB::conn()->prepare(
            "UPDATE banners
             SET status = CASE WHEN status = 'active' THEN 'hidden' ELSE 'active' END
             WHERE id = :id"
        );
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $st = DB::conn()->prepare('DELETE FROM banners WHERE id = :id');
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    public static function activeByPosition(string $position, int $limit = 10): array
    {
        if (!in_array($position, self::POSITIONS, true)) {
            return [];
        }

        $st = DB::conn()->prepare(
            "SELECT id, title, image, link, position, status, created_at
             FROM banners
             WHERE position = :position
               AND status = 'active'
             ORDER BY created_at DESC, id DESC
             LIMIT :limit"
        );
        $st->bindValue(':position', $position, PDO::PARAM_STR);
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }
}
