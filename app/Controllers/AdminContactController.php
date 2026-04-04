<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contact;

final class AdminContactController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string)$this->request->input('q', '')),
            'handled' => trim((string)$this->request->input('handled', '')),
        ];
        $page = max(1, (int)$this->request->input('page', 1));

        $result = Contact::adminList($filters, $page, 15);

        $this->view('admin/contacts/index', [
            'title' => 'Quản lý liên hệ',
            'rows' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'status' => trim((string)$this->request->input('status', '')),
        ], 'layouts/admin');
    }

    public function handled(string $id): void
    {
        $ok = Contact::markHandled((int)$id);
        $this->response->redirect('/admin/contacts?status=' . ($ok ? 'handled' : 'not-found'));
    }

    public function destroy(string $id): void
    {
        $ok = Contact::delete((int)$id);
        $this->response->redirect('/admin/contacts?status=' . ($ok ? 'deleted' : 'not-found'));
    }
}
