<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminRolePermission;

final class AdminRoleController extends Controller
{
    public function index(): void
    {
        $this->view('admin/roles/index', [
            'title' => 'Phân quyền quản trị',
            'roles' => AdminRolePermission::listRoles(),
            'status' => (string)$this->request->input('status', ''),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $name = trim((string)$this->request->input('name', ''));
        if ($name === '') {
            $this->response->redirect('/admin/roles?status=invalid');
            return;
        }

        $roleId = AdminRolePermission::createRole($name);
        if ($roleId <= 0) {
            $this->response->redirect('/admin/roles?status=create-failed');
            return;
        }

        $this->response->redirect('/admin/roles/' . $roleId . '/edit?status=created');
    }

    public function edit(string $id): void
    {
        $roleId = (int)$id;
        $role = AdminRolePermission::findRole($roleId);

        if (!$role) {
            $this->response->redirect('/admin/roles?status=not-found');
            return;
        }

        $this->view('admin/roles/edit', [
            'title' => 'Chỉnh sửa vai trò',
            'role' => $role,
            'catalog' => AdminRolePermission::permissionCatalog(),
            'selectedPermissions' => AdminRolePermission::getRolePermissions($roleId),
            'status' => (string)$this->request->input('status', ''),
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        $roleId = (int)$id;
        $name = trim((string)$this->request->input('name', ''));
        $permissions = $this->request->input('permissions', []);
        $permissionCodes = is_array($permissions) ? $permissions : [];

        $ok = AdminRolePermission::updateRole($roleId, $name, $permissionCodes);
        if (!$ok) {
            $this->response->redirect('/admin/roles/' . $roleId . '/edit?status=invalid');
            return;
        }

        $this->response->redirect('/admin/roles/' . $roleId . '/edit?status=updated');
    }

    public function destroy(string $id): void
    {
        $roleId = (int)$id;
        $ok = AdminRolePermission::deleteRole($roleId);
        $this->response->redirect('/admin/roles?status=' . ($ok ? 'deleted' : 'delete-failed'));
    }
}
