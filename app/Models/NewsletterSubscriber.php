<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class NewsletterSubscriber
{
    public static function subscribe(string $email, string $sourcePage = ''): void
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $normalizedEmail = mb_strtolower(trim($email));
        if ($normalizedEmail === '') {
            throw new \InvalidArgumentException('Email không được để trống.');
        }

        if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email không hợp lệ.');
        }

        $st = $pdo->prepare(
            "INSERT INTO newsletter_subscribers (email, source_page, subscribed_at, updated_at)
             VALUES (:email, :source_page, now(), now())
             ON CONFLICT (email)
             DO UPDATE SET
                status = 'active',
                source_page = EXCLUDED.source_page,
                updated_at = now()"
        );
        $st->execute([
            'email' => $normalizedEmail,
            'source_page' => trim($sourcePage),
        ]);
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $q = trim((string)($filters['q'] ?? ''));
        $status = trim((string)($filters['status'] ?? 'active'));
        if (!in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(email ILIKE :q OR COALESCE(source_page, \'\') ILIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        if ($status !== 'all') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscribers {$whereSql}");
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
            "SELECT id, email, source_page, status, subscribed_at, updated_at
             FROM newsletter_subscribers
             {$whereSql}
             ORDER BY subscribed_at DESC, id DESC
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

    public static function delete(int $id): bool
    {
        $pdo = DB::conn();
        self::ensureTable($pdo);

        $st = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = :id');
        $st->execute(['id' => $id]);

        return $st->rowCount() > 0;
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id bigserial PRIMARY KEY,
                email text NOT NULL UNIQUE,
                source_page text NOT NULL DEFAULT '',
                status text NOT NULL DEFAULT 'active',
                subscribed_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )"
        );

        $pdo->exec("ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS source_page text NOT NULL DEFAULT ''");
        $pdo->exec("ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS status text NOT NULL DEFAULT 'active'");
        $pdo->exec("ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS subscribed_at timestamptz NOT NULL DEFAULT now()");
        $pdo->exec("ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS updated_at timestamptz NOT NULL DEFAULT now()");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_newsletter_subscribers_status ON newsletter_subscribers (status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_newsletter_subscribers_subscribed_at ON newsletter_subscribers (subscribed_at DESC)");
    }
}
