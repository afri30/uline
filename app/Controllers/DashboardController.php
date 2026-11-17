<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Dispatcher route: /dashboard
     * Automatically redirects user to their role-specific dashboard.
     */
    public function index(): void
    {
        $user = auth();
        $targetUrl = User::getDashboardUrl($user['role'] ?? '');
        $this->redirect($targetUrl);
    }
}
