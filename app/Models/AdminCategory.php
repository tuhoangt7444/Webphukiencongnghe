<?php
namespace App\Models;

use App\Core\DB;

final class AdminCategory
{
    public static function list(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $pdo = DB::conn();
        $q = trim((string)($filters['q'] ?? ''));
        $sort = trim((string)($filters['sort'] ?? 'created_at'));
        $direction = strtolower(trim((string)($filters['direction'] ?? 'desc')));

        $allowedSort = [
            'name' => 'c.name',
            'product_count' => 'product_count',
            'created_at' => 'c.created_at',
        ];
        $sortSql = $allowedSort[$sort] ?? 'c.created_at';
        $directionSql = $direction === 'asc' ? 'ASC' : 'DESC';

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(c.name ILIKE :q OR c.slug ILIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM categories c {$whereSql}");
        $countSt->execute($params);
        $total = (int)$countSt->fetchColumn();

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT c.id,
                       c.name,
                       c.slug,
                       COALESCE(c.icon, 'fa-folder-tree') AS icon,
                       COALESCE(c.description, '') AS description,
                       COALESCE(c.status, 'active') AS status,
                       c.created_at,
                       COUNT(p.id)::bigint AS product_count
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                {$whereSql}
                GROUP BY c.id
                ORDER BY {$sortSql} {$directionSql}, c.id DESC
                LIMIT :limit OFFSET :offset";

        $st = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue(':' . $key, $value);
        }
        $st->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, \PDO::PARAM_INT);
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
        $st = DB::conn()->prepare(
            "SELECT id, name, slug,
                    COALESCE(icon, 'fa-folder-tree') AS icon,
                    COALESCE(description, '') AS description,
                    COALESCE(status, 'active') AS status,
                    created_at
             FROM categories
             WHERE id = :id
             LIMIT 1"
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function create(array $data): void
    {
        $st = DB::conn()->prepare(
            "INSERT INTO categories (name, slug, icon, description, status, created_at)
             VALUES (:name, :slug, :icon, :description, :status, now())"
        );
        $st->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'icon' => $data['icon'] ?: 'fa-folder-tree',
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $st = DB::conn()->prepare(
            "UPDATE categories
             SET name = :name,
                 slug = :slug,
                 icon = :icon,
                 description = :description,
                 status = :status
             WHERE id = :id"
        );
        $st->execute([
            'id' => $id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'icon' => $data['icon'] ?: 'fa-folder-tree',
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
        ]);
    }

    public static function canDelete(int $id): bool
    {
        $st = DB::conn()->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $st->execute(['id' => $id]);
        return (int)$st->fetchColumn() === 0;
    }

    public static function delete(int $id): bool
    {
        if (!self::canDelete($id)) {
            return false;
        }

        $st = DB::conn()->prepare("DELETE FROM categories WHERE id = :id");
        $st->execute(['id' => $id]);
        return true;
    }
}
