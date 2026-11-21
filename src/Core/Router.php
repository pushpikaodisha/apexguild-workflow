<?php
declare(strict_types=1);

namespace ApexGuild\Core;

/**
 * Enterprise HTTP Router with Middleware Pipeline and View Rendering
 */
class Router
{
    private array $routes = [];
    private static ?Router $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): self
    {
        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => '/' . trim($path, '/'),
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = '/' . trim($path, '/');
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Check exact path match
            if ($route['path'] === $path) {
                $this->executeRoute($route, []);
                return;
            }

            // Check regex dynamic parameter match e.g. /requests/{id}
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^{$pattern}$#";

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, '\is_string', ARRAY_FILTER_USE_KEY);
                $this->executeRoute($route, $params);
                return;
            }
        }

        // 404 Not Found
        $this->handleNotFound();
    }

    private function executeRoute(array $route, array $params): void
    {
        // Execute Middlewares
        foreach ($route['middlewares'] as $middleware) {
            if (is_string($middleware)) {
                $mw = new $middleware();
                $proceed = $mw->handle();
            } elseif (is_callable($middleware)) {
                $proceed = $middleware();
            } else {
                $proceed = true;
            }

            if ($proceed === false) {
                return; // Middleware intercepted/redirected
            }
        }

        // Execute Handler
        $handler = $route['handler'];
        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass();
            $controller->$action($params);
        } elseif (is_callable($handler)) {
            call_user_func($handler, $params);
        }
    }

    private function handleNotFound(): void
    {
        http_response_code(404);
        if (str_starts_with($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            echo json_encode(['error' => 'Endpoint not found', 'status' => 404]);
            return;
        }

        self::render('errors/404', [
            'title' => 'Page Not Found - ApexGuild Enterprise'
        ]);
    }

    /**
     * Render a view file inside an optional layout
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        extract($data);
        $viewsPath = dirname(__DIR__, 2) . '/views/';
        $viewFile = $viewsPath . ltrim($view, '/') . '.php';

        if (!file_exists($viewFile)) {
            echo "View [{$view}] not found at {$viewFile}";
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = $viewsPath . ltrim($layout, '/') . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
                return;
            }
        }

        echo $content;
    }

    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
