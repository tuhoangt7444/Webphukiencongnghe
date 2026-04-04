<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Voucher;

final class AdminVoucherController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));
        $result = Voucher::adminList($filters, $page, 12);

        $this->view('admin/vouchers/index', [
            'title' => 'Quản lý phiếu giảm giá',
            'rows' => $result['rows'],
            'stats' => $result['stats'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'status' => trim((string)$this->request->input('status', '')),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->ensureSession();

        $this->view('admin/vouchers/create', [
            'title' => 'Tạo phiếu giảm giá',
            'old' => $_SESSION['voucher_old'] ?? [],
            'error' => (string)($_SESSION['voucher_error'] ?? ''),
            'categories' => Voucher::categoriesForVoucher(),
            'customerTypeOptions' => Voucher::customerTypeOptions(),
        ], 'layouts/admin');

        unset($_SESSION['voucher_old'], $_SESSION['voucher_error']);
    }

    public function store(): void
    {
        $this->ensureSession();

        try {
            $payload = Voucher::normalizePayload([
                'name' => $this->request->input('name', ''),
                'code' => $this->request->input('code', ''),
                'discount_amount' => $this->request->input('discount_amount', 0),
                'start_date' => $this->request->input('start_date', ''),
                'end_date' => $this->request->input('end_date', ''),
                'quantity' => $this->request->input('quantity', 0),
                'status' => $this->request->input('status', 'active'),
                'apply_category_id' => $this->request->input('apply_category_id', 0),
                'customer_type' => $this->request->input('customer_type', 'all'),
            ]);

            Voucher::create($payload);
            $this->response->redirect('/admin/vouchers?status=created');
        } catch (\Throwable $e) {
            $_SESSION['voucher_old'] = $_POST;
            $_SESSION['voucher_error'] = $this->friendlyError($e);
            $this->response->redirect('/admin/vouchers/create');
        }
    }

    public function edit(string $id): void
    {
        $this->ensureSession();

        $row = Voucher::find((int)$id);
        if (!$row) {
            $this->response->redirect('/admin/vouchers?status=not-found');
            return;
        }

        $this->view('admin/vouchers/edit', [
            'title' => 'Chỉnh sửa phiếu giảm giá',
            'row' => $row,
            'error' => (string)($_SESSION['voucher_error'] ?? ''),
            'categories' => Voucher::categoriesForVoucher(),
            'customerTypeOptions' => Voucher::customerTypeOptions(),
        ], 'layouts/admin');

        unset($_SESSION['voucher_error']);
    }

    public function update(string $id): void
    {
        $this->ensureSession();

        $voucherId = (int)$id;
        $current = Voucher::find($voucherId);
        if (!$current) {
            $this->response->redirect('/admin/vouchers?status=not-found');
            return;
        }

        try {
            $payload = Voucher::normalizePayload([
                'name' => $this->request->input('name', ''),
                'code' => $this->request->input('code', ''),
                'discount_amount' => $this->request->input('discount_amount', 0),
                'start_date' => $this->request->input('start_date', ''),
                'end_date' => $this->request->input('end_date', ''),
                'quantity' => $this->request->input('quantity', 0),
                'status' => $this->request->input('status', 'active'),
                'apply_category_id' => $this->request->input('apply_category_id', 0),
                'customer_type' => $this->request->input('customer_type', 'all'),
            ]);

            Voucher::update($voucherId, $payload);
            $this->response->redirect('/admin/vouchers?status=updated');
        } catch (\Throwable $e) {
            $_SESSION['voucher_error'] = $this->friendlyError($e);
            $this->response->redirect('/admin/vouchers/' . $voucherId . '/edit');
        }
    }

    public function toggle(string $id): void
    {
        $voucherId = (int)$id;
        $result = Voucher::toggleStatus($voucherId);

        if ($result === 'not-found') {
            $this->response->redirect('/admin/vouchers?status=not-found');
            return;
        }

        if ($result === 'expired') {
            $this->response->redirect('/admin/vouchers?status=expired-lock');
            return;
        }

        $this->response->redirect('/admin/vouchers?status=toggled');
    }

    public function destroy(string $id): void
    {
        $voucherId = (int)$id;
        if (!Voucher::delete($voucherId)) {
            $this->response->redirect('/admin/vouchers?status=in-use');
            return;
        }

        $this->response->redirect('/admin/vouchers?status=deleted');
    }

    private function friendlyError(\Throwable $e): string
    {
        if ($e instanceof \InvalidArgumentException) {
            return $e->getMessage();
        }

        if ($e instanceof \PDOException && $e->getCode() === '23505') {
            return 'Mã phiếu giảm giá đã tồn tại.';
        }

        return 'Không thể xử lý phiếu giảm giá. Vui lòng thử lại.';
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
