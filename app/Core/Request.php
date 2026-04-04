<?php
namespace App\Core;
class Request
{
    public function method(): string 
    {
        #lấy phương thức của request (hỗ trợ override cho form)
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $override = $_POST['_method'] ?? ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null);
            if (is_string($override) && $override !== '') {
                $candidate = strtoupper(trim($override));
                if (in_array($candidate, ['PUT', 'PATCH', 'DELETE'], true)) {
                    return $candidate;
                }
            }
        }

        return $method;
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
        $method = $this->method();
        if ($method === 'GET') {
            return [];
        }

        $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        // Check Content-Type FIRST to handle JSON
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        // Handle form-encoded POST data
        if ($method === 'POST' && !empty($_POST)) {
            return $_POST;
        }

        // Handle other methods or raw form-encoded data
        $raw = file_get_contents('php://input') ?: '';

        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }
    #lấy giá trị của 1 tham số cụ thể từ request
    public function input(string $key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        $body = $this->body();
        if (isset($body[$key])) {
            return $body[$key];
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
