<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\AdminDashboard;

class AdminController extends Controller {
    public function index(): void {
        $data = AdminDashboard::getData();
        $this->view('admin/dashboard', [
            'title' => 'Dashboard Overview',
            'stats' => $data['stats'],
            'recent_orders' => $data['recent_orders']
        ], 'layouts/admin');
    }
}