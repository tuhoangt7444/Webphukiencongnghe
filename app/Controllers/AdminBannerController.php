<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Banner;

final class AdminBannerController extends Controller
{
    private const POSITION_DIMENSIONS = [
        'home_slider' => ['w' => 1920, 'h' => 600],
        'category_banner' => ['w' => 1200, 'h' => 300],
        'promo_banner' => ['w' => 1200, 'h' => 300],
        'sidebar_banner' => ['w' => 400, 'h' => 600],
    ];

    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'position' => trim((string)$this->request->input('position', '')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));
        $result = Banner::list($filters, $page, 12);

        $this->view('admin/banners/index', [
            'title' => 'Quản lý banner',
            'rows' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'status' => trim((string)$this->request->input('status', '')),
            'dimensions' => self::POSITION_DIMENSIONS,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->ensureSession();

        $this->view('admin/banners/create', [
            'title' => 'Thêm banner',
            'old' => $_SESSION['banner_old'] ?? [],
            'error' => (string)($_SESSION['banner_error'] ?? ''),
            'dimensions' => self::POSITION_DIMENSIONS,
        ], 'layouts/admin');

        unset($_SESSION['banner_old'], $_SESSION['banner_error']);
    }

    public function store(): void
    {
        $this->ensureSession();

        try {
            $payload = $this->payload();
            $payload['image'] = $this->processImageUpload('image', $payload['position']);
            Banner::create($payload);

            $this->response->redirect('/admin/banners?status=created');
        } catch (\Throwable $e) {
            $_SESSION['banner_old'] = $_POST;
            $_SESSION['banner_error'] = $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'Không thể thêm banner. Vui lòng thử lại.';
            $this->response->redirect('/admin/banners/create');
        }
    }

    public function edit(string $id): void
    {
        $this->ensureSession();

        $row = Banner::find((int)$id);
        if (!$row) {
            $this->response->redirect('/admin/banners?status=not-found');
            return;
        }

        $this->view('admin/banners/edit', [
            'title' => 'Sửa banner',
            'row' => $row,
            'error' => (string)($_SESSION['banner_error'] ?? ''),
            'dimensions' => self::POSITION_DIMENSIONS,
        ], 'layouts/admin');

        unset($_SESSION['banner_error']);
    }

    public function update(string $id): void
    {
        $this->ensureSession();

        $bannerId = (int)$id;
        $current = Banner::find($bannerId);
        if (!$current) {
            $this->response->redirect('/admin/banners?status=not-found');
            return;
        }

        try {
            $payload = $this->payload();
            $newImage = $this->processImageUpload('image', $payload['position'], false);
            $payload['image'] = $newImage ?? (string)$current['image'];

            Banner::update($bannerId, $payload);
            $this->response->redirect('/admin/banners?status=updated');
        } catch (\Throwable $e) {
            $_SESSION['banner_error'] = $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'Không thể cập nhật banner. Vui lòng thử lại.';
            $this->response->redirect('/admin/banners/' . $bannerId . '/edit');
        }
    }

    public function toggle(string $id): void
    {
        $ok = Banner::toggle((int)$id);
        $this->response->redirect('/admin/banners?status=' . ($ok ? 'toggled' : 'not-found'));
    }

    public function destroy(string $id): void
    {
        $ok = Banner::delete((int)$id);
        $this->response->redirect('/admin/banners?status=' . ($ok ? 'deleted' : 'not-found'));
    }

    private function payload(): array
    {
        $title = trim((string)$this->request->input('title', ''));
        $link = trim((string)$this->request->input('link', ''));
        $position = trim((string)$this->request->input('position', 'home_slider'));
        $status = trim((string)$this->request->input('status', 'active'));

        if ($title === '') {
            throw new \InvalidArgumentException('Tiêu đề banner không được để trống.');
        }

        if (!array_key_exists($position, self::POSITION_DIMENSIONS)) {
            throw new \InvalidArgumentException('Vị trí banner không hợp lệ.');
        }

        if (!in_array($status, ['active', 'hidden'], true)) {
            throw new \InvalidArgumentException('Trạng thái banner không hợp lệ.');
        }

        if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL) && !str_starts_with($link, '/')) {
            throw new \InvalidArgumentException('Link banner phải là URL hợp lệ hoặc đường dẫn bắt đầu bằng /.');
        }

        return [
            'title' => $title,
            'link' => $link,
            'position' => $position,
            'status' => $status,
        ];
    }

    private function processImageUpload(string $field, string $position, bool $required = true): ?string
    {
        if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new \InvalidArgumentException('Vui lòng chọn ảnh banner.');
            }
            return null;
        }

        $file = $_FILES[$field];
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload ảnh banner thất bại.');
        }

        $tmpPath = (string)$file['tmp_name'];
        $originalName = (string)$file['name'];

        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException('Định dạng ảnh không hợp lệ. Chỉ hỗ trợ jpg, png, webp.');
        }

        $size = @getimagesize($tmpPath);
        if (!$size) {
            throw new \InvalidArgumentException('Không đọc được thông tin ảnh.');
        }

        if (!function_exists('imagecreatetruecolor')) {
            throw new \InvalidArgumentException('Máy chủ chưa bật thư viện GD để resize ảnh.');
        }

        $sourceImage = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($tmpPath),
            'png' => @imagecreatefrompng($tmpPath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false,
            default => false,
        };

        if (!$sourceImage) {
            throw new \InvalidArgumentException('Không thể xử lý ảnh tải lên.');
        }

        $target = self::POSITION_DIMENSIONS[$position];
        $targetW = (int)$target['w'];
        $targetH = (int)$target['h'];

        $srcW = (int)$size[0];
        $srcH = (int)$size[1];

        # Resize and crop to exact banner dimensions while keeping the subject centered.
        $scale = max($targetW / max(1, $srcW), $targetH / max(1, $srcH));
        $resizeW = max(1, (int)ceil($srcW * $scale));
        $resizeH = max(1, (int)ceil($srcH * $scale));

        $resized = imagecreatetruecolor($targetW, $targetH);
        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $targetW, $targetH, $transparent);
        } else {
            $background = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $targetW, $targetH, $background);
        }

        $dstX = (int)floor(($targetW - $resizeW) / 2);
        $dstY = (int)floor(($targetH - $resizeH) / 2);
        imagecopyresampled($resized, $sourceImage, $dstX, $dstY, 0, 0, $resizeW, $resizeH, $srcW, $srcH);

        $targetDir = dirname(__DIR__, 2) . '/public/uploads/banners';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            imagedestroy($sourceImage);
            imagedestroy($resized);
            throw new \InvalidArgumentException('Không tạo được thư mục lưu banner.');
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.webp';
        $targetPath = $targetDir . '/' . $filename;

        if (!function_exists('imagewebp')) {
            imagedestroy($sourceImage);
            imagedestroy($resized);
            throw new \InvalidArgumentException('Máy chủ chưa hỗ trợ nén ảnh WebP.');
        }

        $ok = imagewebp($resized, $targetPath, 82);
        imagedestroy($sourceImage);
        imagedestroy($resized);

        if (!$ok) {
            throw new \InvalidArgumentException('Không thể lưu ảnh banner đã tối ưu.');
        }

        return '/uploads/banners/' . $filename;
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
