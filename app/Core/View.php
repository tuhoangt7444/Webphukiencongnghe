<?php
namespace App\Core;
 class View 
 {
    # escape text an toàn cho HTML
    public static function e(?string $value): string 
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function eAttr(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function eUrl(?string $value): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\s*javascript:/i', $raw)) {
            return '';
        }

        return htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

        ob_start();
        require $layoutFile;
        $content = (string)ob_get_clean();

        echo self::injectCsrfTokenIntoPostForms($content);
    }
    # tạo hidden input chứa CSRF token
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