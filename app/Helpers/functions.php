<?php

use App\Core\Session;

/**
 * Get base URL of the application
 *
 * @param string $path
 * @return string
 */
function base_url(string $path = ''): string
{
    $config = require __DIR__ . '/../Config/app.php';
    
    if (!empty($config['base_url'])) {
        $baseUrl = rtrim($config['base_url'], '/');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Find script path to detect subfolder
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        
        // If public is in the dirname, strip it out for pretty URLs or leave root
        $dir = preg_replace('#/public$#', '', $dir);
        $dir = ($dir === '/' || $dir === '\\') ? '' : rtrim($dir, '/');
        
        $baseUrl = $protocol . $host . $dir;
    }
    
    $path = ltrim($path, '/');
    return $path ? $baseUrl . '/' . $path : $baseUrl;
}

/**
 * Get asset URL
 *
 * @param string $path
 * @return string
 */
function asset(string $path = ''): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Escape HTML output
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific path
 *
 * @param string $path
 * @return void
 */
function redirect(string $path): void
{
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . base_url($path));
    }
    exit;
}

/**
 * Get current authenticated user
 *
 * @return array|null
 */
function auth(): ?array
{
    return Session::get('user');
}

/**
 * Check if user is logged in
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return Session::has('user') && !empty(Session::get('user'));
}

/**
 * Check if current user has a specific role
 *
 * @param string|array $roles
 * @return bool
 */
function has_role($roles): bool
{
    $user = auth();
    if (!$user || !isset($user['role'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($user['role'], $roles, true);
    }
    
    return $user['role'] === $roles;
}

/**
 * Get current user role
 *
 * @return string|null
 */
function role(): ?string
{
    $user = auth();
    return $user['role'] ?? null;
}

/**
 * Generate or get CSRF token
 *
 * @return string
 */
function csrf_token(): string
{
    return Session::token();
}

/**
 * Output CSRF hidden input field
 *
 * @return string
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token
 *
 * @param string|null $token
 * @return bool
 */
function csrf_verify(?string $token): bool
{
    return Session::verifyToken($token);
}

/**
 * Set or get flash message
 *
 * @param string $key
 * @param string|null $message
 * @return mixed
 */
function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        Session::setFlash($key, $message);
        return null;
    }
    
    return Session::getFlash($key);
}

/**
 * Check if flash message exists
 *
 * @param string $key
 * @return bool
 */
function has_flash(string $key): bool
{
    return Session::hasFlash($key);
}

/**
 * Render Bootstrap 5 pagination HTML
 *
 * @param int $totalItems
 * @param int $perPage
 * @param int $currentPage
 * @param string $path
 * @param array $queryParams
 * @return string
 */
function paginate_links(int $totalItems, int $perPage, int $currentPage, string $path, array $queryParams = []): string
{
    $totalPages = (int)ceil($totalItems / max(1, $perPage));
    if ($totalPages <= 1) {
        return '';
    }

    $currentPage = max(1, min($currentPage, $totalPages));

    $buildUrl = function ($page) use ($path, $queryParams) {
        $params = array_merge($queryParams, ['page' => $page]);
        return base_url($path . '?' . http_build_query($params));
    };

    $html = '<nav aria-label="Navigasi Halaman" class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">';
    $html .= '<div class="text-muted small">Menampilkan <strong>' . (($currentPage - 1) * $perPage + 1) . '</strong> - <strong>' . min($currentPage * $perPage, $totalItems) . '</strong> dari <strong>' . $totalItems . '</strong> data</div>';
    $html .= '<ul class="pagination pagination-sm mb-0">';

    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl($currentPage - 1)) . '"><i class="bi bi-chevron-left"></i></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
    }

    // Page numbers with ellipsis
    $range = 2;
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)) {
            if ($i == $currentPage) {
                $html .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl($i)) . '">' . $i . '</a></li>';
            }
        } elseif ($i == $currentPage - $range - 1 || $i == $currentPage + $range + 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl($currentPage + 1)) . '"><i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Record an audit log entry
 *
 * @param string $action (e.g. LOGIN, LOGOUT, CREATE_EXAM, UPDATE_EXAM, DELETE_EXAM, START_EXAM, SUBMIT_EXAM, PROCTOR_EVENT, REVIEW_EVENT)
 * @param string|null $description
 * @param array|null $metadata
 * @param int|null $userId
 * @return int
 */
function audit_log(string $action, ?string $description = null, ?array $metadata = null, ?int $userId = null): int
{
    return \App\Services\AuditLogger::log($action, $description, $metadata, $userId);
}

/**
 * Get an environment variable with default fallback
 *
 * @param string $key Environment variable name
 * @param mixed $default Fallback value if not set
 * @return mixed
 */
function env(string $key, $default = null)
{
    return \App\Core\Env::get($key, $default);
}

/**
 * Get configuration value using dot notation (e.g. config('app.app_name'), config('database.host'))
 *
 * @param string $key Dot-notated configuration key
 * @param mixed $default Fallback value if not found
 * @return mixed
 */
function config(string $key, $default = null)
{
    return \App\Core\Config::get($key, $default);
}
