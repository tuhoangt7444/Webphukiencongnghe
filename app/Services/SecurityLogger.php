<?php
namespace App\Services;

final class SecurityLogger
{
    public static function event(string $type, array $context = []): void
    {
        $record = [
            'ts' => date('c'),
            'type' => $type,
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'ua' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'context' => $context,
        ];

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        error_log($line . PHP_EOL, 3, dirname(__DIR__, 2) . '/storage/logs/security.log');
    }
}
