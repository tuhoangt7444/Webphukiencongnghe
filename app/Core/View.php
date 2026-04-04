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
        extract($data, EXTR_SKIP);
        $base = dirname(__DIR__);
        
        $viewFile = $base . '/Views/' . $view . '.php';
        $layoutFile = $base . '/Views/' . $layout . '.php';

        if(!file_exists($viewFile)) {
            die("Không tìm thấy view: " . $viewFile);
        }
        if(!file_exists($layoutFile)) {
            die("Không tìm thấy layout: " . $layoutFile);
        }

        // Tạm thời comment dòng này sau khi thấy nó hiện chữ "layouts/admin"
        // echo "<!-- Debug: Đang dùng layout: $layout -->"; 

        ob_start();
        require $layoutFile;
        $content = (string)ob_get_clean();

        echo self::injectCsrfTokenIntoPostForms($content);
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

    private static function injectCsrfTokenIntoPostForms(string $html): string
    {
        return (string)preg_replace_callback(
            '/(<form\\b[^>]*\\bmethod\\s*=\\s*(?:"post"|\'post\'|post)[^>]*>)(.*?<\\/form>)/is',
            function (array $m): string {
                $openTag = $m[1];
                $bodyAndClose = $m[2];

                if (
                    stripos($bodyAndClose, 'name="csrf_token"') !== false
                    || stripos($bodyAndClose, "name='csrf_token'") !== false
                ) {
                    return $openTag . $bodyAndClose;
                }

                return $openTag . "\n" . self::csrf_field() . $bodyAndClose;
            },
            $html
        );
    }
 }