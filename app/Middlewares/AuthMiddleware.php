<?php
namespace App\Middlewares;
use App\Core\MiddlewareInterface;
use App\Core\Request;
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        # kiem tra xem co mo session chua
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        #neu chua login
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?status=auth-required');
            return null;
        }
        return $next();
    }
 }