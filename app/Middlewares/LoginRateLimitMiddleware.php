<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Models\RateLimit;
use App\Services\SecurityLogger;

final class LoginRateLimitMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $login = strtolower(trim((string)$request->input('login', '')));
        $perIpKey = 'login-ip:' . sha1($ip);
        $perAccountKey = 'login:' . sha1($ip . '|' . $login);

        $ipResult = RateLimit::hit($perIpKey, 5, 300, 900);
        $accountResult = RateLimit::hit($perAccountKey, 5, 300, 900);

        $result = ($ipResult['allowed'] ?? false) === true ? $accountResult : $ipResult;

        if (($result['allowed'] ?? false) !== true) {
            $retryAfter = max(1, (int)($result['retry_after'] ?? 60));
            SecurityLogger::event('login_rate_limited', [
                'login' => $login,
                'retry_after' => $retryAfter,
                'ip' => $ip,
            ]);

            header('Retry-After: ' . $retryAfter);
            header('Location: /login?status=login-rate-limited&retry_after=' . $retryAfter);
            return null;
        }

        return $next();
    }
}
