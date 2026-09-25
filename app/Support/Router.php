<?php
declare(strict_types=1);

namespace Archium\Support;

final class Router
{
    /** @var array<int, array{0:string,1:string,2:callable|array,3:array}> */
    private array $routes = [];

    /** @param string[] $middleware Classi middleware (devono implementare Archium\Middleware\Middleware) */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param string[] $middleware */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $this->routes[] = [$method, $path, $handler, $middleware];
    }

    public function dispatch(string $method, string $uri, array $config): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = ($path !== '/') ? rtrim($path, '/') : '/';

        foreach ($this->routes as [$m, $pattern, $handler, $middleware]) {
            if ($m !== strtoupper($method)) {
                continue;
            }
            $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $matches)) {
                // Middleware: se uno restituisce false, la risposta e' gia' stata emessa.
                foreach ($middleware as $class) {
                    $instance = new $class();
                    if (!$instance->handle($config)) {
                        return '';
                    }
                }
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                if (is_array($handler)) {
                    [$class, $fn] = $handler;
                    $handler = [new $class($config), $fn];
                }
                return (string) call_user_func_array($handler, array_values($params));
            }
        }

        http_response_code(404);
        return 'Pagina non trovata (404).';
    }
}