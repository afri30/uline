<?php

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    /**
     * 404 Not Found Page
     */
    public function notFound(): void
    {
        $this->response->setStatusCode(404);
        $this->view('errors/404', [
            'title' => '404 - Halaman Tidak Ditemukan'
        ], 'auth');
    }

    /**
     * 403 Forbidden Page
     */
    public function forbidden(): void
    {
        $this->response->setStatusCode(403);
        $this->view('errors/403', [
            'title' => '403 - Akses Ditolak'
        ], 'main');
    }
}
