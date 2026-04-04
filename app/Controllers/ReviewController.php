<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(): void
    {
        $this->ensureSession();

        $productId = (int)$this->request->input('product_id', 0);
        $rating = (int)$this->request->input('rating', 0);
        $comment = trim((string)$this->request->input('comment', ''));
        $redirectTo = $this->sanitizeRedirect((string)$this->request->input('redirect_to', '/orders/history'));

        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login?status=review-login-required&next=' . urlencode($redirectTo));
            return;
        }

        if ($productId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
            $this->response->redirect($this->appendStatus($redirectTo, 'invalid'));
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        if (!Review::canUserReviewProduct($userId, $productId)) {
            $this->response->redirect($this->appendStatus($redirectTo, 'not-eligible'));
            return;
        }

        if (Review::findByUserAndProduct($userId, $productId)) {
            $this->response->redirect($this->appendStatus($redirectTo, 'already-reviewed'));
            return;
        }

        try {
            Review::createByCustomer($userId, $productId, $rating, $comment);
            $this->response->redirect($this->appendStatus($redirectTo, 'submitted'));
        } catch (\Throwable $e) {
            $this->response->redirect($this->appendStatus($redirectTo, 'failed'));
        }
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function sanitizeRedirect(string $redirectTo): string
    {
        $redirectTo = trim($redirectTo);
        if ($redirectTo === '' || !str_starts_with($redirectTo, '/')) {
            return '/orders/history';
        }

        return $redirectTo;
    }

    private function appendStatus(string $redirectTo, string $status): string
    {
        $redirectBase = preg_replace('/([?&])(review_status|tab)=[^&]*(&|$)/', '$1', $redirectTo) ?? $redirectTo;
        $redirectBase = rtrim((string)$redirectBase, '?&');
        $separator = str_contains($redirectBase, '?') ? '&' : '?';

        return $redirectBase . $separator . 'tab=review&review_status=' . urlencode($status);
    }
}