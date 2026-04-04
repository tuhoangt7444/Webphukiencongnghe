<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class InventoryLog
{
    public static function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = DB::conn();

        $q = trim((string)($filters['q'] ?? ''));
        $type = trim((string)($filters['type'] ?? ''));
        $productId = (int)($filters['product_id'] ?? 0);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = 'p.name ILIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if (in_array($type, ['import', 'export'], true)) {
            $where[] = 'il.type = :type';
            $params['type'] = $type;
        }

        if ($productId > 0) {
            $where[] = 'il.product_id = :product_id';
            $params['product_id'] = $productId;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSql = "SELECT COUNT(*)
                     FROM inventory_logs il
                     JOIN products p ON p.id = il.product_id
                     {$whereSql}";
        $countSt = $pdo->prepare($countSql);
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

        $listSql = "SELECT il.id,
                           il.product_id,
                           p.name AS product_name,
                           il.quantity,
                           il.type,
                           COALESCE(il.note, '') AS note,
                           il.created_at
                    FROM inventory_logs il
                    JOIN products p ON p.id = il.product_id
                    {$whereSql}
                    ORDER BY il.created_at DESC, il.id DESC
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
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public static function create(int $productId, int $quantity, string $type, string $note = '', ?PDO $pdo = null): void
    {
        if ($productId <= 0 || $quantity <= 0 || !in_array($type, ['import', 'export'], true)) {
            return;
        }

        $conn = $pdo ?? DB::conn();
        $st = $conn->prepare(
            'INSERT INTO inventory_logs (product_id, quantity, type, note) VALUES (:product_id, :quantity, :type, :note)'
        );
        $st->execute([
            'product_id' => $productId,
            'quantity' => $quantity,
            'type' => $type,
            'note' => $note !== '' ? $note : null,
        ]);
    }
}
