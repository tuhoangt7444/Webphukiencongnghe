<?php
namespace App\Models;

use App\Core\DB;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

final class RateLimit
{
    private static bool $tableEnsured = false;

    public static function hit(string $key, int $maxAttempts, int $windowSeconds, int $blockSeconds): array
    {
        self::ensureTable();

        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

            $st = $pdo->prepare('SELECT key, hits, window_start, blocked_until FROM rate_limits WHERE key = :key FOR UPDATE');
            $st->execute(['key' => $key]);
            $row = $st->fetch();

            if (!$row) {
                $insert = $pdo->prepare(
                    'INSERT INTO rate_limits (key, hits, window_start, blocked_until, updated_at)
                     VALUES (:key, 1, :window_start, NULL, now())'
                );
                $insert->execute([
                    'key' => $key,
                    'window_start' => $now->format('Y-m-d H:i:sP'),
                ]);
                $pdo->commit();

                return ['allowed' => true, 'remaining' => max(0, $maxAttempts - 1), 'retry_after' => 0];
            }

            $blockedUntil = $row['blocked_until'] ? new DateTimeImmutable((string)$row['blocked_until']) : null;
            if ($blockedUntil && $blockedUntil > $now) {
                $retryAfter = $blockedUntil->getTimestamp() - $now->getTimestamp();
                $pdo->commit();
                return ['allowed' => false, 'remaining' => 0, 'retry_after' => max(1, $retryAfter)];
            }

            $windowStart = new DateTimeImmutable((string)$row['window_start']);
            $windowEnd = $windowStart->add(new DateInterval('PT' . $windowSeconds . 'S'));
            $hits = (int)($row['hits'] ?? 0);

            if ($now >= $windowEnd) {
                $hits = 0;
                $windowStart = $now;
            }

            $hits++;
            $newBlockedUntil = null;
            $allowed = true;

            if ($hits > $maxAttempts) {
                $allowed = false;
                $newBlockedUntil = $now->add(new DateInterval('PT' . $blockSeconds . 'S'));
            }

            $up = $pdo->prepare(
                'UPDATE rate_limits
                 SET hits = :hits,
                     window_start = :window_start,
                     blocked_until = :blocked_until,
                     updated_at = now()
                 WHERE key = :key'
            );
            $up->execute([
                'key' => $key,
                'hits' => $hits,
                'window_start' => $windowStart->format('Y-m-d H:i:sP'),
                'blocked_until' => $newBlockedUntil ? $newBlockedUntil->format('Y-m-d H:i:sP') : null,
            ]);

            $pdo->commit();

            if (!$allowed) {
                return ['allowed' => false, 'remaining' => 0, 'retry_after' => $blockSeconds];
            }

            return ['allowed' => true, 'remaining' => max(0, $maxAttempts - $hits), 'retry_after' => 0];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
        }
    }

    private static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        $pdo = DB::conn();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS rate_limits (
                key TEXT PRIMARY KEY,
                hits INT NOT NULL DEFAULT 0,
                window_start TIMESTAMPTZ NOT NULL,
                blocked_until TIMESTAMPTZ NULL,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )'
        );

        self::$tableEnsured = true;
    }
}
