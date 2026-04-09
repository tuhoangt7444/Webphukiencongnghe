<?php
namespace App\Middlewares;
use App\Core\MiddlewareInterface;
use App\Core\Request;
class AuthMiddleware implements MiddlewareInterface
{
    # chặn truy cập khi chưa đăng nhập
    public function handle(Request $request, callable $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?status=auth-required');
            return null;
        }
        return $next();
    }
 }