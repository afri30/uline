<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Session;

class GuestMiddleware implements Middleware
{
    /**
     * Ensure user is guest (not logged in)
     *
     * @param Request $request
     * @return bool|void
     */
    public function handle(Request $request)
    {
        if (Session::has('user') && !empty(Session::get('user'))) {
            redirect('dashboard');
            return false;
        }
    }
}
