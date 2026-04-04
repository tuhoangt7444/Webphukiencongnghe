<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminRolePermission;
use App\Models\AdminUser;

final class AdminUserController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'customer_type' => trim((string)$this->request->input('customer_type', '')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));
        $result = AdminUser::list($filters, $page, 12);

        $this->view('admin/users/index', [
            'title' => 'Quản lý người dùng',
            'rows' => $result['rows'],
            'stats' => $result['stats'],
            'segmentStats' => AdminUser::segmentStats(),
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'status' => (string)$this->request->input('status', ''),
            'currentUserId' => (int)($_SESSION['user_id'] ?? 0),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $userId = (int)$id;
        $user = AdminUser::find($userId);

        if (!$user) {
            $this->response->redirect('/admin/users?status=not-found');
            return;
        }

        $this->view('admin/users/show', [
            'title' => 'Chi tiết khách hàng',
            'row' => $user,
            'orders' => AdminUser::orderHistory($userId),
            'status' => (string)$this->request->input('status', ''),
            'segmentStats' => AdminUser::segmentStats(),
        ], 'layouts/admin');
    }

    public function edit(string $id): void
    {
        $userId = (int)$id;
        $user = AdminUser::find($userId);

        if (!$user) {
            $this->response->redirect('/admin/users?status=not-found');
            return;
        }

        $this->view('admin/users/edit', [
            'title' => 'Chỉnh sửa khách hàng',
            'row' => $user,
            'roles' => AdminRolePermission::listAssignableRoles(),
            'status' => (string)$this->request->input('status', ''),
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        $userId = (int)$id;

        $fullName = trim((string)$this->request->input('full_name', ''));
        $email = trim((string)$this->request->input('email', ''));
        $phone = trim((string)$this->request->input('phone', ''));
        $address = trim((string)$this->request->input('address', ''));
        $roleId = (int)$this->request->input('role_id', 0);

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->redirect('/admin/users/' . $userId . '/edit?status=invalid');
            return;
        }

        try {
            AdminUser::updateProfile($userId, [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
            ]);

            if ($roleId > 0) {
                AdminRolePermission::assignRoleToUser($userId, $roleId);
            }

            $this->response->redirect('/admin/users/' . $userId . '?status=updated');
        } catch (\PDOException $e) {
            $this->response->redirect('/admin/users/' . $userId . '/edit?status=exists');
        }
    }

    public function toggle(string $id): void
    {
        $targetUserId = (int)$id;
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
            $this->response->redirect('/admin/users?status=forbidden');
            return;
        }

        AdminUser::toggleStatus($targetUserId);
        $this->response->redirect('/admin/users?status=toggled');
    }

    public function destroy(string $id): void
    {
        $targetUserId = (int)$id;
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
            $this->response->redirect('/admin/users?status=forbidden');
            return;
        }

        $action = AdminUser::deleteOrBlock($targetUserId);
        $this->response->redirect('/admin/users?status=' . $action);
    }
}
