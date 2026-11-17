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
use App\Core\Env;
use App\Core\Session;
use App\Core\Request;
use App\Middlewares\RoleMiddleware;
use App\Models\ExamConfig;
use App\Controllers\Admin\ExamConfigController;

Session::start();

echo "====================================================================\n";
echo "   KONFIGURASI UJIAN TEST SUITE (DATABASE, ENV, MIDDLEWARE, CONTROLLER)\n";
echo "   AI Online Examination & Proctoring System\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(bool $condition, string $testName, string $details = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$testName}" . ($details ? " ({$details})" : "") . "\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$testName} - {$details}\n";
    }
}

// -------------------------------------------------------------------------
// SECTION 1: DATABASE & MODEL VERIFICATION
// -------------------------------------------------------------------------
echo "1. TESTING DATABASE TABLE 'exam_configs' & MODEL...\n";
$pdo = Database::getConnection();

// Check table existence
$stmt = $pdo->query("SHOW TABLES LIKE 'exam_configs'");
$tableExists = (bool)$stmt->fetch();
assertCheck($tableExists, "1. Table 'exam_configs' exists in database");

// Check required columns
$colsStmt = $pdo->query("DESCRIBE `exam_configs`");
$columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

assertCheck(in_array('duration', $columns), "2. Column 'duration' exists");
assertCheck(in_array('random_questions', $columns), "3. Column 'random_questions' exists");
assertCheck(in_array('random_options', $columns), "4. Column 'random_options' exists");
assertCheck(in_array('start_time', $columns), "5. Column 'start_time' exists");
assertCheck(in_array('end_time', $columns), "6. Column 'end_time' exists");
assertCheck(in_array('token', $columns), "7. Column 'token' exists");
assertCheck(in_array('token_secret', $columns), "8. Column 'token_secret' exists");
assertCheck(in_array('is_active', $columns), "9. Column 'is_active' exists");

// Test ExamConfig Model Methods
$model = new ExamConfig();
$config = $model->getActiveConfig();
assertCheck(!empty($config) && isset($config['id']), "10. ExamConfig::getActiveConfig() returns active configuration", "ID: {$config['id']}");
assertCheck((int)$config['duration'] > 0, "11. Config duration is valid integer", "Duration: {$config['duration']}m");
assertCheck(!empty($config['token']), "12. Config token is non-empty", "Token: {$config['token']}");

// Test Token Verification
$testToken = $config['token'];
assertCheck($model->verifyToken($testToken), "13. verifyToken() accepts correct token");
assertCheck($model->verifyToken(strtolower($testToken)), "14. verifyToken() is case-insensitive");
assertCheck(!$model->verifyToken('WRONG_TOKEN_XYZ'), "15. verifyToken() rejects invalid token");

// Test Token Generator
$newToken = ExamConfig::generateToken(6);
assertCheck(strlen($newToken) === 6 && ctype_alnum($newToken), "16. ExamConfig::generateToken() produces 6-char alphanumeric token", "Token: {$newToken}");

// -------------------------------------------------------------------------
// SECTION 2: SECRET CREDENTIALS STORAGE IN .ENV
// -------------------------------------------------------------------------
echo "\n2. TESTING SECRET CREDENTIALS PERSISTENCE IN .ENV...\n";
Env::load();

$initialSecret = env('EXAM_TOKEN_SECRET_KEY');
assertCheck(!empty($initialSecret), "17. env('EXAM_TOKEN_SECRET_KEY') is loaded from .env", "Value: {$initialSecret}");

// Test updating .env dynamically
$newSecret = 'exam_sec_test_' . bin2hex(random_bytes(4));
$envUpdated = Env::set('EXAM_TOKEN_SECRET_KEY', $newSecret);
assertCheck($envUpdated, "18. Env::set('EXAM_TOKEN_SECRET_KEY', ...) succeeded");

// Reload and verify
$verifySecret = env('EXAM_TOKEN_SECRET_KEY');
assertCheck($verifySecret === $newSecret, "19. .env file content reflects updated secret key", "Saved: {$verifySecret}");

// Restore original or clean secret
$finalSecret = 'exam_sec_f98c21a084eb73';
Env::set('EXAM_TOKEN_SECRET_KEY', $finalSecret);
assertCheck(env('EXAM_TOKEN_SECRET_KEY') === $finalSecret, "20. Restored standard EXAM_TOKEN_SECRET_KEY in .env");

// -------------------------------------------------------------------------
// SECTION 3: ROLE-BASED ACCESS CONTROL (MIDDLEWARE GUARD)
// -------------------------------------------------------------------------
echo "\n3. TESTING ROLE-BASED MIDDLEWARE GUARD (SUPER_ADMIN & GURU ONLY)...\n";

Session::start();
$middleware = new RoleMiddleware(['SUPER_ADMIN', 'GURU']);

// Test A: SUPER_ADMIN User (Allowed)
Session::set('user', [
    'id' => 1,
    'name' => 'Super Administrator',
    'username' => 'admin',
    'role' => 'SUPER_ADMIN'
]);
$req = new Request();
$adminAllowed = true;
try {
    $middleware->handle($req);
} catch (\Throwable $e) {
    $adminAllowed = false;
}
assertCheck($adminAllowed && http_response_code() !== 403, "21. SUPER_ADMIN is permitted by RoleMiddleware");

// Test B: GURU User (Allowed)
http_response_code(200);
Session::set('user', [
    'id' => 2,
    'name' => 'Budi Santoso, M.Pd.',
    'username' => 'guru1',
    'role' => 'GURU'
]);
$guruAllowed = true;
try {
    $middleware->handle($req);
} catch (\Throwable $e) {
    $guruAllowed = false;
}
assertCheck($guruAllowed && http_response_code() !== 403, "22. GURU is permitted by RoleMiddleware");

// Test C: PESERTA User (Blocked with 403 Forbidden)
http_response_code(200);
Session::set('user', [
    'id' => 4,
    'name' => 'Ahmad Pratama',
    'username' => 'siswa1',
    'role' => 'PESERTA'
]);

// Mock Ajax request to capture 403 cleanly
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$ajaxReq = new Request();
ob_start();
$middleware->handle($ajaxReq);
$forbiddenOutput = ob_get_clean();
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

$is403 = (http_response_code() === 403);
assertCheck($is403, "23. PESERTA is blocked with HTTP 403 Forbidden by RoleMiddleware");
assertCheck(str_contains($forbiddenOutput, 'PESERTA'), "24. 403 response indicates role PESERTA is unauthorized");

// -------------------------------------------------------------------------
// SECTION 4: CONTROLLER LOGIC & UPDATE PERSISTENCE
// -------------------------------------------------------------------------
echo "\n4. TESTING CONTROLLER LOGIC & CONFIG UPDATE...\n";
http_response_code(200);

// Setup Admin session & valid CSRF
Session::set('user', [
    'id' => 1,
    'name' => 'Super Administrator',
    'username' => 'admin',
    'role' => 'SUPER_ADMIN'
]);
$validCsrf = csrf_token();

// Test Model update
$updateSuccess = $model->saveConfig([
    'title' => 'Ujian Akhir Semester Ganjil 2026',
    'duration' => 90,
    'random_questions' => 1,
    'random_options' => 1,
    'start_time' => '2026-10-25 08:00:00',
    'end_time' => '2026-10-25 12:00:00',
    'token' => 'ULINE99X',
    'token_secret' => 'exam_sec_f98c21a084eb73',
    'is_active' => 1
]);
assertCheck($updateSuccess, "25. ExamConfig::saveConfig() persisted new parameters");

$updated = $model->getActiveConfig();
assertCheck($updated['title'] === 'Ujian Akhir Semester Ganjil 2026', "26. Saved title matches updated value");
assertCheck((int)$updated['duration'] === 90, "27. Saved duration is 90 minutes");
assertCheck($updated['token'] === 'ULINE99X', "28. Saved token is ULINE99X");
assertCheck((int)$updated['random_questions'] === 1, "29. Saved random_questions is 1");
assertCheck((int)$updated['random_options'] === 1, "30. Saved random_options is 1");

// Test View File Exists
$viewFile = dirname(__DIR__) . '/app/Views/admin/exam_config/index.php';
assertCheck(file_exists($viewFile), "31. View template 'app/Views/admin/exam_config/index.php' exists");

$viewCode = file_get_contents($viewFile);
assertCheck(str_contains($viewCode, 'toast') && str_contains($viewCode, 'saveToast'), "32. View contains Bootstrap 5 Toast notification element");
assertCheck(str_contains($viewCode, 'cfg_duration') && str_contains($viewCode, 'cfg_token'), "33. View contains duration and token form controls");
assertCheck(str_contains($viewCode, 'cfg_token_secret'), "34. View contains .env secret credential control");

echo "\n====================================================================\n";
echo "EXAM CONFIG TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================================\n";

exit($failCount > 0 ? 1 : 0);
