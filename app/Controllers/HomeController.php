<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', ['title' => 'Trang chủ']);
    }
    public function ping(): void
    {
        $this->response->json([
            'ok' => true,
            'time' => date('c')
        ]);
    }
    public function go(): void
    {
        $this->response->redirect('/products');
    }
    public function fakeLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = 1;
        $this->response->redirect('/admin');
    }
    public function dbTest(): void
    {
        try {
            $pdo = DB::conn();
            $row = $pdo->query("SELECT 1 AS ok")->fetch();

            $this->response->json([
                'db' => true,
                'ok' => (int)($row['ok'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            $this->response->json([
                'db' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
