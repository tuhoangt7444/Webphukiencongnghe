<?php
namespace App\Core;
class Response
{
    #đặt mã trạng thái cho phản hồi
    public function setStatus(int $code): void
    {
        http_response_code($code);
    }
    #đặt header cho phản hồi
    public function header(string $key, string $value): void
    {
        header($key . ': ' . $value);
    }
    #gửi phản hồi với nội dung và mã trạng thái
    public function send(string $content, int $status = 200): void
    {
        $this->setStatus($status);
        echo $content;
    }
    # chuyển hướng đến URL khác
    public function redirect(string $url, int $status = 302): void
    {
        $this->setStatus($status);
        header('Location: ' . $url);
        exit; 
    }
    #gửi phản hồi dưới dạng JSON
    public function json(array $data, int $status = 200): void
    {
        $this->setStatus($status);
        $this->header('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}