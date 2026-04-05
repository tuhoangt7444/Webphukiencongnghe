<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\SafeUploadService;
use App\Services\SecurityLogger;

class AccountController extends Controller
{
    public function profile(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required');
            return;
        }

        $profile = User::getCustomerProfileByUserId((int)$_SESSION['user_id']);
        $avatarUrl = User::getResolvedAvatarByUserId((int)$_SESSION['user_id']);
        $_SESSION['user_avatar'] = $avatarUrl;

        $this->view('account/profile', [
            'title' => 'Thông tin tài khoản',
            'userId' => (int)$_SESSION['user_id'],
            'userEmail' => (string)($_SESSION['user_email'] ?? ''),
            'avatarUrl' => $avatarUrl,
            'isAdmin' => (string)($_SESSION['user_role_code'] ?? '') === 'admin',
            'status' => (string)$this->request->input('status', ''),
            'profile' => $profile,
        ]);
    }

    public function editProfile(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required&next=/account/edit');
            return;
        }

        $profile = User::getCustomerProfileByUserId((int)$_SESSION['user_id']);
        $avatarUrl = User::getResolvedAvatarByUserId((int)$_SESSION['user_id']);
        $_SESSION['user_avatar'] = $avatarUrl;

        $this->view('account/edit', [
            'title' => 'Cập nhật thông tin cá nhân',
            'userEmail' => (string)($_SESSION['user_email'] ?? ''),
            'avatarUrl' => $avatarUrl,
            'isAdmin' => (string)($_SESSION['user_role_code'] ?? '') === 'admin',
            'status' => (string)$this->request->input('status', ''),
            'profile' => $profile,
        ]);
    }

    public function updateAvatar(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required&next=/account/edit');
            return;
        }

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            SecurityLogger::event('avatar_upload_rejected', ['reason' => 'file-missing']);
            $this->response->redirect('/account/edit?status=avatar-empty');
            return;
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/avatars';
        $uploadResult = SafeUploadService::uploadImage($_FILES['avatar'], $uploadDir, 2 * 1024 * 1024);
        if (($uploadResult['ok'] ?? false) !== true) {
            $reason = (string)($uploadResult['error'] ?? 'upload-failed');
            SecurityLogger::event('avatar_upload_rejected', [
                'reason' => $reason,
                'user_id' => (int)($_SESSION['user_id'] ?? 0),
            ]);

            if ($reason === 'size-invalid') {
                $this->response->redirect('/account/edit?status=avatar-too-large');
                return;
            }

            $this->response->redirect('/account/edit?status=avatar-invalid');
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $fileName = (string)($uploadResult['filename'] ?? '');
        if ($fileName === '') {
            SecurityLogger::event('avatar_upload_rejected', [
                'reason' => 'filename-empty',
                'user_id' => $userId,
            ]);
            $this->response->redirect('/account/edit?status=avatar-failed');
            return;
        }

        $avatarUrl = '/uploads/avatars/' . $fileName;

        try {
            User::updateAvatarByUserId($userId, $avatarUrl);
            $_SESSION['user_avatar'] = $avatarUrl;
            $this->response->redirect('/account/edit?status=avatar-updated');
        } catch (\Throwable $e) {
            SecurityLogger::event('avatar_upload_rejected', [
                'reason' => 'db-update-failed',
                'user_id' => $userId,
            ]);
            $this->response->redirect('/account/edit?status=avatar-failed');
        }
    }

    public function updateProfile(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required&next=/account/edit');
            return;
        }

        $profile = [
            'full_name' => trim((string)$this->request->input('full_name', '')),
            'phone' => trim((string)$this->request->input('phone', '')),
            'address_line' => trim((string)$this->request->input('address_line', '')),
            'ward' => trim((string)$this->request->input('ward', '')),
            'district' => trim((string)$this->request->input('district', '')),
            'city' => trim((string)$this->request->input('city', '')),
            'full_address' => trim((string)$this->request->input('full_address', '')),
        ];

        if ($profile['full_address'] === '') {
            $profile['full_address'] = $this->composeFullAddress($profile);
        }

        if (!$this->isValidCustomerProfile($profile)) {
            $this->response->redirect('/account/edit?status=profile-invalid');
            return;
        }

        try {
            User::upsertCustomerProfile((int)$_SESSION['user_id'], $profile);
            $_SESSION['order_form'] = $profile;
            $this->response->redirect('/account?status=profile-updated');
        } catch (\Throwable $e) {
            $this->response->redirect('/account/edit?status=profile-failed');
        }
    }

    public function history(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required');
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $orders = [];

        try {
            $orders = Order::listDetailedByUser($userId);
        } catch (\Throwable $e) {
            $orders = [];
        }

        $this->view('account/history', [
            'title' => 'Lịch sử mua hàng',
            'orders' => $orders,
            'status' => (string)$this->request->input('status', ''),
        ]);
    }

    public function reviewHistory(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required&next=/reviews/history');
            return;
        }

        $reviews = [];
        try {
            $reviews = Review::listByUser((int)$_SESSION['user_id']);
        } catch (\Throwable $e) {
            $reviews = [];
        }

        $this->view('account/reviews', [
            'title' => 'Lịch sử đánh giá',
            'reviews' => $reviews,
        ]);
    }

    public function cancelOrder(): void
    {
        $this->ensureSession();

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=auth-required');
            return;
        }

        $orderId = max(0, (int)$this->request->input('order_id', 0));
        if ($orderId <= 0) {
            $this->response->redirect('/orders/history?status=order-invalid');
            return;
        }

        try {
            $result = Order::userCancel($orderId, (int)$_SESSION['user_id']);
            
            if ((bool)($result['is_bank_transfer'] ?? false)) {
                $this->response->redirect('/orders/history?status=cancelled-bank');
            } else {
                $this->response->redirect('/orders/history?status=cancelled-success');
            }
        } catch (\Throwable $e) {
            $errorMsg = (string)$e->getMessage();
            $status = match ($errorMsg) {
                'order:not-found' => 'order-not-found',
                'order:not-yours' => 'order-not-yours',
                'order:cannot-cancel' => 'order-cannot-cancel',
                'order:timeout' => 'order-timeout',
                default => 'cancel-failed',
            };
            $this->response->redirect('/orders/history?status=' . $status);
        }
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isValidCustomerProfile(array $profile): bool
    {
        foreach (['full_name', 'phone', 'address_line', 'ward', 'district', 'city'] as $required) {
            if (($profile[$required] ?? '') === '') {
                return false;
            }
        }

        $digits = preg_replace('/\D+/', '', (string)$profile['phone']);
        return strlen((string)$digits) >= 9;
    }

    private function composeFullAddress(array $profile): string
    {
        $parts = array_filter([
            (string)($profile['address_line'] ?? ''),
            (string)($profile['ward'] ?? ''),
            (string)($profile['district'] ?? ''),
            (string)($profile['city'] ?? ''),
        ], static fn($v) => trim($v) !== '');

        return implode(', ', $parts);
    }
}
