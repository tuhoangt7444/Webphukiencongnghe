<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class Contact
{
    public static function create(array $payload): int
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $st = $pdo->prepare(
            'INSERT INTO contacts (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message) RETURNING id'
        );
        $st->execute([
            'name' => trim((string)($payload['name'] ?? '')),
            'email' => trim((string)($payload['email'] ?? '')),
            'phone' => trim((string)($payload['phone'] ?? '')),
            'subject' => trim((string)($payload['subject'] ?? '')),
            'message' => trim((string)($payload['message'] ?? '')),
        ]);

        return (int)$st->fetchColumn();
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $q = trim((string)($filters['q'] ?? ''));
        $handled = trim((string)($filters['handled'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(name ILIKE :q OR email ILIKE :q OR phone ILIKE :q OR subject ILIKE :q OR message ILIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        if ($handled === '0' || $handled === '1') {
            $where[] = 'is_handled = :handled';
            $params['handled'] = $handled === '1';
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM contacts {$whereSql}");
        foreach ($params as $k => $v) {
            $countSt->bindValue(':' . $k, $v, is_bool($v) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
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
            "SELECT id, name, email, phone, subject, message, is_handled, handled_at, created_at
             FROM contacts
             {$whereSql}
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $k => $v) {
            $st->bindValue(':' . $k, $v, is_bool($v) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
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

    public static function markHandled(int $id): bool
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $st = $pdo->prepare(
            'UPDATE contacts
             SET is_handled = TRUE,
                 handled_at = now()
             WHERE id = :id'
        );
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $st = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS contacts (
                id bigserial PRIMARY KEY,
                name text NOT NULL,
                email text NOT NULL,
                phone text NOT NULL DEFAULT '',
                subject text NOT NULL DEFAULT '',
                message text NOT NULL,
                is_handled boolean NOT NULL DEFAULT false,
                handled_at timestamptz NULL,
                created_at timestamptz NOT NULL DEFAULT now()
            )"
        );

        $pdo->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS subject text NOT NULL DEFAULT ''");
        $pdo->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS is_handled boolean NOT NULL DEFAULT false");
        $pdo->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS handled_at timestamptz NULL");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contacts_created_at ON contacts (created_at DESC)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contacts_handled ON contacts (is_handled)");
    }
}
