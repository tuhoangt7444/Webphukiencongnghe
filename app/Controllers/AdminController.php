<?php
namespace App\Controllers;

use App\Core\Controller;

class AdminController extends Controller
{
    public function index(): void
    {
        $this->response->send("ADMIN OK");
    }
}
