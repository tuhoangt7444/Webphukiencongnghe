<?php
namespace App\Core\Security;

final class SecureSession
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
            || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', '7200');

        session_name('TECHGEARSESSID');
        session_start();

        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        }

        if (!isset($_SESSION['_last_regenerated_at'])) {
            $_SESSION['_last_regenerated_at'] = time();
        }

        if (time() - (int)$_SESSION['_last_regenerated_at'] > 900) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated_at'] = time();
        }
    }

    public static function regenerateOnLogin(): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['_last_regenerated_at'] = time();
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();
    }
}
