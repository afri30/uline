<?php

require __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Models\User;

$userModel = new User();

echo "Testing User Authenticate:\n";
try {
    $admin = $userModel->authenticate('admin', 'password123');
    echo "Admin: OK (Role: " . $admin['role'] . ", Name: " . $admin['name'] . ")\n";
} catch (Exception $e) {
    echo "Admin ERROR: " . $e->getMessage() . "\n";
}

try {
    $guru = $userModel->authenticate('guru1', 'password123');
    echo "Guru: OK (Role: " . $guru['role'] . ", NIP: " . ($guru['teacher_profile']['nip'] ?? 'N/A') . ")\n";
} catch (Exception $e) {
    echo "Guru ERROR: " . $e->getMessage() . "\n";
}

try {
    $siswa = $userModel->authenticate('siswa1', 'password123');
    echo "Siswa: OK (Role: " . $siswa['role'] . ", Kelas: " . ($siswa['student_profile']['class_name'] ?? 'N/A') . ")\n";
} catch (Exception $e) {
    echo "Siswa ERROR: " . $e->getMessage() . "\n";
}

try {
    $nonaktif = $userModel->authenticate('siswa_nonaktif', 'password123');
    echo "Nonaktif: UNEXPECTED SUCCESS\n";
} catch (Exception $e) {
    echo "Nonaktif correctly rejected: " . $e->getMessage() . "\n";
}
