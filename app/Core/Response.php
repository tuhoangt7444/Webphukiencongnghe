<?php
namespace App\Core;
class Response
{
    # đặt HTTP status code
    public function setStatus(int $code): void
    {
        http_response_code($code);
    }

    # gửi response header
    public function header(string $key, string $value): void
    {
        header($key . ': ' . $value);
    }

    # gửi nội dung thường kèm status
    public function send(string $content, int $status = 200): void
    {
        $this->setStatus($status);
        echo $content;
    }

    # chuyển hướng sang URL khác
    public function redirect(string $url, int $status = 302): void
    {
        $this->setStatus($status);
        header('Location: ' . $url);
        exit; 
    }

    # trả về JSON response
    public function json(array $data, int $status = 200): void
    {
        $this->setStatus($status);
        $this->header('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}