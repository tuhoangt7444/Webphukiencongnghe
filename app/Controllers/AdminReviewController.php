<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Review;

final class AdminReviewController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'rating' => (int)$this->request->input('rating', 0),
            'status' => trim((string)$this->request->input('status', '')),
        ];

        $page = max(1, (int)$this->request->input('page', 1));
        $result = Review::adminList($filters, $page, 15);

        $this->view('admin/reviews/index', [
            'title' => 'Quản lý đánh giá sản phẩm',
            'rows' => $result['rows'],
            'stats' => $result['stats'],
            'avgByProduct' => $result['avg_by_product'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'statusMessage' => trim((string)$this->request->input('status_msg', '')),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $review = Review::find((int)$id);
        if (!$review) {
            $this->response->redirect('/admin/reviews?status_msg=not-found');
            return;
        }

        $this->view('admin/reviews/show', [
            'title' => 'Chi tiết đánh giá',
            'review' => $review,
            'statusMessage' => trim((string)$this->request->input('status_msg', '')),
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): void
    {
        $reviewId = (int)$id;
        $nextStatus = trim((string)$this->request->input('status', ''));

        $ok = Review::updateStatus($reviewId, $nextStatus);
        if (!$ok) {
            $this->response->redirect('/admin/reviews?status_msg=invalid');
            return;
        }

        $redirect = trim((string)$this->request->input('redirect', 'index'));
        if ($redirect === 'show') {
            $this->response->redirect('/admin/reviews/' . $reviewId . '?status_msg=updated');
            return;
        }

        $this->response->redirect('/admin/reviews?status_msg=updated');
    }

    public function destroy(string $id): void
    {
        $reviewId = (int)$id;
        $ok = Review::delete($reviewId);

        if (!$ok) {
            $this->response->redirect('/admin/reviews?status_msg=delete-denied');
            return;
        }

        $this->response->redirect('/admin/reviews?status_msg=deleted');
    }
}
