<?php
namespace App\Core;
class Router
{
    private Request $request; #biến lưu trữ đối tượng Request
    private array $routes = []; #mảng lưu trữ các tuyến đường

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    #thêm tuyến đường vào mảng routes 
    public function get(string $pattern, string $handler): RouteDef
    {
        return $this->add('GET', $pattern, $handler);
    }
    #thêm tuyến đường vào mảng routes
    public function post(string $pattern, string $handler): RouteDef
    {
        return $this->add('POST', $pattern, $handler);
    }
    #chuan hóa đường dẫn
    private function normalize(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
    #hàm biên dịch mẫu đường dẫn thành biểu thức chính quy
    private function compile(string $pattern): string 
    {
        $pattern = $this->normalize($pattern);
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
            $name = $m[1];
            return '(?P<' . $name . '>[^/]+)';
        }, $pattern);
        return '#^' . $regex . '$#';
    }
    # them tuyến đường vào mảng routes
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
    # chuyển huoớng yêu cầu đến controller và action tương ứng
    public function dispatch(): void
    {
        $method = $this->request->method();
        $path   = $this->request->path();

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $r) {
            if (!preg_match($r['regex'], $path, $matches)) {
                continue;
            }

            # Lấy tham số từ URL
            $params = [];
            foreach ($matches as $k => $v) {
                if (!is_int($k)) {
                    $params[$k] = $v;
                }
            }
            # Lấy danh sách middleware
            $middlewares = $r['middlewares'] ?? [];
            # Hàm core để gọi controller và action
            $core = function () use ($r, $params) {
                $this->invoke($r['handler'], $params);
            };
            # Xây dựng pipeline middleware
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

            // Chạy pipeline
            $pipeline();
            return;
        }

        http_response_code(404);
        echo "404 Not Found";
    }
    #gọi controller và action tương ứng
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

