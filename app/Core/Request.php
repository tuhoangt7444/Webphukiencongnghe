<?php
namespace App\Core;
class Request
{
    # lấy HTTP method, có hỗ trợ override cho form
    public function method(): string 
    {
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

    # lấy path đã chuẩn hóa
    public function path(): string 
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    # lấy query params từ URL
    public function query(): array
    {
        return $_GET;
    }

    # lấy body cho request không phải GET
    public function body(): array
    {
        $method = $this->method();
        if ($method === 'GET') {
            return [];
        }

        $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($method === 'POST' && !empty($_POST)) {
            return $_POST;
        }

        $raw = file_get_contents('php://input') ?: '';

        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }

    # lấy 1 input theo thứ tự POST -> body -> query
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

    # lấy toàn bộ header của request
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
