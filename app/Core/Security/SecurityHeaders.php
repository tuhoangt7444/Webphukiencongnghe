<?php
namespace App\Core\Security;

final class SecurityHeaders
{
    public static function apply(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        header('Content-Security-Policy: '
            . "default-src 'self'; "
            . "img-src 'self' data: blob: https:; "
            . "style-src 'self' 'unsafe-inline' https:; "
            . "font-src 'self' data: https:; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; "
            . "connect-src 'self' https: wss: ws:; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self';"
        );
    }
}
