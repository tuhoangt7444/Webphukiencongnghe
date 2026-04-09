<?php
namespace App\Core;

use App\Services\SecurityLogger;

class Router
{
    private Request $request;
    private array $routes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    # đăng ký route GET
    public function get(string $pattern, string $handler): RouteDef
    {
        return $this->add('GET', $pattern, $handler);
    }

    # đăng ký route POST
    public function post(string $pattern, string $handler): RouteDef
    {
        return $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, string $handler): RouteDef
    {
        return $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, string $handler): RouteDef
    {
        return $this->add('DELETE', $pattern, $handler);
    }
    # chuẩn hóa path route
    private function normalize(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
    # biên dịch pattern route sang regex
    private function compile(string $pattern): string 
    {
        $pattern = $this->normalize($pattern);
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
            $name = $m[1];
            return '(?P<' . $name . '>[^/]+)';
        }, $pattern);
        return '#^' . $regex . '$#';
    }
    # thêm route và trả về RouteDef để gắn middleware
    private function add(string $method, string $pattern,string $handler): RouteDef
    {
        $this->routes[$method][] = [
            'pattern' => $this->normalize($pattern),
            'regex' => $this->compile($pattern),
            'handler' => $handler,
            'middlewares' => []
        ];
        $index = count($this->routes[$method]) - 1;
        return new RouteDef($this, $method, $index);
    }
    public function attachMiddlewares(string $method, int $index, array $middlewares): void
    {
        $this->routes[$method][$index]['middlewares'] = $middlewares;
    }
    # dispatch request đến controller/action tương ứng
    public function dispatch(): void
    {
        $method = $this->request->method();
        $path   = $this->request->path();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !$this->verifyCsrfToken()) {
            SecurityLogger::event('csrf_mismatch', [
                'method' => $method,
                'path' => $path,
            ]);
            http_response_code(419);
            echo '419 CSRF Token Mismatch';
            return;
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $r) {
            if (!preg_match($r['regex'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $k => $v) {
                if (!is_int($k)) {
                    $params[$k] = $v;
                }
            }
            $middlewares = $r['middlewares'] ?? [];
            $core = function () use ($r, $params) {
                $this->invoke($r['handler'], $params);
            };
            $pipeline = array_reduce(
                array_reverse($middlewares),
                function ($next, $mwClass) {
                    return function () use ($mwClass, $next) {
                        $mw = new $mwClass();
                        return $mw->handle($this->request, $next);
                    };
                },
                $core
            );

            $pipeline();
            return;
        }

        $allowed = $this->allowedMethodsForPath($path);
        if ($allowed !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            echo '405 Method Not Allowed';
            return;
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    private function verifyCsrfToken(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
        if ($sessionToken === '') {
            return false;
        }

        $token = trim((string)$this->request->input('csrf_token', ''));
        if ($token === '') {
            $headers = $this->request->headers();
            $normalized = [];
            foreach ($headers as $k => $v) {
                $normalized[strtolower((string)$k)] = $v;
            }

            $token = trim((string)($normalized['x-csrf-token'] ?? $normalized['x-xsrf-token'] ?? ''));
        }

        return $token !== '' && hash_equals($sessionToken, $token);
    }

    private function allowedMethodsForPath(string $path): array
    {
        $allowed = [];
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $r) {
                if (preg_match($r['regex'], $path)) {
                    $allowed[] = $method;
                    break;
                }
            }
        }

        sort($allowed);
        return array_values(array_unique($allowed));
    }
    # gọi controller action
    private function invoke(string $handler, array $params): void
    {
        [$controllerName, $action] = explode('@', $handler);

        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo "Controller {$controllerClass} not found";
            return;
        }

        $response = new \App\Core\Response();
        $controller = new $controllerClass($this->request, $response);


        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo "Action not found: {$controllerClass}@{$action}";
            return;
        }
        $controller->$action(...array_values($params));
    }
}
class RouteDef
{
    public function __construct(
        private Router $router,
        private string $method,
        private int $index
    ) {}

    public function middleware(array $middlewares): self
    {
        $this->router->attachMiddlewares($this->method, $this->index, $middlewares);
        return $this;
    }
}

