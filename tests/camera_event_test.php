<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/functions.php';

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

use App\Core\Database;
use App\Models\CameraEvent;
use App\Models\ExamSession;
use App\Models\Student;

echo "========================================================\n";
echo "  TESTING WEBRTC DUAL CAMERA EVENT LOGGING SYSTEM\n";
echo "========================================================\n\n";

$cameraModel = new CameraEvent();
$sessionModel = new ExamSession();
$studentModel = new Student();

$totalTests = 0;
$passedTests = 0;

function assertTest(bool $condition, string $testName): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName}\n";
    }
}

// 1. Check student & session
$student = $studentModel->findByUserIdWithClass(4); // Siswa Ahmad
assertTest($student !== false, "1. Student Ahmad found (ID: {$student['id']})");

$session = $sessionModel->getOrCreateSession(1, (int)$student['id'], 90);
assertTest(!empty($session['id']), "2. Active Exam Session exists (Session ID: {$session['id']})");

$sessionId = (int)$session['id'];

// 2. Test logging event for Camera 1 (Face Camera)
$ev1 = $cameraModel->logEvent($sessionId, 'camera_1', 'permission_granted');
assertTest($ev1 > 0, "3. Camera 1 'permission_granted' logged (Event ID: {$ev1})");

$ev2 = $cameraModel->logEvent($sessionId, 'camera_1', 'connected', ['deviceId' => 'cam1_mock_id_123', 'resolution' => '1280x720']);
assertTest($ev2 > 0, "4. Camera 1 'connected' with metadata logged (Event ID: {$ev2})");

// 3. Test logging event for Camera 2 (Desk/Environment Camera)
$ev3 = $cameraModel->logEvent($sessionId, 'camera_2', 'single_camera_fallback', ['reason' => 'Only 1 video device detected']);
assertTest($ev3 > 0, "5. Camera 2 'single_camera_fallback' logged (Event ID: {$ev3})");

// 4. Test disconnect event
$ev4 = $cameraModel->logEvent($sessionId, 'camera_1', 'disconnected', ['reason' => 'Webcam cable unplugged']);
assertTest($ev4 > 0, "6. Camera 1 'disconnected' logged (Event ID: {$ev4})");

// 5. Test retrieve events for session
$events = $cameraModel->getBySessionId($sessionId);
assertTest(count($events) >= 4, "7. getBySessionId returns all logged events (Count: " . count($events) . ")");

$hasParsedMetadata = false;
foreach ($events as $e) {
    if ($e['event_type'] === 'connected' && isset($e['metadata_parsed']['deviceId'])) {
        $hasParsedMetadata = true;
        break;
    }
}
assertTest($hasParsedMetadata, "8. JSON metadata is parsed correctly");

// 6. Test disconnection count
$disconnectCount = $cameraModel->getDisconnectionCount($sessionId);
assertTest($disconnectCount >= 1, "9. getDisconnectionCount returns count >= 1 (Actual: {$disconnectCount})");

// 7. Test latest status by camera
$latestStatus = $cameraModel->getLatestStatusByCamera($sessionId);
assertTest(isset($latestStatus['camera_1']) && isset($latestStatus['camera_2']), "10. getLatestStatusByCamera returns entries for camera_1 and camera_2");
assertTest($latestStatus['camera_1']['event_type'] === 'disconnected', "11. Latest event for camera_1 is 'disconnected'");
assertTest($latestStatus['camera_2']['event_type'] === 'single_camera_fallback', "12. Latest event for camera_2 is 'single_camera_fallback'");

echo "\n========================================================\n";
echo "  TEST SUMMARY: {$passedTests} / {$totalTests} PASSED\n";
echo "========================================================\n";

if ($passedTests === $totalTests) {
    echo "SUCCESS: ALL CAMERA EVENT TESTS PASSED!\n";
    exit(0);
} else {
    echo "ERROR: SOME TESTS FAILED.\n";
    exit(1);
}
