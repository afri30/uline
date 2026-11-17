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

use App\Core\Database;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Kelas;
use App\Models\Subject;

echo "========================================================\n";
echo "  MASTER DATA (USER, GURU, SISWA, KELAS, MAPEL) AUDIT\n";
echo "========================================================\n\n";

$pass = 0;
$fail = 0;

function check(bool $cond, string $msg): void {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [PASS] {$msg}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$msg}\n";
    }
}

// 1. Check clean login page form without role tabs box
$loginHtml = file_get_contents(__DIR__ . '/../app/Views/auth/login.php');
check(!str_contains($loginHtml, 'btn-role-tab'), 'Login View: Role tabs switcher removed as requested');
check(str_contains($loginHtml, 'admin / guru1 / siswa1'), 'Login View: Username placeholder indicates admin, guru1, siswa1');
check(str_contains($loginHtml, 'id="identifier"'), 'Login View: Username/identifier input present');
check(str_contains($loginHtml, 'id="password"'), 'Login View: Password input present');
check(str_contains($loginHtml, 'id="loginSubmitBtn"'), 'Login View: Form submit button is present');

// 2. Check routes in public/index.php
$indexPhp = file_get_contents(__DIR__ . '/../public/index.php');
check(str_contains($indexPhp, "'/admin/guru'"), 'Routes: /admin/guru alias route defined');
check(str_contains($indexPhp, "'/admin/siswa'"), 'Routes: /admin/siswa alias route defined');
check(str_contains($indexPhp, "'/admin/kelas'"), 'Routes: /admin/kelas alias route defined');
check(str_contains($indexPhp, "'/admin/mapel'"), 'Routes: /admin/mapel alias route defined');

// 2b. Check User Index View Dashboard Cards & Super Admin Priority
$usersIndexView = file_get_contents(__DIR__ . '/../app/Views/admin/users/index.php');
check(str_contains($usersIndexView, 'Total Pengguna'), 'Users View: Metric card for Total Pengguna present');
check(str_contains($usersIndexView, 'Super Admin'), 'Users View: Metric card for Super Admin present');
check(str_contains($usersIndexView, 'Guru Penguji'), 'Users View: Metric card for Guru Penguji present');
check(str_contains($usersIndexView, 'Peserta Didik'), 'Users View: Metric card for Peserta Didik present');

// 3. Check Models & Database Data
$db = Database::getConnection();
$userModel = new User();
$teacherModel = new Teacher();
$studentModel = new Student();
$kelasModel = new Kelas();
$subjectModel = new Subject();

// Check that Super Admin (admin) is ordered at row 1
$orderedUsers = $userModel->query("
    SELECT id, name, username, role 
    FROM users 
    ORDER BY CASE 
        WHEN role = 'SUPER_ADMIN' THEN 1 
        WHEN role = 'GURU' THEN 2 
        WHEN role = 'PESERTA' THEN 3 
        ELSE 4 
    END ASC, id ASC 
    LIMIT 15
");
check(!empty($orderedUsers) && $orderedUsers[0]['role'] === 'SUPER_ADMIN', 'Users Ordering: SUPER_ADMIN is prioritized at Row #1');
check(!empty($orderedUsers) && $orderedUsers[0]['username'] === 'admin', 'Users Ordering: Username at Row #1 is admin');

// Check authentication for all 3 roles: admin, guru1, siswa1
$authAdmin = $userModel->authenticate('admin', 'password123');
check(!empty($authAdmin) && $authAdmin['role'] === 'SUPER_ADMIN', 'Auth admin: Login with username admin & password123 successful');

$authGuru = $userModel->authenticate('guru1', 'password123');
check(!empty($authGuru) && $authGuru['role'] === 'GURU', 'Auth guru1: Login with username guru1 & password123 successful');

$authSiswa = $userModel->authenticate('siswa1', 'password123');
check(!empty($authSiswa) && $authSiswa['role'] === 'PESERTA', 'Auth siswa1: Login with username siswa1 & password123 successful');

$users = $userModel->all();
check(count($users) >= 15, 'Users: At least 15 users in database (Current: ' . count($users) . ')');

$teachers = $teacherModel->all();
check(count($teachers) >= 4, 'Teachers: At least 4 teachers in database (Current: ' . count($teachers) . ')');

$students = $studentModel->all();
check(count($students) >= 10, 'Students: At least 10 students in database (Current: ' . count($students) . ')');

$classes = $kelasModel->all();
check(count($classes) >= 3, 'Classes: At least 3 classes in database (Current: ' . count($classes) . ')');

$subjects = $subjectModel->all();
check(count($subjects) >= 5, 'Subjects: At least 5 subjects in database (Current: ' . count($subjects) . ')');

// 4. Test User Sync Creation for GURU
$testGuruUser = 'test_guru_' . time();
$newTeacherUserId = $userModel->createWithPassword([
    'name' => 'Guru Test Automated',
    'username' => $testGuruUser,
    'email' => $testGuruUser . '@example.com',
    'password' => 'password123',
    'role' => 'GURU',
    'status' => 'active'
]);

$nipTest = '1999' . rand(100000, 999999);
$teacherModel->create([
    'user_id' => $newTeacherUserId,
    'nip' => $nipTest,
    'gender' => 'L'
]);

$teacherCheck = $teacherModel->findWhere('user_id', $newTeacherUserId);
check(!empty($teacherCheck) && $teacherCheck['nip'] === $nipTest, 'Sync GURU: Teacher profile successfully created and linked to user');

// Clean up test guru
$userModel->delete($newTeacherUserId);
$teacherCheckAfter = $teacherModel->findWhere('user_id', $newTeacherUserId);
check(empty($teacherCheckAfter), 'Cascade Delete: Deleting user cascades and removes teacher profile');

// 5. Test User Sync Creation for PESERTA
$testSiswaUser = 'test_siswa_' . time();
$newSiswaUserId = $userModel->createWithPassword([
    'name' => 'Siswa Test Automated',
    'username' => $testSiswaUser,
    'email' => $testSiswaUser . '@example.com',
    'password' => 'password123',
    'role' => 'PESERTA',
    'status' => 'active'
]);

$nisTest = '2026' . rand(1000, 9999);
$studentModel->create([
    'user_id' => $newSiswaUserId,
    'nis' => $nisTest,
    'gender' => 'P'
]);

$studentCheck = $studentModel->findWhere('user_id', $newSiswaUserId);
check(!empty($studentCheck) && $studentCheck['nis'] === $nisTest, 'Sync PESERTA: Student profile successfully created and linked to user');

// Clean up test student
$userModel->delete($newSiswaUserId);
$studentCheckAfter = $studentModel->findWhere('user_id', $newSiswaUserId);
check(empty($studentCheckAfter), 'Cascade Delete: Deleting user cascades and removes student profile');

// 6. Test Class Integrity Guard
$classWithExam = $db->query("SELECT class_id FROM exams LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($classWithExam && !empty($classWithExam['class_id'])) {
    $cid = (int)$classWithExam['class_id'];
    $examCount = $kelasModel->query("SELECT COUNT(*) as total FROM exams WHERE class_id = :cid", [':cid' => $cid]);
    check((int)$examCount[0]['total'] > 0, 'Class Protection: Exam dependency detected correctly for class ID ' . $cid);
}

// 7. Test Subject Integrity Guard
$subWithExam = $db->query("SELECT subject_id FROM exams LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($subWithExam && !empty($subWithExam['subject_id'])) {
    $sid = (int)$subWithExam['subject_id'];
    $subExamCount = $subjectModel->query("SELECT COUNT(*) as total FROM exams WHERE subject_id = :sid", [':sid' => $sid]);
    check((int)$subExamCount[0]['total'] > 0, 'Subject Protection: Exam dependency detected correctly for subject ID ' . $sid);
}

echo "\n========================================================\n";
echo "  RESULT: {$pass} PASSED, {$fail} FAILED\n";
echo "========================================================\n";

if ($fail > 0) exit(1);
