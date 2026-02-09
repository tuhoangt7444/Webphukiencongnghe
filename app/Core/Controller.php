<?php
namespace App\Core;

class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }
}
