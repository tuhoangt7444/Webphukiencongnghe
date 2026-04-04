<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsletterSubscriber;

final class NewsletterController extends Controller
{
    public function subscribe(): void
    {
        $email = trim((string)$this->request->input('email', ''));
        $fromPath = trim((string)$this->request->input('from_path', '/'));

        if ($fromPath === '' || $fromPath[0] !== '/') {
            $fromPath = '/';
        }

        try {
            NewsletterSubscriber::subscribe($email, $fromPath);
            $this->response->redirect($fromPath . '?newsletter=ok');
        } catch (\InvalidArgumentException $e) {
            $this->response->redirect($fromPath . '?newsletter=invalid');
        } catch (\Throwable $e) {
            $this->response->redirect($fromPath . '?newsletter=failed');
        }
    }
}
