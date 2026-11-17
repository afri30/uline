<?php

namespace App\Core;

use Exception;

abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Render a view file with optional data and layout wrapper
     *
     * @param string $view e.g. 'dashboard/index' or 'auth/login'
     * @param array $data Data passed to view
     * @param string|null $layout e.g. 'main', 'auth', or null for raw view
     * @return void
     * @throws Exception
     */
    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        // Extract data into variables for view
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception("View file not found: {$viewPath}");
        }

        if ($layout === null) {
            require $viewPath;
            return;
        }

        // Capture view content to buffer
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Render layout containing $content
        $layoutPath = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutPath)) {
            throw new Exception("Layout file not found: {$layoutPath}");
        }

        require $layoutPath;
    }

    /**
     * Return JSON response
     *
     * @param mixed $data
     * @param int $status
     * @return void
     */
    protected function json($data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    /**
     * Redirect to path
     *
     * @param string $path
     * @return void
     */
    protected function redirect(string $path): void
    {
        $this->response->redirect($path);
    }

    /**
     * Instantiate model helper
     *
     * @param string $model
     * @return object
     * @throws Exception
     */
    protected function model(string $model): object
    {
        $modelClass = "App\\Models\\" . $model;
        if (!class_exists($modelClass)) {
            throw new Exception("Model class {$modelClass} does not exist.");
        }
        return new $modelClass();
    }
}

class_alias(Controller::class, 'App\\Core\\BaseController');

