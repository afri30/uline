<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Models\User;
use App\Models\Student;
use App\Models\Kelas;

$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Super Administrator',
    'username' => 'admin',
    'email' => 'admin@example.com',
    'role' => 'SUPER_ADMIN',
    'status' => 'active'
];

$_SERVER['REQUEST_URI'] = '/uline/admin/students';
$_SERVER['REQUEST_METHOD'] = 'GET';

$studentModel = new Student();
$kelasModel = new Kelas();

$search = '';
$classId = '';
$status = '';
$page = 1;
$perPage = 10;
$offset = 0;

$whereClauses = ["u.`role` = 'PESERTA'"];
$params = [];
$whereSql = "WHERE " . implode(" AND ", $whereClauses);

$countSql = "SELECT COUNT(s.id) as total 
             FROM `students` s 
             JOIN `users` u ON s.user_id = u.id 
             LEFT JOIN `classes` c ON s.class_id = c.id
             {$whereSql}";
$totalRes = $studentModel->query($countSql, $params);
$totalItems = (int)($totalRes[0]['total'] ?? 0);

$dataSql = "SELECT s.*, u.name, u.username, u.email, u.status, u.created_at as user_created_at,
                   c.name as class_name, c.grade
            FROM `students` s
            JOIN `users` u ON s.user_id = u.id
            LEFT JOIN `classes` c ON s.class_id = c.id
            {$whereSql}
            ORDER BY u.`name` ASC
            LIMIT {$perPage} OFFSET {$offset}";
$students = $studentModel->query($dataSql, $params);
$classes = $kelasModel->findAll('name', 'ASC');

ob_start();
try {
    extract([
        'title' => 'Manajemen Peserta Didik - Super Admin',
        'students' => $students,
        'classes' => $classes,
        'totalItems' => $totalItems,
        'currentPage' => $page,
        'perPage' => $perPage,
        'search' => $search,
        'classId' => $classId,
        'status' => $status
    ]);
    require __DIR__ . '/../app/Views/admin/students/index.php';
    $rendered = ob_get_clean();
    echo "SUCCESS: admin/students/index rendered successfully! Length: " . strlen($rendered) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR in admin/students/index: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Also test the full layout wrapper!
ob_start();
try {
    $content = $rendered;
    $title = 'Manajemen Peserta Didik - Super Admin';
    require __DIR__ . '/../app/Views/layouts/main.php';
    $fullRendered = ob_get_clean();
    echo "SUCCESS: Full layout main.php rendered successfully! Length: " . strlen($fullRendered) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR in layouts/main: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
