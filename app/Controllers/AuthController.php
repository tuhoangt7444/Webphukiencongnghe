<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\OtpMailService;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $next = (string)$this->request->input('next', '');
        if (!$this->isSafeInternalPath($next)) {
            $next = '';
        }

        $this->view('auth/login', [
            'title' => 'Đăng nhập',
            'status' => (string)$this->request->input('status', ''),
            'next' => $next,
        ]);
    }

    public function login(): void
    {
        $this->ensureSession();

        $login = trim((string)$this->request->input('login', ''));
        $password = (string)$this->request->input('password', '');

        if ($login === '' || $password === '') {
            $this->response->redirect('/login?status=invalid');
            return;
        }

        $user = User::findByLogin($login);
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            $this->response->redirect('/login?status=failed');
            return;
        }

        if (($user['status'] ?? 'active') !== 'active') {
            $this->response->redirect('/login?status=locked');
            return;
        }

        $this->loginUser($user);

        $next = (string)$this->request->input('next', '');
        if ($this->isSafeInternalPath($next)) {
            $this->response->redirect($next);
            return;
        }

        $sessionRoleCode = (string)($_SESSION['user_role_code'] ?? '');
        if ($sessionRoleCode !== '' && $sessionRoleCode !== 'customer') {
            $this->response->redirect('/admin');
            return;
        }

        $this->response->redirect('/');
    }

    public function redirectToGoogle(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $this->ensureSession();
        $config = $this->googleConfig();
        if (!$this->isGoogleConfigured($config)) {
            $this->response->redirect('/login?status=google-unavailable');
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $query = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        $this->response->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGoogleCallback(): void
    {
        $this->ensureSession();
        $config = $this->googleConfig();
        if (!$this->isGoogleConfigured($config)) {
            $this->response->redirect('/login?status=google-unavailable');
            return;
        }

        $state = (string)$this->request->input('state', '');
        $savedState = (string)($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);

        if ($state === '' || $savedState === '' || !hash_equals($savedState, $state)) {
            $this->response->redirect('/login?status=google-state-invalid');
            return;
        }

        if ((string)$this->request->input('error', '') !== '') {
            $this->response->redirect('/login?status=google-denied');
            return;
        }

        $code = trim((string)$this->request->input('code', ''));
        if ($code === '') {
            $this->response->redirect('/login?status=google-failed');
            return;
        }

        $tokenData = $this->fetchJsonPost('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        $accessToken = (string)($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            $this->response->redirect('/login?status=google-failed');
            return;
        }

        $profile = $this->fetchJson('https://www.googleapis.com/oauth2/v3/userinfo?' . http_build_query([
            'access_token' => $accessToken,
        ]));

        $email = strtolower(trim((string)($profile['email'] ?? '')));
        $fullName = trim((string)($profile['name'] ?? ''));
        $googleAvatarUrl = trim((string)($profile['picture'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->redirect('/login?status=google-email-required');
            return;
        }

        $user = User::findByEmail($email);
        if (!$user) {
            $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $newUserId = User::createCustomer($email, $passwordHash, [
                'full_name' => $fullName,
                'phone' => '',
            ]);

            if ($newUserId <= 0) {
                $this->response->redirect('/login?status=google-failed');
                return;
            }

            $user = User::findByEmail($email);
        }

        if ($user && $googleAvatarUrl !== '') {
            User::updateGoogleAvatarByUserId((int)$user['id'], $googleAvatarUrl);
            $user = User::findByEmail($email) ?? $user;
        }

        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            $this->response->redirect('/login?status=locked');
            return;
        }

        $this->loginUser($user);
        $sessionRoleCode = (string)($_SESSION['user_role_code'] ?? '');
        if ($sessionRoleCode !== '' && $sessionRoleCode !== 'customer') {
            $this->response->redirect('/admin');
            return;
        }

        $this->response->redirect('/?status=welcome');
    }

    public function showRegister(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $this->view('auth/register', [
            'title' => 'Đăng ký',
            'status' => (string)$this->request->input('status', ''),
        ]);
    }

    public function showForgotPassword(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $this->view('auth/forgot_password', [
            'title' => 'Quên mật khẩu',
            'status' => (string)$this->request->input('status', ''),
            'email' => (string)$this->request->input('email', ''),
        ]);
    }

    public function sendForgotPasswordOtp(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $email = strtolower(trim((string)$this->request->input('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->redirect('/forgot-password?status=email-invalid');
            return;
        }

        $issued = PasswordResetOtp::issueForEmail($email);
        if (($issued['ok'] ?? false) !== true) {
            if (($issued['error'] ?? '') === 'rate-limited') {
                $retryAfter = max(1, (int)($issued['retry_after'] ?? 30));
                $this->response->redirect('/forgot-password?status=otp-rate-limited&retry_after=' . $retryAfter . '&email=' . rawurlencode($email));
                return;
            }

            $this->response->redirect('/forgot-password?status=request-failed');
            return;
        }

        $otp = (string)($issued['otp'] ?? '');
        if ($otp !== '') {
            $sent = OtpMailService::sendPasswordResetOtp($email, $otp, (int)($issued['expires_minutes'] ?? 10));
            if (!$sent) {
                $this->response->redirect('/forgot-password?status=mail-failed&email=' . rawurlencode($email));
                return;
            }
        }

        $this->response->redirect('/forgot-password/verify?status=otp-sent&email=' . rawurlencode($email));
    }

    public function showResetPassword(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $this->view('auth/reset_password', [
            'title' => 'Đặt lại mật khẩu',
            'status' => (string)$this->request->input('status', ''),
            'email' => (string)$this->request->input('email', ''),
        ]);
    }

    public function resetPasswordWithOtp(): void
    {
        if ($this->isLoggedIn()) {
            $this->response->redirect('/');
            return;
        }

        $email = strtolower(trim((string)$this->request->input('email', '')));
        $otp = trim((string)$this->request->input('otp', ''));
        $password = (string)$this->request->input('password', '');
        $passwordConfirm = (string)$this->request->input('password_confirm', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->redirect('/forgot-password?status=email-invalid');
            return;
        }

        if (!preg_match('/^\d{6}$/', $otp)) {
            $this->response->redirect('/forgot-password/verify?status=otp-invalid&email=' . rawurlencode($email));
            return;
        }

        if (strlen($password) < 6 || $password !== $passwordConfirm) {
            $this->response->redirect('/forgot-password/verify?status=password-invalid&email=' . rawurlencode($email));
            return;
        }

        $verified = PasswordResetOtp::verify($email, $otp);
        if (($verified['ok'] ?? false) !== true) {
            $status = match ((string)($verified['error'] ?? '')) {
                'otp-expired' => 'otp-expired',
                'otp-too-many-attempts' => 'otp-too-many-attempts',
                'otp-not-found' => 'otp-not-found',
                'otp-used' => 'otp-used',
                default => 'otp-invalid',
            };

            $this->response->redirect('/forgot-password/verify?status=' . $status . '&email=' . rawurlencode($email));
            return;
        }

        $userId = (int)($verified['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->response->redirect('/forgot-password/verify?status=request-failed&email=' . rawurlencode($email));
            return;
        }

        User::updatePasswordById($userId, password_hash($password, PASSWORD_DEFAULT));
        PasswordResetOtp::invalidateByUserId($userId);

        $this->response->redirect('/login?status=password-reset-success');
    }

    public function verifyOtpOnly(): void
    {
        try {
            if ($this->isLoggedIn()) {
                $this->response->json(['ok' => false, 'error' => 'already-logged-in'], 403);
                return;
            }

            $email = strtolower(trim((string)$this->request->input('email', '')));
            $otp = trim((string)$this->request->input('otp', ''));

            error_log('verifyOtpOnly: email=' . $email . ' (len=' . strlen($email) . '), otp=' . $otp . ' (len=' . strlen($otp) . ')');

            // More lenient email validation
            if (empty($email)) {
                error_log('verifyOtpOnly: email empty');
                $this->response->json(['ok' => false, 'error' => 'email-invalid'], 400);
                return;
            }

            // Try basic regex first
            if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                error_log('verifyOtpOnly: email regex failed for ' . $email);
                $this->response->json(['ok' => false, 'error' => 'email-invalid'], 400);
                return;
            }

            if (!preg_match('/^\d{6}$/', $otp)) {
                error_log('verifyOtpOnly: otp format invalid');
                $this->response->json(['ok' => false, 'error' => 'otp-format-invalid'], 400);
                return;
            }

            $verified = PasswordResetOtp::verify($email, $otp, true);
            error_log('verifyOtpOnly result: ' . json_encode($verified));

            if (($verified['ok'] ?? false) !== true) {
                $error = match ((string)($verified['error'] ?? '')) {
                    'otp-expired' => 'otp-expired',
                    'otp-too-many-attempts' => 'otp-too-many-attempts',
                    'otp-not-found' => 'otp-not-found',
                    'otp-used' => 'otp-used',
                    default => 'otp-invalid',
                };

                $statusCode = $error === 'otp-too-many-attempts' ? 429 : 400;
                $this->response->json(['ok' => false, 'error' => $error], $statusCode);
                return;
            }

            $this->response->json(['ok' => true, 'user_id' => (int)($verified['user_id'] ?? 0)], 200);
        } catch (\Throwable $e) {
            error_log('verifyOtpOnly error: ' . $e->getMessage() . ', ' . $e->getTraceAsString());
            $this->response->json(['ok' => false, 'error' => 'server-error', 'message' => $e->getMessage()], 500);
        }
    }

    public function register(): void
    {
        $this->ensureSession();

        $fullName = trim((string)$this->request->input('full_name', ''));
        $email = trim((string)$this->request->input('email', ''));
        $phone = trim((string)$this->request->input('phone', ''));
        $password = (string)$this->request->input('password', '');
        $confirm = (string)$this->request->input('password_confirm', '');
        $phoneDigits = preg_replace('/\D+/', '', $phone);

        if (
            $fullName === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $phoneDigits === ''
            || strlen((string)$phoneDigits) < 9
            || strlen($password) < 6
            || $password !== $confirm
        ) {
            $this->response->redirect('/register?status=invalid');
            return;
        }

        if (User::findByEmail($email)) {
            $this->response->redirect('/register?status=exists');
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::createCustomer($email, $passwordHash, [
            'full_name' => $fullName,
            'phone' => $phone,
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role_code'] = 'customer';
        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
        ];
        $_SESSION['user_avatar'] = '';

        $this->response->redirect('/?status=welcome');
    }

    public function logout(): void
    {
        $this->ensureSession();

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

        $this->response->redirect('/login?status=logout');
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isLoggedIn(): bool
    {
        $this->ensureSession();
        return isset($_SESSION['user_id']);
    }

    private function loginUser(array $user): void
    {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = (string)$user['email'];
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
        ];
        $_SESSION['user_avatar'] = User::resolveAvatarUrlFromUserRow($user);

        if (isset($user['role_id'])) {
            $_SESSION['user_role_id'] = (int)$user['role_id'];
        }
        if (isset($user['role_code'])) {
            $_SESSION['user_role_code'] = (string)$user['role_code'];
        }
    }

    private function googleConfig(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/social.php';
        return is_array($config['google'] ?? null) ? $config['google'] : [];
    }

    private function isGoogleConfigured(array $config): bool
    {
        return trim((string)($config['client_id'] ?? '')) !== ''
            && trim((string)($config['client_secret'] ?? '')) !== ''
            && trim((string)($config['redirect_uri'] ?? '')) !== '';
    }

    private function fetchJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function fetchJsonPost(string $url, array $payload): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isSafeInternalPath(string $path): bool
    {
        return $path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//');
    }
}
