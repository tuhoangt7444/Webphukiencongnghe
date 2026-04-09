<?php
namespace App\Models;

use App\Core\DB;

final class AdminRolePermission
{
    public static function permissionCatalog(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.products' => 'Quản lý sản phẩm',
            'admin.orders' => 'Quản lý đơn hàng',
            'admin.users' => 'Quản lý người dùng',
            'admin.product_discounts' => 'Giảm giá sản phẩm',
            'admin.categories' => 'Quản lý danh mục',
            'admin.vouchers' => 'Quản lý phiếu giảm giá',
            'admin.inventory' => 'Quản lý tồn kho',
            'admin.reviews' => 'Quản lý đánh giá',
            'admin.banners' => 'Quản lý banner',
            'admin.posts' => 'Quản lý bài viết',
            'admin.contacts' => 'Quản lý liên hệ',
            'admin.newsletters' => 'Nhận ưu đãi',
            'admin.reports' => 'Xuất báo cáo',
            'admin.roles' => 'Phân quyền quản trị',
        ];
    }

    public static function pathPermissionMap(): array
    {
        return [
            '/admin/products' => 'admin.products',
            '/admin/orders' => 'admin.orders',
            '/admin/users' => 'admin.users',
            '/admin/product-discounts' => 'admin.product_discounts',
            '/admin/categories' => 'admin.categories',
            '/admin/vouchers' => 'admin.vouchers',
            '/admin/inventory' => 'admin.inventory',
            '/admin/reviews' => 'admin.reviews',
            '/admin/banners' => 'admin.banners',
            '/admin/posts' => 'admin.posts',
            '/admin/contacts' => 'admin.contacts',
            '/admin/newsletters' => 'admin.newsletters',
            '/admin/reports' => 'admin.reports',
            '/admin/roles' => 'admin.roles',
            '/admin' => 'admin.dashboard',
        ];
    }

    public static function permissionPathMap(): array
    {
        return [
            'admin.dashboard' => '/admin',
            'admin.products' => '/admin/products',
            'admin.orders' => '/admin/orders',
            'admin.users' => '/admin/users',
            'admin.product_discounts' => '/admin/product-discounts',
            'admin.categories' => '/admin/categories',
            'admin.vouchers' => '/admin/vouchers',
            'admin.inventory' => '/admin/inventory',
            'admin.reviews' => '/admin/reviews',
            'admin.banners' => '/admin/banners',
            'admin.posts' => '/admin/posts',
            'admin.contacts' => '/admin/contacts',
            'admin.newsletters' => '/admin/newsletters',
            'admin.reports' => '/admin/reports/export',
            'admin.roles' => '/admin/roles',
        ];
    }

    public static function firstAllowedPath(array $permissions): string
    {
        $map = self::permissionPathMap();
        foreach ($map as $permission => $path) {
            if (in_array($permission, $permissions, true)) {
                return $path;
            }
        }

        return '/';
    }

    public static function resolveRequiredPermission(string $path): ?string
    {
        $map = self::pathPermissionMap();

        # Match longest prefix first.
        uksort($map, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($map as $prefix => $permission) {
            if (str_starts_with($path, $prefix)) {
                return $permission;
            }
        }

        return null;
    }

    public static function ensureTables(): void
    {
        $pdo = DB::conn();

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS permissions (
                id BIGSERIAL PRIMARY KEY,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS role_permissions (
                id BIGSERIAL PRIMARY KEY,
                role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
                permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
                UNIQUE(role_id, permission_id)
            )"
        );

        foreach (self::permissionCatalog() as $code => $name) {
            $st = $pdo->prepare(
                "INSERT INTO permissions (code, name)
                 VALUES (:code, :name)
                 ON CONFLICT (code)
                 DO UPDATE SET name = EXCLUDED.name"
            );
            $st->execute([
                'code' => $code,
                'name' => $name,
            ]);
        }
    }

    public static function listRoles(): array
    {
        self::ensureTables();
        $sql = "SELECT r.id,
                       r.code,
                       r.name,
                       r.is_system,
                       COUNT(DISTINCT rp.permission_id)::int AS permission_count,
                       COUNT(DISTINCT u.id)::int AS user_count
                FROM roles r
                LEFT JOIN role_permissions rp ON rp.role_id = r.id
                LEFT JOIN users u ON u.role_id = r.id
                GROUP BY r.id, r.code, r.name, r.is_system
                ORDER BY r.is_system DESC, r.id ASC";

        return DB::conn()->query($sql)->fetchAll() ?: [];
    }

    public static function listAssignableRoles(): array
    {
        self::ensureTables();
        $sql = "SELECT id, code, name
                FROM roles
                                ORDER BY CASE
                                                     WHEN code = 'admin' THEN 0
                                                     WHEN code = 'customer' THEN 1
                                                     ELSE 2
                                                 END, name";

        return DB::conn()->query($sql)->fetchAll() ?: [];
    }

    public static function findRole(int $roleId): ?array
    {
        self::ensureTables();
        $st = DB::conn()->prepare('SELECT id, code, name, is_system FROM roles WHERE id = :id LIMIT 1');
        $st->execute(['id' => $roleId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function getRolePermissions(int $roleId): array
    {
        self::ensureTables();
        $st = DB::conn()->prepare(
            "SELECT p.code
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id"
        );
        $st->execute(['role_id' => $roleId]);

        return array_values(array_unique(array_map('strval', $st->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
    }

    public static function getPermissionsByUserId(int $userId): array
    {
        self::ensureTables();
        $st = DB::conn()->prepare(
            "SELECT p.code
             FROM users u
             INNER JOIN role_permissions rp ON rp.role_id = u.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :user_id"
        );
        $st->execute(['user_id' => $userId]);

        return array_values(array_unique(array_map('strval', $st->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
    }

    public static function createRole(string $name): int
    {
        self::ensureTables();

        $name = trim($name);
        if ($name === '') {
            return 0;
        }

        $base = self::slugify($name);
        if ($base === '' || $base === 'admin' || $base === 'customer') {
            $base = 'staff';
        }

        $pdo = DB::conn();
        $code = $base;
        $n = 1;

        while (true) {
            $st = $pdo->prepare('SELECT 1 FROM roles WHERE code = :code LIMIT 1');
            $st->execute(['code' => $code]);
            if (!$st->fetchColumn()) {
                break;
            }

            $n++;
            $code = $base . '_' . $n;
        }

        $insert = $pdo->prepare(
            "INSERT INTO roles (code, name, is_system)
             VALUES (:code, :name, false)
             RETURNING id"
        );
        $insert->execute([
            'code' => $code,
            'name' => $name,
        ]);

        return (int)$insert->fetchColumn();
    }

    public static function updateRole(int $roleId, string $name, array $permissionCodes): bool
    {
        self::ensureTables();

        $role = self::findRole($roleId);
        if (!$role) {
            return false;
        }

        $name = trim($name);
        if ($name === '') {
            return false;
        }

        $catalog = self::permissionCatalog();
        $validCodes = array_values(array_filter(
            array_map('strval', $permissionCodes),
            static fn(string $code): bool => isset($catalog[$code])
        ));

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('UPDATE roles SET name = :name WHERE id = :id');
            $st->execute([
                'id' => $roleId,
                'name' => $name,
            ]);

            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')
                ->execute(['role_id' => $roleId]);

            if ($validCodes !== []) {
                $permSt = $pdo->prepare('SELECT id FROM permissions WHERE code = :code LIMIT 1');
                $insertSt = $pdo->prepare(
                    'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
                );

                foreach ($validCodes as $code) {
                    $permSt->execute(['code' => $code]);
                    $permissionId = (int)$permSt->fetchColumn();
                    if ($permissionId <= 0) {
                        continue;
                    }

                    $insertSt->execute([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function deleteRole(int $roleId): bool
    {
        self::ensureTables();

        $role = self::findRole($roleId);
        if (!$role || (string)($role['code'] ?? '') === 'admin') {
            return false;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $stUsed = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = :role_id');
            $stUsed->execute(['role_id' => $roleId]);
            $usedCount = (int)$stUsed->fetchColumn();

            if ($usedCount > 0) {
                $fallbackRoleId = self::resolveFallbackRoleId($roleId, (string)($role['code'] ?? ''));
                if ($fallbackRoleId <= 0) {
                    $pdo->rollBack();
                    return false;
                }

                $stMove = $pdo->prepare('UPDATE users SET role_id = :to_role_id WHERE role_id = :from_role_id');
                $stMove->execute([
                    'to_role_id' => $fallbackRoleId,
                    'from_role_id' => $roleId,
                ]);
            }

            $st = $pdo->prepare('DELETE FROM roles WHERE id = :id');
            $st->execute(['id' => $roleId]);

            $pdo->commit();
            return $st->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function resolveFallbackRoleId(int $deletingRoleId, string $deletingRoleCode): int
    {
        $pdo = DB::conn();

        # Prefer moving users to customer role when deleting non-customer roles.
        if ($deletingRoleCode !== 'customer') {
            $stCustomer = $pdo->prepare("SELECT id FROM roles WHERE code = 'customer' LIMIT 1");
            $stCustomer->execute();
            $customerRoleId = (int)$stCustomer->fetchColumn();
            if ($customerRoleId > 0 && $customerRoleId !== $deletingRoleId) {
                return $customerRoleId;
            }
        }

        # Otherwise pick any role except admin and the role being deleted.
        $stAny = $pdo->prepare(
            "SELECT id
             FROM roles
             WHERE id <> :id
               AND code <> 'admin'
             ORDER BY CASE WHEN code = 'customer' THEN 0 ELSE 1 END, id ASC
             LIMIT 1"
        );
        $stAny->execute(['id' => $deletingRoleId]);

        return (int)$stAny->fetchColumn();
    }

    public static function assignRoleToUser(int $userId, int $roleId): bool
    {
        self::ensureTables();

        if ($userId <= 0 || $roleId <= 0) {
            return false;
        }

        $role = self::findRole($roleId);
        if (!$role) {
            return false;
        }

        $st = DB::conn()->prepare('UPDATE users SET role_id = :role_id WHERE id = :user_id');
        $st->execute([
            'role_id' => $roleId,
            'user_id' => $userId,
        ]);

        return $st->rowCount() >= 0;
    }

    private static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s_-]+/', '', $value) ?? '';
        $value = preg_replace('/[\s-]+/', '_', $value) ?? '';
        return trim($value, '_');
    }
}
