<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Product;

final class AdminPostController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'status' => trim((string)$this->request->input('status', '')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));
        $result = Post::adminList($filters, $page, 12);

        $this->view('admin/posts/index', [
            'title' => 'Quản lý bài viết',
            'rows' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'status' => trim((string)$this->request->input('status_message', '')),
            'tableReady' => Post::isAvailable(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->ensureSession();

        $this->view('admin/posts/create', [
            'title' => 'Thêm bài viết',
            'old' => $_SESSION['post_old'] ?? [],
            'error' => (string)($_SESSION['post_error'] ?? ''),
            'tableReady' => Post::isAvailable(),
            'relatedReady' => Post::relatedFeatureReady(),
            'products' => Product::allForPostRelation(),
            'selectedRelatedProductIds' => array_map('intval', (array)($_SESSION['post_old']['related_product_ids'] ?? [])),
        ], 'layouts/admin');

        unset($_SESSION['post_old'], $_SESSION['post_error']);
    }

    public function store(): void
    {
        $this->ensureSession();

        if (!Post::isAvailable()) {
            $_SESSION['post_error'] = 'Bảng posts chưa tồn tại. Hãy chạy migration tạo bảng bài viết.';
            $this->response->redirect('/admin/posts/create');
            return;
        }

        try {
            $payload = $this->payload();
            $payload['cover_image'] = $this->processImageUpload('cover_image_file', true) ?? '';
            $postId = Post::create($payload);
            Post::syncRelatedProducts($postId, (array)$this->request->input('related_product_ids', []));
            $this->response->redirect('/admin/posts?status_message=created');
        } catch (\Throwable $e) {
            $_SESSION['post_old'] = $_POST;
            $_SESSION['post_error'] = $this->friendlyError($e);
            $this->response->redirect('/admin/posts/create');
        }
    }

    public function edit(string $id): void
    {
        $this->ensureSession();

        $row = Post::find((int)$id);
        if (!$row) {
            $this->response->redirect('/admin/posts?status_message=not-found');
            return;
        }

        $this->view('admin/posts/edit', [
            'title' => 'Sửa bài viết',
            'row' => $row,
            'error' => (string)($_SESSION['post_error'] ?? ''),
            'tableReady' => Post::isAvailable(),
            'relatedReady' => Post::relatedFeatureReady(),
            'products' => Product::allForPostRelation(),
            'selectedRelatedProductIds' => Post::relatedProductIds((int)$row['id']),
        ], 'layouts/admin');

        unset($_SESSION['post_error']);
    }

    public function update(string $id): void
    {
        $this->ensureSession();

        $postId = (int)$id;
        $current = Post::find($postId);
        if (!$current) {
            $this->response->redirect('/admin/posts?status_message=not-found');
            return;
        }

        try {
            $payload = $this->payload($postId);
            $newImage = $this->processImageUpload('cover_image_file', false);
            $payload['cover_image'] = $newImage ?? (string)($current['cover_image'] ?? '');
            Post::update($postId, $payload);
            Post::syncRelatedProducts($postId, (array)$this->request->input('related_product_ids', []));
            $this->response->redirect('/admin/posts?status_message=updated');
        } catch (\Throwable $e) {
            $_SESSION['post_error'] = $this->friendlyError($e);
            $this->response->redirect('/admin/posts/' . $postId . '/edit');
        }
    }

    public function destroy(string $id): void
    {
        $ok = Post::delete((int)$id);
        $this->response->redirect('/admin/posts?status_message=' . ($ok ? 'deleted' : 'not-found'));
    }

    public function toggle(string $id): void
    {
        $ok = Post::toggleVisibility((int)$id);
        $this->response->redirect('/admin/posts?status_message=' . ($ok ? 'toggled' : 'not-found'));
    }

    private function payload(int $exceptId = 0): array
    {
        $title = trim((string)$this->request->input('title', ''));
        $slugInput = trim((string)$this->request->input('slug', ''));
        $excerpt = trim((string)$this->request->input('excerpt', ''));
        $content = trim((string)$this->request->input('content', ''));
        $status = trim((string)$this->request->input('status', 'draft'));
        $publishedAtInput = trim((string)$this->request->input('published_at', ''));

        if ($title === '') {
            throw new \InvalidArgumentException('Tiêu đề bài viết không được để trống.');
        }

        if ($content === '') {
            throw new \InvalidArgumentException('Nội dung bài viết không được để trống.');
        }

        if (!in_array($status, ['draft', 'published', 'hidden'], true)) {
            throw new \InvalidArgumentException('Trạng thái bài viết không hợp lệ.');
        }

        $slug = $this->slugify($slugInput !== '' ? $slugInput : $title);
        if ($slug === '') {
            $slug = 'bai-viet';
        }

        $slug = $this->uniqueSlug($slug, $exceptId);

        $publishedAt = null;
        if ($status === 'published') {
            if ($publishedAtInput !== '') {
                $ts = strtotime($publishedAtInput);
                if ($ts === false) {
                    throw new \InvalidArgumentException('Ngày đăng không hợp lệ.');
                }
                $publishedAt = date('Y-m-d H:i:sP', $ts);
            } else {
                $publishedAt = date('Y-m-d H:i:sP');
            }
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'cover_image' => '',
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    private function processImageUpload(string $field, bool $required = false): ?string
    {
        if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new \InvalidArgumentException('Vui lòng chọn ảnh bìa cho bài viết.');
            }
            return null;
        }

        $file = $_FILES[$field];
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload ảnh bìa thất bại.');
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        $originalName = (string)($file['name'] ?? '');

        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException('Định dạng ảnh không hợp lệ. Chỉ hỗ trợ jpg, jpeg, png, webp.');
        }

        $targetDir = dirname(__DIR__, 2) . '/public/uploads/posts';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \InvalidArgumentException('Không tạo được thư mục lưu ảnh bài viết.');
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw new \InvalidArgumentException('Không thể lưu ảnh bìa đã tải lên.');
        }

        return '/uploads/posts/' . $filename;
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }

    private function uniqueSlug(string $baseSlug, int $exceptId = 0): string
    {
        if (!Post::slugExists($baseSlug, $exceptId)) {
            return $baseSlug;
        }

        $index = 2;
        do {
            $candidate = $baseSlug . '-' . $index;
            $index++;
        } while (Post::slugExists($candidate, $exceptId));

        return $candidate;
    }

    private function friendlyError(\Throwable $e): string
    {
        if ($e instanceof \InvalidArgumentException) {
            return $e->getMessage();
        }

        if ($e instanceof \PDOException && $e->getCode() === '23505') {
            return 'Slug bài viết đã tồn tại. Vui lòng đổi slug khác.';
        }

        return 'Không thể lưu bài viết. Vui lòng thử lại.';
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
