<?php

namespace App\Core;

use Exception;

class Router
{
    private static array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Register GET route
     *
     * @param string $path
     * @param string|array|callable $handler
     * @param array $middlewares
     */
    public static function get(string $path, $handler, array $middlewares = []): void
    {
        self::add('GET', $path, $handler, $middlewares);
    }

    /**
     * Register POST route
     *
     * @param string $path
     * @param string|array|callable $handler
     * @param array $middlewares
     */
    public static function post(string $path, $handler, array $middlewares = []): void
    {
        self::add('POST', $path, $handler, $middlewares);
    }

    /**
     * Add route to collection
     *
     * @param string $method
     * @param string $path
     * @param string|array|callable $handler
     * @param array $middlewares
     */
    public static function add(string $method, string $path, $handler, array $middlewares = []): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Dispatch the current request to matching route
     */
    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $routePath = $route['path'];
            // Convert route pattern with {param} into regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePath);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match

                // Execute middlewares
                $this->executeMiddlewares($route['middlewares']);

                // Execute handler
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        // Route Not Found (404)
        $this->handleNotFound();
    }

    /**
     * Execute middlewares for route
     *
     * @param array $middlewares
     */
    private function executeMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $middlewareClass = $middleware;
            $params = [];

            // Support tuple format: [MiddlewareClass::class, ['PARAM1', 'PARAM2']]
            if (is_array($middleware)) {
                $middlewareClass = $middleware[0];
                $params = $middleware[1] ?? [];
            } elseif (is_string($middleware) && str_contains($middleware, ':')) {
                [$middlewareClass, $paramStr] = explode(':', $middleware, 2);
                $params = explode(',', $paramStr);
            }

            if (class_exists($middlewareClass)) {
                $instance = !empty($params) ? new $middlewareClass($params) : new $middlewareClass();
                if ($instance instanceof Middleware) {
                    $result = $instance->handle($this->request);
                    if ($result === false) {
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Execute handler (Controller@action, Closure, or [Controller, action])
     *
     * @param mixed $handler
     * @param array $params
     * @throws Exception
     */
    private function executeHandler($handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerName, $action] = explode('@', $handler);
            $controllerClass = "App\\Controllers\\" . $controllerName;

            if (!class_exists($controllerClass)) {
                throw new Exception("Controller {$controllerClass} not found.");
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $action)) {
                throw new Exception("Action {$action} not found in {$controllerClass}.");
            }

            call_user_func_array([$controller, $action], $params);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controller, $action] = $handler;
            if (is_string($controller)) {
                $controller = new $controller();
            }
            call_user_func_array([$controller, $action], $params);
            return;
        }

        throw new Exception("Invalid route handler format.");
    }

    /**
     * Handle 404 response
     */
    private function handleNotFound(): void
    {
        $this->response->setStatusCode(404);
        $errorView = __DIR__ . '/../Views/errors/404.php';
        
        if (file_exists($errorView)) {
            $content = '';
            ob_start();
            require $errorView;
            $content = ob_get_clean();

            $layoutPath = __DIR__ . '/../Views/layouts/auth.php';
            if (file_exists($layoutPath)) {
                $title = "404 - Halaman Tidak Ditemukan";
                require $layoutPath;
                return;
            }
            echo $content;
            return;
        }

        echo "<h1>404 Not Found</h1><p>The requested page was not found.</p>";
    }
}
