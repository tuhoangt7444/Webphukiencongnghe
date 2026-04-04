<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Voucher;

final class VoucherController extends Controller
{
    public function claim(): void
    {
        $this->ensureSession();

        $voucherId = (int)$this->request->input('voucher_id', 0);

        if (!isset($_SESSION['user_id'])) {
            $next = '/';
            if ($voucherId > 0) {
                $next .= '?claim_voucher=' . $voucherId;
            }
            $this->response->redirect('/login?status=buy-login-required&next=' . rawurlencode($next));
            return;
        }

        $result = Voucher::claimForUser((int)$_SESSION['user_id'], $voucherId);

        $status = match ($result) {
            'claimed' => 'claimed',
            'already-claimed' => 'already-claimed',
            'unavailable' => 'unavailable',
            'not-eligible' => 'not-eligible',
            default => 'failed',
        };

        $_SESSION['voucher_claim_status'] = $status;
        $this->response->redirect('/');
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
