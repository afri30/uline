<?php

namespace App\Core;

class Request
{
    private array $getParams;
    private array $postParams;
    private ?array $jsonBody = null;
    private array $server;

    public function __construct()
    {
        $this->getParams = $_GET ?? [];
        $this->postParams = $_POST ?? [];
        $this->server = $_SERVER ?? [];
    }

    /**
     * Get request method in UPPERCASE (GET, POST, etc.)
     *
     * @return string
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if request is POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Check if request is GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Check if request is AJAX / Fetch JSON
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        $requestedWith = $this->server['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        return strtolower($requestedWith) === 'xmlhttprequest' 
            || str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json');
    }

    /**
     * Get request path / URI cleaned
     *
     * @return string
     */
    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        
        // Remove query string
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }

        // Handle subfolder installation (e.g. /uline/public or /uline)
        $scriptDir = dirname($scriptName);
        $scriptDir = str_replace('\\', '/', $scriptDir);

        if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Also handle if root folder without public in rewrite
        $baseDir = preg_replace('#/public$#', '', $scriptDir);
        if ($baseDir && $baseDir !== '/' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }

        $uri = trim($uri, '/');
        return $uri === '' ? '/' : '/' . $uri;
    }

    /**
     * Get query parameter from $_GET
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->getParams;
        }
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Get post parameter from $_POST
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->postParams;
        }
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Get input data from GET/POST or JSON
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        $all = $this->all();
        
        if ($key === null) {
            return $all;
        }

        return $all[$key] ?? $default;
    }

    /**
     * Get all inputs merged (GET, POST, JSON)
     *
     * @return array
     */
    public function all(): array
    {
        $data = array_merge($this->getParams, $this->postParams);

        if ($this->jsonBody === null) {
            $contentType = $this->server['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $rawInput = file_get_contents('php://input');
                $decoded = json_decode($rawInput, true);
                $this->jsonBody = is_array($decoded) ? $decoded : [];
            } else {
                $this->jsonBody = [];
            }
        }

        return array_merge($data, $this->jsonBody);
    }

    /**
     * Get trimmed and sanitized string input
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_string($value) ? trim($value) : (string)$value;
    }
}
