<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminCategory;

final class AdminCategoryController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'sort' => trim((string)$this->request->input('sort', 'created_at')),
            'direction' => trim((string)$this->request->input('direction', 'desc')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));
        $result = AdminCategory::list($filters, $page, 10);

        $this->view('admin/categories/index', [
            'title' => 'Quản lý danh mục',
            'rows' => $result['rows'],
            'filters' => $filters,
            'pagination' => $result['pagination'],
            'status' => (string)$this->request->input('status', ''),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->ensureSession();
        $this->view('admin/categories/create', [
            'title' => 'Thêm danh mục',
            'old' => $_SESSION['old'] ?? [],
            'error' => $_SESSION['error'] ?? '',
        ], 'layouts/admin');
        unset($_SESSION['old'], $_SESSION['error']);
    }

    public function store(): void
    {
        $this->ensureSession();
        $data = $this->payload();
        $name = $data['name'];
        $slug = $data['slug'];

        if ($name === '') {
            $_SESSION['error'] = 'Tên danh mục không được để trống.';
            $_SESSION['old'] = $_POST;
            $this->response->redirect('/admin/categories/create');
            return;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        $data['slug'] = $slug;

        try {
            AdminCategory::create($data);
            $this->response->redirect('/admin/categories?status=created');
        } catch (\PDOException $e) {
            $_SESSION['error'] = $e->getCode() === '23505' ? 'Slug danh mục đã tồn tại.' : ('Không thể thêm danh mục: ' . $e->getMessage());
            $_SESSION['old'] = $_POST;
            $this->response->redirect('/admin/categories/create');
        }
    }

    public function edit(string $id): void
    {
        $this->ensureSession();
        $row = AdminCategory::find((int)$id);
        if (!$row) {
            $this->response->redirect('/admin/categories?status=not-found');
            return;
        }

        $this->view('admin/categories/edit', [
            'title' => 'Sửa danh mục',
            'row' => $row,
            'error' => $_SESSION['error'] ?? '',
        ], 'layouts/admin');
        unset($_SESSION['error']);
    }

    public function update(string $id): void
    {
        $this->ensureSession();
        $categoryId = (int)$id;
        $data = $this->payload();
        $name = $data['name'];
        $slug = $data['slug'];

        if ($name === '') {
            $_SESSION['error'] = 'Tên danh mục không được để trống.';
            $this->response->redirect('/admin/categories/' . $categoryId . '/edit');
            return;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        $data['slug'] = $slug;

        try {
            AdminCategory::update($categoryId, $data);
            $this->response->redirect('/admin/categories?status=updated');
        } catch (\PDOException $e) {
            $_SESSION['error'] = $e->getCode() === '23505' ? 'Slug danh mục đã tồn tại.' : ('Không thể cập nhật danh mục: ' . $e->getMessage());
            $this->response->redirect('/admin/categories/' . $categoryId . '/edit');
        }
    }

    public function destroy(string $id): void
    {
        $deleted = AdminCategory::delete((int)$id);
        $this->response->redirect('/admin/categories?status=' . ($deleted ? 'deleted' : 'has-products'));
    }

    private function payload(): array
    {
        $status = trim((string)$this->request->input('status', 'active'));
        if (!in_array($status, ['active', 'hidden'], true)) {
            $status = 'active';
        }

        return [
            'name' => trim((string)$this->request->input('name', '')),
            'slug' => trim((string)$this->request->input('slug', '')),
            'icon' => trim((string)$this->request->input('icon', 'fa-folder-tree')),
            'description' => trim((string)$this->request->input('description', '')),
            'status' => $status,
        ];
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function slugify(string $value): string
    {
        $slug = mb_strtolower($value, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if (is_string($ascii) && $ascii !== '') {
            $slug = mb_strtolower($ascii, 'UTF-8');
        }
        $slug = str_replace(['đ', 'Đ'], 'd', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', trim((string)$slug));
        $slug = preg_replace('/-+/', '-', $slug);

        return $slug ?: 'danh-muc';
    }
}
