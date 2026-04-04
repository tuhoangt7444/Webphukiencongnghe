<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsletterSubscriber;

final class AdminNewsletterController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'status' => trim((string)$this->request->input('status', 'active')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));

        $result = NewsletterSubscriber::adminList($filters, $page, 20);

        $this->view('admin/newsletters/index', [
            'title' => 'Nhận ưu đãi',
            'rows' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'status' => trim((string)$this->request->input('result', '')),
        ], 'layouts/admin');
    }

    public function destroy(string $id): void
    {
        $ok = NewsletterSubscriber::delete((int)$id);
        $this->response->redirect('/admin/newsletters?result=' . ($ok ? 'deleted' : 'not-found'));
    }
}
