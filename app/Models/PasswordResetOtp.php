<?php
namespace App\Models;

use App\Core\DB;

final class PasswordResetOtp
{
    private const OTP_EXPIRE_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 45;

    public static function issueForEmail(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'email-invalid'];
        }

        self::ensureTable();

        $user = User::findByEmail($email);
        if (!$user) {
            // Keep response generic to avoid user enumeration.
            return ['ok' => true, 'noop' => true];
        }

        $latest = DB::conn()->prepare(
            "SELECT created_at
             FROM password_reset_otps
             WHERE email = :email
             ORDER BY id DESC
             LIMIT 1"
        );
        $latest->execute(['email' => $email]);
        $latestCreatedAt = $latest->fetchColumn();
        if (is_string($latestCreatedAt) && $latestCreatedAt !== '') {
            $elapsed = time() - (int)strtotime($latestCreatedAt);
            if ($elapsed >= 0 && $elapsed < self::RESEND_COOLDOWN_SECONDS) {
                return [
                    'ok' => false,
                    'error' => 'rate-limited',
                    'retry_after' => self::RESEND_COOLDOWN_SECONDS - $elapsed,
                ];
            }
        }

        $pdo = DB::conn();
        $otp = (string)random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        $pdo->prepare(
            "UPDATE password_reset_otps
             SET used_at = now()
             WHERE email = :email
               AND used_at IS NULL"
        )->execute(['email' => $email]);

        $pdo->prepare(
            "INSERT INTO password_reset_otps (user_id, email, otp_hash, expires_at, attempts)
             VALUES (:user_id, :email, :otp_hash, now() + interval '10 minutes', 0)"
        )->execute([
            'user_id' => (int)$user['id'],
            'email' => $email,
            'otp_hash' => $otpHash,
        ]);

        return [
            'ok' => true,
            'email' => $email,
            'otp' => $otp,
            'expires_minutes' => self::OTP_EXPIRE_MINUTES,
        ];
    }

    public static function verify(string $email, string $otp, bool $checkOnly = false): array
    {
        $email = strtolower(trim($email));
        $otp = trim($otp);

        \error_log('PasswordResetOtp::verify called - email=' . $email . ', otp=' . $otp . ', checkOnly=' . ($checkOnly ? 'true' : 'false'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $otp)) {
            \error_log('PasswordResetOtp::verify - email or otp format invalid');
            return ['ok' => false, 'error' => 'otp-invalid'];
        }

        self::ensureTable();

        $pdo = DB::conn();
        $st = $pdo->prepare(
            "SELECT id, user_id, otp_hash, expires_at, attempts, used_at
             FROM password_reset_otps
             WHERE email = :email
             ORDER BY id DESC
             LIMIT 1"
        );
        $st->execute(['email' => $email]);
        $row = $st->fetch();

        if (!$row) {
            \error_log('PasswordResetOtp::verify - otp-not-found');
            return ['ok' => false, 'error' => 'otp-not-found'];
        }

        \error_log('PasswordResetOtp::verify - found OTP, used_at=' . ($row['used_at'] ?? 'null') . ', attempts=' . ($row['attempts'] ?? '0'));

        if (!empty($row['used_at'])) {
            \error_log('PasswordResetOtp::verify - otp-used');
            return ['ok' => false, 'error' => 'otp-used'];
        }

        if ((int)($row['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            \error_log('PasswordResetOtp::verify - otp-too-many-attempts');
            return ['ok' => false, 'error' => 'otp-too-many-attempts'];
        }

        $expiresAt = strtotime((string)$row['expires_at']);
        if ($expiresAt === false || $expiresAt < time()) {
            \error_log('PasswordResetOtp::verify - otp-expired');
            if (!$checkOnly) {
                self::markUsed((int)$row['id']);
            }
            return ['ok' => false, 'error' => 'otp-expired'];
        }

        if (!password_verify($otp, (string)$row['otp_hash'])) {
            \error_log('PasswordResetOtp::verify - otp-invalid (password_verify failed)');
            $pdo->prepare('UPDATE password_reset_otps SET attempts = attempts + 1 WHERE id = :id')
                ->execute(['id' => (int)$row['id']]);
            return ['ok' => false, 'error' => 'otp-invalid'];
        }

        \error_log('PasswordResetOtp::verify - otp-valid');
        if (!$checkOnly) {
            self::markUsed((int)$row['id']);
        }

        return [
            'ok' => true,
            'user_id' => (int)$row['user_id'],
            'email' => $email,
        ];
    }

    public static function invalidateByUserId(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        self::ensureTable();
        DB::conn()->prepare(
            "UPDATE password_reset_otps
             SET used_at = now()
             WHERE user_id = :user_id
               AND used_at IS NULL"
        )->execute(['user_id' => $userId]);
    }

    private static function markUsed(int $id): void
    {
        DB::conn()->prepare('UPDATE password_reset_otps SET used_at = now() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    private static function ensureTable(): void
    {
        DB::conn()->exec(
            "CREATE TABLE IF NOT EXISTS password_reset_otps (
                id BIGSERIAL PRIMARY KEY,
                user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                email TEXT NOT NULL,
                otp_hash TEXT NOT NULL,
                attempts SMALLINT NOT NULL DEFAULT 0,
                expires_at TIMESTAMPTZ NOT NULL,
                used_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )"
        );

        DB::conn()->exec(
            "CREATE INDEX IF NOT EXISTS idx_password_reset_otps_email
             ON password_reset_otps(email, created_at DESC)"
        );
    }
}
