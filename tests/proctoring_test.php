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

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Models\ProctorEvent;
use App\Models\ExamSession;
use App\Models\Exam;
use App\Models\CameraEvent;
use App\Services\Proctoring\RiskScoringService;
use App\Services\Proctoring\DetectionService;
use App\Services\Proctoring\ProctoringService;

echo "========================================================\n";
echo "AI PROCTORING & RISK SCORING SERVICE UNIT TESTS\n";
echo "========================================================\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $testName\n";
        $passCount++;
    } else {
        echo "[FAIL] $testName\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// 1. RISK SCORING SERVICE TESTS
// -------------------------------------------------------------
echo "\n--- 1. Testing RiskScoringService ---\n";
$riskScorer = new RiskScoringService();

// Empty events -> score 0 (LOW)
$risk0 = $riskScorer->calculateRiskScore([]);
assertTest($risk0['score'] === 0, "Empty events gives score 0");
assertTest($risk0['level'] === 'LOW', "Score 0 is categorized as LOW");
assertTest($risk0['color'] === 'success', "LOW category has 'success' color");

// 1 INFO (+1), 2 WARNING (+10), 1 HIGH (+15) -> Total = 26 (MEDIUM)
$mockEvents = [
    ['severity' => 'INFO'],
    ['severity' => 'WARNING'],
    ['severity' => 'WARNING'],
    ['severity' => 'HIGH']
];
$risk26 = $riskScorer->calculateRiskScore($mockEvents);
assertTest($risk26['score'] === 26, "Score correctly sums to 26 (1 + 5 + 5 + 15)");
assertTest($risk26['level'] === 'MEDIUM', "Score 26 is categorized as MEDIUM");
assertTest($risk26['color'] === 'warning', "MEDIUM category has 'warning' color");

// 4 HIGH events (+60) -> Total = 60 (HIGH)
$highMockEvents = [
    ['severity' => 'HIGH'],
    ['severity' => 'HIGH'],
    ['severity' => 'HIGH'],
    ['severity' => 'HIGH']
];
$risk60 = $riskScorer->calculateRiskScore($highMockEvents);
assertTest($risk60['score'] === 60, "Score correctly sums to 60 (4 x 15)");
assertTest($risk60['level'] === 'HIGH', "Score 60 is categorized as HIGH");
assertTest($risk60['color'] === 'danger', "HIGH category has 'danger' color");

// Breakdown counts
$breakdown = $riskScorer->getSeverityBreakdown($mockEvents);
assertTest($breakdown['high'] === 1 && $breakdown['warning'] === 2 && $breakdown['info'] === 1, "Breakdown counts match event distribution");

// -------------------------------------------------------------
// 2. DETECTION SERVICE TESTS
// -------------------------------------------------------------
echo "\n--- 2. Testing DetectionService ---\n";
$detector = new DetectionService();

assertTest($detector->isValidEventType('FACE_NOT_DETECTED'), "FACE_NOT_DETECTED is a valid event");
assertTest($detector->isValidEventType('MULTIPLE_FACES'), "MULTIPLE_FACES is a valid event");
assertTest($detector->isValidEventType('CAMERA_DISCONNECTED'), "CAMERA_DISCONNECTED is a valid event");
assertTest($detector->isValidEventType('LOOKING_AWAY'), "LOOKING_AWAY is a valid event");
assertTest($detector->isValidEventType('SUSPICIOUS_ACTIVITY'), "SUSPICIOUS_ACTIVITY is a valid event");
assertTest($detector->isValidEventType('TAB_SWITCHED'), "TAB_SWITCHED is a valid event");
assertTest(!$detector->isValidEventType('INVALID_RANDOM_EVENT'), "Random event type is invalid");

assertTest($detector->getDefaultSeverity('MULTIPLE_FACES') === 'HIGH', "MULTIPLE_FACES has default severity HIGH");
assertTest($detector->getDefaultSeverity('FACE_NOT_DETECTED') === 'WARNING', "FACE_NOT_DETECTED has default severity WARNING");
assertTest($detector->getDefaultSeverity('CAMERA_DISCONNECTED') === 'HIGH', "CAMERA_DISCONNECTED has default severity HIGH");

// Rule evaluation: Face not detected for 8 seconds -> WARNING
$eval1 = $detector->evaluateHeuristic('face_count', ['count' => 0, 'duration_seconds' => 8]);
assertTest($eval1 !== null && $eval1['event_type'] === 'FACE_NOT_DETECTED', "Zero face for 8s triggers FACE_NOT_DETECTED");

// Rule evaluation: Multiple faces (2) -> HIGH
$eval2 = $detector->evaluateHeuristic('face_count', ['count' => 2]);
assertTest($eval2 !== null && $eval2['event_type'] === 'MULTIPLE_FACES' && $eval2['severity'] === 'HIGH', "2 faces triggers MULTIPLE_FACES with HIGH severity");

// Rule evaluation: Normal face (1) -> null
$eval3 = $detector->evaluateHeuristic('face_count', ['count' => 1]);
assertTest($eval3 === null, "1 face returns null (no suspicious event)");

// Rule evaluation: Tab switched -> WARNING
$eval4 = $detector->evaluateHeuristic('visibility', ['tab_hidden' => true]);
assertTest($eval4 !== null && $eval4['event_type'] === 'TAB_SWITCHED', "Tab hidden triggers TAB_SWITCHED");

// -------------------------------------------------------------
// 3. PROCTOR EVENT MODEL & PROCTORING SERVICE INTEGRATION
// -------------------------------------------------------------
echo "\n--- 3. Testing ProctorEvent Model & ProctoringService ---\n";
$proctorEventModel = new ProctorEvent();
$proctorService = new ProctoringService();
$sessionModel = new ExamSession();

// Find an existing session or pick session ID 1
$db = \App\Core\Database::getConnection();
$stmt = $db->query("SELECT id FROM exam_sessions ORDER BY id ASC LIMIT 1");
$testSession = $stmt->fetch();

if (!$testSession) {
    // Create a dummy session for test
    $examStmt = $db->query("SELECT id FROM exams LIMIT 1");
    $testExam = $examStmt->fetch();
    $studentStmt = $db->query("SELECT id FROM students LIMIT 1");
    $testStudent = $studentStmt->fetch();
    
    if ($testExam && $testStudent) {
        $testSession = $sessionModel->getOrCreateSession((int)$testExam['id'], (int)$testStudent['id'], 60);
    }
}

$sessionId = (int)($testSession['id'] ?? 1);
echo "Using Session ID $sessionId for database persistence testing...\n";

// Clear existing events for clean test run
$db->prepare("DELETE FROM proctor_events WHERE exam_session_id = :sid")->execute([':sid' => $sessionId]);

// Log 3 events via ProctoringService
$res1 = $proctorService->recordSuspiciousEvent(
    $sessionId,
    'primary',
    'FACE_NOT_DETECTED',
    0.92,
    ['duration_seconds' => 7, 'reason' => 'Wajah peserta tidak terdeteksi di kamera selama 7 detik'],
    'evidence/test_face_not_detected.jpg'
);
assertTest($res1['event_id'] > 0, "Event 1 (FACE_NOT_DETECTED) logged with valid ID");
assertTest($res1['severity'] === 'WARNING', "Event 1 severity is WARNING");
assertTest($res1['current_risk_score']['score'] === 5, "Risk score after Event 1 is 5");

$res2 = $proctorService->recordSuspiciousEvent(
    $sessionId,
    'primary',
    'LOOKING_AWAY',
    0.84,
    ['direction' => 'left_bottom', 'reason' => 'Pandangan peserta konsisten menoleh ke arah kiri bawah'],
    ''
);
assertTest($res2['event_id'] > 0, "Event 2 (LOOKING_AWAY) logged with valid ID");
assertTest($res2['current_risk_score']['score'] === 10, "Risk score after Event 2 is 10 (5 + 5)");

$res3 = $proctorService->recordSuspiciousEvent(
    $sessionId,
    'primary',
    'MULTIPLE_FACES',
    0.97,
    ['faces_detected' => 2, 'reason' => 'Terdeteksi 2 wajah berbeda dalam frame kamera peserta'],
    'evidence/test_multi_face.jpg'
);
assertTest($res3['event_id'] > 0, "Event 3 (MULTIPLE_FACES) logged with valid ID");
assertTest($res3['current_risk_score']['score'] === 25, "Risk score after Event 3 is 25 (10 + 15, MEDIUM)");
assertTest($res3['current_risk_score']['level'] === 'MEDIUM', "Risk level is MEDIUM");

// Add 2 more HIGH events to push risk score into HIGH (>50)
$proctorService->recordSuspiciousEvent($sessionId, 'secondary', 'CAMERA_DISCONNECTED', 0.95, ['reason' => 'Kamera terputus']);
$res5 = $proctorService->recordSuspiciousEvent($sessionId, 'primary', 'SUSPICIOUS_ACTIVITY', 0.90, ['reason' => 'Pola gerak anomali']);
assertTest($res5['current_risk_score']['score'] === 45, "Risk score updated correctly (25 + 15 + 5 = 45)");

// Fetch events by session
$events = $proctorEventModel->getBySessionId($sessionId);
assertTest(count($events) === 5, "getBySessionId returned exactly 5 events");

// Check teacher review update
$reviewSuccess = $proctorEventModel->reviewAllForSession($sessionId, "Terkonfirmasi kendala jaringan dan pergerakan wajar siswa.");
assertTest($reviewSuccess, "reviewAllForSession executed successfully");

$reviewedEvents = $proctorEventModel->getBySessionId($sessionId);
$allReviewed = true;
foreach ($reviewedEvents as $revEv) {
    if (!$revEv['reviewed'] || $revEv['review_note'] === null) {
        $allReviewed = false;
        break;
    }
}
assertTest($allReviewed, "All events marked as reviewed with review_note set");

echo "\n========================================================\n";
echo "SUMMARY: Passed: $passCount, Failed: $failCount\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
