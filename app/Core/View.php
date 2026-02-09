<?php
namespace App\Core;
 class View 
 {
    # Hàm escape để tránh XSS
    public static function e(?string $value): string 
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        # Kết xuất view chính
        extract($data, EXTR_SKIP);
        $base = dirname(__DIR__);
        $viewFile = $base . '/Views/' . $view . '.php';
        $layoutFile = $base . '/Views/' . $layout . '.php';

        if(!file_exists($viewFile)) {
            http_response_code(500);
            echo "không tìm thấy view: " . $view;
            return;
        }
        if(!file_exists($layoutFile)) {
            http_response_code(500);
            echo "không tìm thấy layout: " . $layout;
            return;
        }

        require $layoutFile;
    }
    # Hàm tạo trường CSRF
    public static function csrf_field(): string 
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
 }