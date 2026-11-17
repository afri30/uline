<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Models\AuditLog;
use App\Services\AuditLogger;

echo "=== AUDIT LOG TEST SUITE ===\n";

$db = Database::getConnection();
$auditModel = new AuditLog();

// 1. Test direct AuditLogger invocation
echo "1. Testing direct AuditLogger::log()... ";
$logId = AuditLogger::log(
    'TEST_ACTION',
    'Melakukan pengujian sistem audit log.',
    ['test_key' => 'test_value', 'timestamp' => time()],
    1,
    'SUPER_ADMIN',
    '127.0.0.1'
);

if ($logId > 0) {
    echo "[PASS] - Log ID: {$logId}\n";
} else {
    echo "[FAIL] - Failed to insert audit log\n";
    exit(1);
}

// 2. Test fetching recent logs
echo "2. Testing AuditLog::getRecent()... ";
$recent = $auditModel->getRecent(5);
if (!empty($recent) && $recent[0]['action'] === 'TEST_ACTION') {
    echo "[PASS] - Retrieved " . count($recent) . " logs. Latest action: " . $recent[0]['action'] . "\n";
} else {
    echo "[FAIL] - Latest log does not match TEST_ACTION\n";
    exit(1);
}

// 3. Test helper audit_log() with auth session simulation
echo "3. Testing helper audit_log()... ";
$_SESSION['user'] = [
    'id' => 2,
    'username' => 'guru_budi',
    'name' => 'Budi Santoso, S.Pd',
    'role' => 'GURU'
];

$helperLogId = audit_log('CREATE_EXAM', 'Guru membuat ujian baru: Ujian Matematika Bab 1', [
    'exam_id' => 999,
    'title' => 'Ujian Matematika Bab 1'
]);

if ($helperLogId > 0) {
    $inserted = $auditModel->find($helperLogId);
    $recentLog = $auditModel->getRecent(1)[0] ?? null;
    if ($inserted && $inserted['user_id'] == 2 && $recentLog && $recentLog['user_role'] === 'GURU') {
        echo "[PASS] - Helper recorded log with session user info (User ID: 2, Role: GURU). Log ID: {$helperLogId}\n";
    } else {
        echo "[FAIL] - User info mismatch in helper log\n";
        exit(1);
    }
} else {
    echo "[FAIL] - Helper audit_log returned 0\n";
    exit(1);
}

// 4. Test required audit actions validation
echo "4. Testing all required action types presence in database...\n";
$requiredActions = [
    'LOGIN',
    'LOGOUT',
    'CREATE_EXAM',
    'UPDATE_EXAM',
    'DELETE_EXAM',
    'START_EXAM',
    'SUBMIT_EXAM',
    'PROCTOR_EVENT',
    'REVIEW_EVENT'
];

foreach ($requiredActions as $act) {
    $id = audit_log($act, "Automated test for action {$act}", ['dummy' => true]);
    if ($id > 0) {
        echo "  [OK] Action logged: {$act}\n";
    } else {
        echo "  [FAIL] Failed to log: {$act}\n";
        exit(1);
    }
}

echo "\n=== ALL AUDIT LOG TESTS PASSED SUCCESSFULLY! ===\n";
