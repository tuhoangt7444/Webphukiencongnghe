<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class Category
{
    public static function countActive(): int
    {
        $st = DB::conn()->query(
            "SELECT COUNT(*)
             FROM categories c
             WHERE COALESCE(c.status, 'active') = 'active'"
        );

        return (int)$st->fetchColumn();
    }

    public static function homeFeatured(int $limit = 6): array
    {
        $st = DB::conn()->prepare(
            "SELECT c.id,
                    c.name,
                    c.slug,
                    c.icon,
                    COUNT(DISTINCT p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = TRUE
             WHERE COALESCE(c.status, 'active') = 'active'
             GROUP BY c.id, c.name, c.slug, c.icon
             ORDER BY COUNT(DISTINCT p.id) DESC, c.created_at DESC, c.id DESC
             LIMIT :limit"
        );
        $st->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }
}
