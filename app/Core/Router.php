<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [$method, $this->compile($path), $handler];
    }

    private function compile(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    public function dispatch(string $method, string $uri): void
    {
        // Treat HEAD like GET for routing (body stripped by the SAPI).
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // Strip query, normalize trailing slash (except root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        foreach ($this->routes as [$m, $regex, $handler]) {
            if ($m !== $method) {
                continue;
            }
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                if (is_array($handler)) {
                    [$class, $fn] = $handler;
                    (new $class())->$fn($params);
                } else {
                    $handler($params);
                }
                return;
            }
        }
        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Tidak ditemukan']);
    }
}
