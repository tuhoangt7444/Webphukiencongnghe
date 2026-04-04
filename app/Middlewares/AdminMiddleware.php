<?php
namespace App\Middlewares;

use App\Core\DB;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Models\AdminRolePermission;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $path = $request->path();
            header('Location: /login?status=auth-required&next=' . rawurlencode($path));
            return null;
        }

        $roleInfo = $this->resolveRoleInfo((int)$_SESSION['user_id']);
        $roleCode = (string)($roleInfo['code'] ?? '');
        $roleId = (int)($roleInfo['id'] ?? 0);
        $_SESSION['user_role_code'] = $roleCode;
        $_SESSION['user_role_id'] = $roleId;

        if ($roleCode === 'customer' || $roleCode === '') {
            header('Location: /?status=forbidden');
            return null;
        }

        if ($roleCode === 'admin') {
            $_SESSION['admin_permissions'] = array_keys(AdminRolePermission::permissionCatalog());
            return $next();
        }

        $permissions = AdminRolePermission::getPermissionsByUserId((int)$_SESSION['user_id']);
        $_SESSION['admin_permissions'] = $permissions;

        $path = $request->path();
        if ($path === '/admin' && !in_array('admin.dashboard', $permissions, true)) {
            $firstPath = AdminRolePermission::firstAllowedPath($permissions);
            if ($firstPath !== '/' && $firstPath !== '/admin') {
                header('Location: ' . $firstPath);
                return null;
            }
        }

        $requiredPermission = AdminRolePermission::resolveRequiredPermission($path);
        if ($requiredPermission !== null && !in_array($requiredPermission, $permissions, true)) {
            header('Location: /?status=forbidden');
            return null;
        }

        return $next();
    }

    private function resolveRoleInfo(int $userId): array
    {
        AdminRolePermission::ensureTables();
        $pdo = DB::conn();
        $st = $pdo->prepare(
            'SELECT r.id, r.code
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $st->execute(['id' => $userId]);
        $row = $st->fetch();

        return [
            'id' => (int)($row['id'] ?? 0),
            'code' => is_string($row['code'] ?? null) ? (string)$row['code'] : '',
        ];
    }
}
