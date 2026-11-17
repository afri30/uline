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
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ExamSession;

echo "1. Testing Student Login Authentication...\n";
$userModel = new User();
try {
    $user = $userModel->authenticate('siswa1', 'password123');
    echo "  [PASS] Authenticated as siswa1 successfully! Role: " . $user['role'] . "\n";
    echo "  Student profile attached: " . (!empty($user['student_profile']) ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "  [FAIL] Login error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing Peserta Dashboard queries...\n";
try {
    $studentModel = new Student();
    $subjectModel = new Subject();
    $examModel = new Exam();
    $sessionModel = new ExamSession();

    $student = $studentModel->findByUserIdWithClass((int)($user['id'] ?? 0));
    $allSubjects = $subjectModel->getAllWithTeachers();
    $classId = $student ? (int)$student['class_id'] : 0;
    $studentId = $student ? (int)$student['id'] : 0;
    $availableExams = ($classId && $studentId) ? $examModel->getAvailableForStudentClass($classId, $studentId) : [];
    $history = $studentId ? $sessionModel->getStudentHistory($studentId) : [];

    echo "  [PASS] Student: " . ($student['name'] ?? 'N/A') . " (Class ID: {$classId}, Student ID: {$studentId})\n";
    echo "  [PASS] Available exams: " . count($availableExams) . "\n";
    echo "  [PASS] History items: " . count($history) . "\n";
} catch (Throwable $e) {
    echo "  [FAIL] Dashboard query error: " . $e->getMessage() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n3. Testing Admin Students (/admin/students) Query & View...\n";
try {
    $adminUser = $userModel->authenticate('admin', 'password123');
    echo "  [PASS] Authenticated as admin!\n";

    $page = 1;
    $perPage = 10;
    $offset = 0;
    $whereSql = "WHERE u.`role` = 'PESERTA'";
    $dataSql = "SELECT s.*, u.name, u.username, u.email, u.status, u.created_at as user_created_at,
                       c.name as class_name, c.grade
                FROM `students` s
                JOIN `users` u ON s.user_id = u.id
                LEFT JOIN `classes` c ON s.class_id = c.id
                {$whereSql}
                ORDER BY u.`name` ASC
                LIMIT {$perPage} OFFSET {$offset}";
    $students = $studentModel->query($dataSql, []);
    echo "  [PASS] Admin students fetched: " . count($students) . " students\n";
    foreach ($students as $s) {
        echo "    - " . $s['name'] . " (" . $s['username'] . ") NIS: " . $s['nis'] . "\n";
    }
} catch (Throwable $e) {
    echo "  [FAIL] Admin students error: " . $e->getMessage() . "\n";
}
