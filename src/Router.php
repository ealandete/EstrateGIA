<?php
declare(strict_types=1);

require_once BASE_PATH . '/lib/RateLimiter.php';

class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function add(string $method, string $path, callable $handler): void {
        $this->routes[] = ['method' => strtoupper($method), 'path' => $path, 'handler' => $handler];
    }

    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }

    public function dispatch(string $method, string $uri): mixed {
        $uri = $this->basePath ? str_replace($this->basePath, '', $uri) : $uri;
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH), '/');
        $method = strtoupper($method);

        // Rate limiting global para todas las rutas
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) session_start();
        $limiter = RateLimiter::getInstance();
        if (!$limiter->check()) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Demasiadas peticiones. Espera un minuto.']);
            exit;
        }

        // CSRF validation for POST to API/tools endpoints
        if ($method === 'POST' && $this->isApiRequest($uri)) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $expected = $_SESSION['csrf_token'] ?? '';
            if (!$token || !hash_equals($expected, $token)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token inválido']);
                exit;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->compilePattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                try {
                    return call_user_func_array($route['handler'], $matches);
                } catch (\Throwable $e) {
                    require_once BASE_PATH . '/lib/Logger.php';
                    Logger::getInstance()->error($e->getMessage(), ['uri' => $uri, 'file' => $e->getFile(), 'line' => $e->getLine()]);
                    if ($this->isApiRequest($uri)) {
                        http_response_code(500);
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()]);
                        exit;
                    }
                    throw $e;
                }
            }
        }

        http_response_code(404);
        return '404 - Página no encontrada';
    }

    private function compilePattern(string $path): string {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function isApiRequest(string $uri): bool {
        return str_starts_with($uri, '/generar') || str_starts_with($uri, '/tools/') || str_starts_with($uri, '/api/');
    }
}

