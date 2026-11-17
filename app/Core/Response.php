<?php

namespace App\Core;

class Response
{
    /**
     * Set HTTP status code
     *
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Send JSON response
     *
     * @param mixed $data
     * @param int $status
     * @return void
     */
    public function json($data, int $status = 200): void
    {
        $this->setStatusCode($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to URL
     *
     * @param string $path
     * @param int $status
     * @return void
     */
    public function redirect(string $path, int $status = 302): void
    {
        http_response_code($status);
        redirect($path);
    }
}
