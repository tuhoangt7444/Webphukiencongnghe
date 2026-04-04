<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\AdminDashboard;

class AdminController extends Controller {
    public function index(): void {
        $this->dashboard();
    }

    public function dashboard(): void {
        $data = AdminDashboard::getData();
        $this->view('admin/dashboard', [
            'title' => 'Dashboard quản trị',
            'stats' => $data['stats'],
            'time_stats' => $data['time_stats'],
            'chart' => $data['chart'],
            'top_products' => $data['top_products'],
            'recent_orders' => $data['recent_orders'],
            'low_stock_products' => $data['low_stock_products'],
        ], 'layouts/admin');
    }
}