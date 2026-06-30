<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router minimalista.
 * - Un solo punto de entrada (index.php).
 * - Soporta parámetros tipo {id}.
 * - Resta la subcarpeta (basePath) de la URI para que funcione en XAMPP.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:array}> */
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Quitar la subcarpeta del proyecto (p.ej. /medapp) de la URI.
        $basePath = Config::basePath();
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = $this->toRegex($route['pattern']);
            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );

                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->$action($params);
                return;
            }
        }

        $this->notFound();
    }

    private function toRegex(string $pattern): string
    {
        // {id} -> grupo nombrado que captura segmentos no vacíos.
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    private function notFound(): void
    {
        http_response_code(404);
        view('errors/404');
    }
}
