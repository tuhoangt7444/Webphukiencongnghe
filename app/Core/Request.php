<?php
namespace App\Core;
class Request
{
    public function method(): string 
    {
        #lấy phương thức của request
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
    # lấy đường dẫn của request
    public function path(): string 
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/'; # lấy URI từ domain
        $path = parse_url($uri, PHP_URL_PATH) ?? '/'; # tach phần path từ URI
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
    # lấy các tham số truy vấn từ url
    public function query(): array
    {
        return $_GET;
    }
    # lấy dữ liệu từ bodu từ request
    public function body(): array
    {
        return $_POST;
    }
    #lấy giá trị của 1 tham số cụ thể từ request
    public function input(string $key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }
    #lấy tất cả các header từ request
    public function headers(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($k, 5)));
                $headers[$name] = $v;
            }
        }
        return $headers;
    }
}
