<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminInventory;
use App\Models\InventoryLog;

final class AdminInventoryController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'category_id' => (int)$this->request->input('category_id', 0),
            'stock_range' => trim((string)$this->request->input('stock_range', '')),
        ];

        $page = max(1, (int)$this->request->input('page', 1));
        $result = AdminInventory::list($filters, $page, 12);

        $this->view('admin/inventory/index', [
            'title' => 'Quản lý tồn kho',
            'rows' => $result['rows'],
            'stats' => $result['stats'],
            'lowStock' => $result['low_stock'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'categories' => AdminInventory::categories(),
            'status' => trim((string)$this->request->input('status', '')),
        ], 'layouts/admin');
    }

    public function import(string $productId): void
    {
        $id = (int)$productId;
        $product = AdminInventory::find($id);

        if (!$product) {
            $this->response->redirect('/admin/inventory?status=not-found');
            return;
        }

        $this->ensureSession();

        $this->view('admin/inventory/import', [
            'title' => 'Nhập thêm hàng',
            'product' => $product,
            'error' => (string)($_SESSION['inventory_error'] ?? ''),
            'old' => $_SESSION['inventory_old'] ?? [],
        ], 'layouts/admin');

        unset($_SESSION['inventory_error'], $_SESSION['inventory_old']);
    }

    public function storeImport(string $productId): void
    {
        $this->ensureSession();

        $id = (int)$productId;
        $quantity = (int)$this->request->input('quantity', 0);
        $note = trim((string)$this->request->input('note', ''));

        try {
            AdminInventory::importStock($id, $quantity, $note);
            $this->response->redirect('/admin/inventory?status=imported');
        } catch (\Throwable $e) {
            $_SESSION['inventory_old'] = $_POST;
            $_SESSION['inventory_error'] = $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'Không thể nhập kho. Vui lòng thử lại.';
            $this->response->redirect('/admin/inventory/import/' . $id);
        }
    }

    public function logs(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'type' => trim((string)$this->request->input('type', '')),
            'product_id' => (int)$this->request->input('product_id', 0),
        ];

        $page = max(1, (int)$this->request->input('page', 1));
        $result = InventoryLog::list($filters, $page, 20);

        $this->view('admin/inventory/logs', [
            'title' => 'Lịch sử nhập/xuất kho',
            'rows' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
        ], 'layouts/admin');
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
