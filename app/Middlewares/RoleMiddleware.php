<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Session;

class RoleMiddleware implements Middleware
{
    private array $allowedRoles;

    /**
     * @param array|string $roles
     */
    public function __construct($roles = [])
    {
        if (is_string($roles)) {
            $this->allowedRoles = array_map('trim', explode(',', $roles));
        } else {
            $this->allowedRoles = (array) $roles;
        }
    }

    /**
     * Ensure user has required role
     *
     * @param Request $request
     * @return bool|void
     */
    public function handle(Request $request)
    {
        $user = Session::get('user');

        if (!$user || !isset($user['role'])) {
            Session::setFlash('error', 'Sesi tidak valid. Silakan login kembali.');
            redirect('login');
            return false;
        }

        if (!empty($this->allowedRoles) && !in_array($user['role'], $this->allowedRoles, true)) {
            if ($request->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses (Role: ' . $user['role'] . ').'
                ]);
                return false;
            }

            // Render 403 Page
            http_response_code(403);
            $errorView = __DIR__ . '/../Views/errors/403.php';
            if (file_exists($errorView)) {
                $requiredRoles = $this->allowedRoles;
                $userRole = $user['role'];
                
                ob_start();
                require $errorView;
                $content = ob_get_clean();

                $layoutPath = __DIR__ . '/../Views/layouts/main.php';
                if (file_exists($layoutPath)) {
                    $title = "403 - Akses Ditolak";
                    require $layoutPath;
                    return false;
                }
                echo $content;
                return false;
            }

            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
            return false;
        }
    }
}
