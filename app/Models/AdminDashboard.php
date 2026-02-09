<?php
namespace App\Models;
use App\Core\DB;

class AdminDashboard {
    public static function getStats() {
        $pdo = DB::conn();
        return [
            'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
            'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'low_stock' => $pdo->query("SELECT COUNT(*) FROM product_variants WHERE stock < 10")->fetchColumn(),
            'total_revenue' => $pdo->query("SELECT SUM(total) FROM orders WHERE status = 'done'")->fetchColumn() ?? 0
        ];
    }
    public static function getData() {
        $pdo = DB::conn();
        return [
            'stats' => [
                'revenue'   => $pdo->query("SELECT SUM(total) FROM orders WHERE status = 'done'")->fetchColumn() ?: 0,
                'orders'    => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
                'customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE code='customer')")->fetchColumn(),
                'low_stock' => $pdo->query("SELECT COUNT(*) FROM product_variants WHERE stock < 5")->fetchColumn()
            ],
            'recent_orders' => $pdo->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll()
        ];
    }
}