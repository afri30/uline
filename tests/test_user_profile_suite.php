<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Controllers\Peserta\ProfileController as PesertaProfileController;
use App\Core\Session;

$totalPass = 0;
$totalFail = 0;

function assertProfile(bool $condition, string $testName, string $detail = ''): void {
    global $totalPass, $totalFail;
    if ($condition) {
        $totalPass++;
        echo "  [PASS] {$testName}\n";
    } else {
        $totalFail++;
        echo "  [FAIL] {$testName}\n";
        if (!empty($detail)) {
            echo "         Detail: {$detail}\n";
        }
    }
}

echo "====================================================================\n";
echo "  TEST SUITE: USER PROFILE IMPLEMENTATION (MULTI-ROLE & RBAC)       \n";
echo "====================================================================\n\n";

// 1. Test User::getProfileUrl mapping
echo "--- 1. Testing User::getProfileUrl Role Mapping ---\n";
assertProfile(User::getProfileUrl('SUPER_ADMIN') === 'admin/profile', "1.1 SUPER_ADMIN mapped to admin/profile");
assertProfile(User::getProfileUrl('GURU') === 'guru/profile', "1.2 GURU mapped to guru/profile");
assertProfile(User::getProfileUrl('PESERTA') === 'peserta/profile', "1.3 PESERTA mapped to peserta/profile");
assertProfile(User::getProfileUrl('UNKNOWN') === 'login', "1.4 Unknown role defaults to login");

// 2. Test Peserta Profile Controller existence & instantiation
echo "\n--- 2. Testing Peserta ProfileController Instantiation ---\n";
$controller = new PesertaProfileController();
assertProfile($controller instanceof \App\Core\Controller, "2.1 Peserta ProfileController extends Core Controller");

// 3. Test Student Data Fetching for Profile
echo "\n--- 3. Testing Student Profile Data Loading ---\n";
$userModel = new User();
$studentModel = new Student();
$siswa = $userModel->findByUsernameOrEmail('siswa1');
assertProfile(!empty($siswa), "3.1 Akun siswa1 ditemukan di database ({$siswa['name']})");

$studentProfile = $studentModel->findByUserIdWithClass((int)$siswa['id']);
assertProfile(!empty($studentProfile), "3.2 Data profil siswa beserta kelas ditemukan");
assertProfile(!empty($studentProfile['nis']), "3.3 NIS siswa terisi: {$studentProfile['nis']}");
assertProfile(!empty($studentProfile['class_name']), "3.4 Kelas siswa terisi: {$studentProfile['class_name']}");

// 4. Test Navbar & Sidebar Profile Links
echo "\n--- 4. Testing Layouts (Navbar & Sidebar Integration) ---\n";
$navbarContent = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/partials/navbar.php');
assertProfile(!str_contains($navbarContent, "base_url('profile')"), "4.1 Navbar dropdown tidak lagi memuat link base_url('profile')");
assertProfile(!str_contains($navbarContent, "Profil Saya"), "4.2 Navbar dropdown bersih dari menu 'Profil Saya'");

$sidebarContent = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/partials/sidebar.php');
assertProfile(str_contains($sidebarContent, "peserta/profile") && str_contains($sidebarContent, "Profil Peserta"), "4.3 Sidebar menampilkan Profil Peserta di menu kiri");
assertProfile(str_contains($sidebarContent, "base_url('profile')"), "4.4 Sidebar footer avatar memuat link profile");
assertProfile(str_contains($sidebarContent, "admin/profile") && str_contains($sidebarContent, "Profil Admin"), "4.5 Sidebar menampilkan Profil Admin secara eksplisit di menu kiri");
assertProfile(str_contains($sidebarContent, "guru/profile") && str_contains($sidebarContent, "Profil Guru"), "4.6 Sidebar menampilkan Profil Guru secara eksplisit di menu kiri");
assertProfile(!str_contains($sidebarContent, "Profil Saya"), "4.7 Sidebar Menu Utama tidak menduplikasi 'Profil Saya'");

// 5. Test Profile View File Existence
echo "\n--- 5. Testing Profile Views ---\n";
$pesertaViewPath = dirname(__DIR__) . '/app/Views/peserta/profile/index.php';
assertProfile(file_exists($pesertaViewPath), "5.1 File app/Views/peserta/profile/index.php tersedia");
$pesertaViewContent = file_get_contents($pesertaViewPath);
assertProfile(str_contains($pesertaViewContent, 'peserta/profile'), "5.2 View memuat form action peserta/profile");
assertProfile(str_contains($pesertaViewContent, 'peserta/profile/password'), "5.3 View memuat form action peserta/profile/password");
assertProfile(str_contains($pesertaViewContent, 'NIS'), "5.4 View memuat informasi NIS siswa");
assertProfile(str_contains($pesertaViewContent, 'Kata Sandi'), "5.5 View memuat formulir ganti kata sandi");
$adminViewPath = dirname(__DIR__) . '/app/Views/admin/profile/index.php';
assertProfile(file_exists($adminViewPath), "5.6 File app/Views/admin/profile/index.php tersedia");

// 6. Test Routes in public/index.php
echo "\n--- 6. Testing Route Registration in public/index.php ---\n";
$indexContent = file_get_contents(dirname(__DIR__) . '/public/index.php');
assertProfile(str_contains($indexContent, "'/profile'"), "6.1 Route /profile terdaftar");
assertProfile(str_contains($indexContent, "'/admin/profile'"), "6.2 Route /admin/profile terdaftar");
assertProfile(str_contains($indexContent, "'/peserta/profile'"), "6.3 Route /peserta/profile terdaftar");
assertProfile(str_contains($indexContent, "'/peserta/profile/password'"), "6.4 Route /peserta/profile/password terdaftar");

echo "\n====================================================================\n";
echo "  HASIL AKHIR: {$totalPass} PASSED, {$totalFail} FAILED\n";
echo "====================================================================\n";

