<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contact;

class ContactController extends Controller
{
    public function index(): void
    {
        $status = trim((string)$this->request->input('status', ''));

        $this->view('contact/index', [
            'title' => 'Liên hệ - TechGear',
            'status' => $status,
        ]);
    }

    public function store(): void
    {
        $name = trim((string)$this->request->input('name', ''));
        $email = trim((string)$this->request->input('email', ''));
        $phone = trim((string)$this->request->input('phone', ''));
        $subject = trim((string)$this->request->input('subject', ''));
        $message = trim((string)$this->request->input('message', ''));

        if ($name === '' || $email === '' || $phone === '' || $subject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->redirect('/contact?status=invalid');
            return;
        }

        try {
            Contact::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message,
            ]);
            $this->response->redirect('/contact?status=sent');
        } catch (\Throwable $e) {
            $this->response->redirect('/contact?status=failed');
        }
    }
}
