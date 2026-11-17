<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Session;

class AuthMiddleware implements Middleware
{
    /**
     * Ensure user is logged in
     *
     * @param Request $request
     * @return bool|void
     */
    public function handle(Request $request)
    {
        if (!Session::has('user') || empty(Session::get('user'))) {
            Session::setFlash('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
            
            if ($request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthenticated session.'
                ]);
                return false;
            }

            redirect('login');
            return false;
        }
    }
}
