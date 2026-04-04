<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;

class AdminOrderController extends Controller
{
    private const STATUS_LABELS = [
        'pending_approval' => 'Chờ xác nhận',
        'approved' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'done' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'status' => trim((string)$this->request->input('status', '')),
            'date_from' => trim((string)$this->request->input('date_from', '')),
            'date_to' => trim((string)$this->request->input('date_to', '')),
            'payment_method' => trim((string)$this->request->input('payment_method', '')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));

        $result = Order::adminList($filters, $page, 12);

        $this->view('admin/orders/index', [
            'title' => 'Quản lý đơn hàng',
            'rows' => $result['rows'],
            'stats' => $result['stats'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'paymentMethods' => Order::adminPaymentMethods(),
            'statusOptions' => self::STATUS_LABELS,
            'flash' => (string)$this->request->input('status', ''),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $orderId = (int)$id;
        $order = Order::adminFind($orderId);
        if (!$order) {
            $this->response->redirect('/admin/orders?status=not-found');
            return;
        }

        $this->view('admin/orders/show', [
            'title' => 'Chi tiết đơn hàng',
            'order' => $order,
            'items' => Order::adminItems($orderId),
            'statusOptions' => self::STATUS_LABELS,
            'flash' => (string)$this->request->input('status', ''),
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): void
    {
        $this->ensureSession();

        $orderId = (int)$id;
        $newStatus = trim((string)$this->request->input('status', ''));
        $allowed = array_keys(self::STATUS_LABELS);
        if (!in_array($newStatus, $allowed, true)) {
            $this->response->redirect('/admin/orders/' . $orderId . '?status=invalid');
            return;
        }

        Order::adminUpdateStatus($orderId, $newStatus, (int)($_SESSION['user_id'] ?? 0));

        $redirect = trim((string)$this->request->input('redirect', 'show'));
        if ($redirect === 'index') {
            // Build query string to preserve filters
            $queryParams = [
                'q' => (string)$this->request->input('q', ''),
                'status' => (string)$this->request->input('status_filter', ''),
                'date_from' => (string)$this->request->input('date_from', ''),
                'date_to' => (string)$this->request->input('date_to', ''),
                'payment_method' => (string)$this->request->input('payment_method', ''),
                'page' => (string)$this->request->input('page', '1'),
                'msg' => 'updated'
            ];
            $queryString = http_build_query(array_filter($queryParams, static fn($value) => $value !== ''));
            $this->response->redirect('/admin/orders?' . $queryString);
            return;
        }

        $this->response->redirect('/admin/orders/' . $orderId . '?msg=updated');
    }

    public function cancel(string $id): void
    {
        $this->ensureSession();
        $orderId = (int)$id;
        $stockAction = trim((string)$this->request->input('stock_action', 'keep'));
        $restoreStock = $stockAction === 'restore';

        Order::adminCancel($orderId, (int)($_SESSION['user_id'] ?? 0), $restoreStock);

        $redirect = trim((string)$this->request->input('redirect', 'index'));
        if ($redirect === 'show') {
            $this->response->redirect('/admin/orders/' . $orderId . '?msg=cancelled');
            return;
        }

        // Build query string to preserve filters
        $queryParams = [
            'q' => (string)$this->request->input('q', ''),
            'status' => (string)$this->request->input('status', ''),
            'date_from' => (string)$this->request->input('date_from', ''),
            'date_to' => (string)$this->request->input('date_to', ''),
            'payment_method' => (string)$this->request->input('payment_method', ''),
            'page' => (string)$this->request->input('page', '1'),
            'msg' => 'cancelled'
        ];
        $queryString = http_build_query(array_filter($queryParams, static fn($value) => $value !== ''));
        $this->response->redirect('/admin/orders?' . $queryString);
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
