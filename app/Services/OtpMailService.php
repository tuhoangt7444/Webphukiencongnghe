<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class OtpMailService
{
    public static function sendPasswordResetOtp(string $toEmail, string $otp, int $expiresMinutes = 10): bool
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $subject = 'TechGear - Ma OTP dat lai mat khau';
        $message = "Xin chao,\n\n"
            . "Ban vua yeu cau dat lai mat khau tai TechGear.\n"
            . "Ma OTP cua ban la: {$otp}\n"
            . "Ma co hieu luc trong {$expiresMinutes} phut.\n\n"
            . "Neu ban khong thuc hien yeu cau nay, vui long bo qua email nay.\n\n"
            . "TechGear";

        $error = '';
        $sent = self::sendViaSmtp($toEmail, $subject, $message, $error);

        if (!$sent) {
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'From: TechGear <no-reply@techgear.local>',
            ];
            $sent = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
            if (!$sent && $error === '') {
                $error = 'mail() failed';
            }
        }

        self::logDispatch($toEmail, $otp, $sent, $error);
        return $sent;
    }

    private static function sendViaSmtp(string $toEmail, string $subject, string $message, string &$error): bool
    {
        $host = trim((string)getenv('MAIL_HOST'));
        if ($host === '') {
            return false;
        }

        $port = (int)((string)getenv('MAIL_PORT') !== '' ? getenv('MAIL_PORT') : 587);
        $username = trim((string)getenv('MAIL_USERNAME'));
        $password = (string)getenv('MAIL_PASSWORD');
        $encryption = strtolower(trim((string)getenv('MAIL_ENCRYPTION')));
        $fromAddress = trim((string)getenv('MAIL_FROM_ADDRESS'));
        $fromName = trim((string)getenv('MAIL_FROM_NAME'));

        if ($username === '' || $password === '' || $fromAddress === '') {
            $error = 'MAIL_* config missing';
            return false;
        }

        if ($fromName === '') {
            $fromName = 'TechGear';
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port > 0 ? $port : 587;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'none') {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->isHTML(false);

            $sent = $mail->send();
            if (!$sent && $error === '') {
                $error = $mail->ErrorInfo;
            }

            return $sent;
        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    private static function logDispatch(string $email, string $otp, bool $sent, string $error = ''): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $line = sprintf(
            "[%s] reset_otp email=%s otp=%s sent=%s error=%s\n",
            date('Y-m-d H:i:s'),
            $email,
            $otp,
            $sent ? 'yes' : 'no',
            $error !== '' ? $error : '-'
        );

        @file_put_contents($logDir . '/otp_mail.log', $line, FILE_APPEND);
    }
}
