<?php

declare(strict_types=1);

$port = $argv[1] ?? '8099';
$baseUrl = "http://127.0.0.1:{$port}";

$cookieGuruBudi = tempnam(sys_get_temp_dir(), 'guru_budi_');
$cookieGuruSiti = tempnam(sys_get_temp_dir(), 'guru_siti_');
$cookieAhmad    = tempnam(sys_get_temp_dir(), 'siswa_ahmad_');

function makeRequest(string $url, string $method = 'GET', $data = [], string $cookieFile = '', bool $isJson = false): array
{
    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ];

    if ($cookieFile) {
        $options[CURLOPT_COOKIEJAR] = $cookieFile;
        $options[CURLOPT_COOKIEFILE] = $cookieFile;
    }

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        if ($isJson) {
            $payload = is_string($data) ? $data : json_encode($data);
            $options[CURLOPT_POSTFIELDS] = $payload;
            $options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ];
        } else {
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        }
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $header = substr((string)$response, 0, $headerSize);
    $body = substr((string)$response, $headerSize);

    return [
        'code' => $httpCode,
        'header' => $header,
        'body' => $body
    ];
}

function loginUser(string $baseUrl, string $identifier, string $password, string $cookieFile): bool {
    $loginPage = makeRequest("{$baseUrl}/login", "GET", [], $cookieFile);
    preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $loginPage['body'], $m);
    $csrf = $m[1] ?? '';

    $loginResp = makeRequest("{$baseUrl}/login", "POST", [
        '_csrf_token' => $csrf,
        'identifier' => $identifier,
        'password' => $password
    ], $cookieFile);

    return $loginResp['code'] === 302;
}

echo "========================================================\n";
echo "  E2E HTTP AI PROCTORING & MONITORING FLOW TEST\n";
echo "========================================================\n\n";

$pass = 0;
$total = 0;

function assertHttp(bool $condition, string $msg, $debug = null): void {
    global $pass, $total;
    $total++;
    if ($condition) {
        $pass++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
        if ($debug !== null) {
            echo "         DEBUG CODE: " . ($debug['code'] ?? '') . "\n";
            echo "         DEBUG BODY: " . substr(strip_tags((string)($debug['body'] ?? '')), 0, 200) . "\n";
        }
    }
}

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
$db = App\Core\Database::getConnection();

// Reset session status and insert mock events for clean e2e verification
$examId = 1;
$sessionRow = $db->query("SELECT id FROM exam_sessions WHERE exam_id = 1 AND student_id = 1 ORDER BY id DESC LIMIT 1")->fetch();
if (!$sessionRow) {
    $db->exec("INSERT INTO exam_sessions (exam_id, student_id, started_at, status, remaining_seconds) VALUES (1, 1, NOW(), 'started', 3600)");
    $sessionId = (int)$db->lastInsertId();
} else {
    $sessionId = (int)$sessionRow['id'];
    $db->exec("UPDATE exam_sessions SET status = 'started', started_at = NOW(), finished_at = NULL WHERE id = {$sessionId}");
}
$db->prepare("DELETE FROM proctor_events WHERE exam_session_id = :sid")->execute([':sid' => $sessionId]);

// 1. Student Ahmad Logs In and sends Proctoring Events
$ahmadLogged = loginUser($baseUrl, 'siswa_ahmad', 'password123', $cookieAhmad);
assertHttp($ahmadLogged, "1. Siswa Ahmad login successful (302 Redirect)");

// Log FACE_NOT_DETECTED
$pEv1 = makeRequest("{$baseUrl}/peserta/exams/proctor-event", "POST", [
    'session_id' => $sessionId,
    'camera_type' => 'primary',
    'event_type' => 'FACE_NOT_DETECTED',
    'confidence' => 0.92,
    'metadata' => ['reason' => 'Wajah tidak terdeteksi 6 detik']
], $cookieAhmad, true);
assertHttp($pEv1['code'] === 200, "2. POST /peserta/exams/proctor-event (FACE_NOT_DETECTED) returns 200 OK", $pEv1);
assertHttp(strpos($pEv1['body'], '"status":"success"') !== false, "3. Event 1 JSON response status is success");

// Log MULTIPLE_FACES
$pEv2 = makeRequest("{$baseUrl}/peserta/exams/proctor-event", "POST", [
    'session_id' => $sessionId,
    'camera_type' => 'primary',
    'event_type' => 'MULTIPLE_FACES',
    'confidence' => 0.97,
    'metadata' => ['faces_count' => 2, 'reason' => 'Terdeteksi 2 wajah di layar']
], $cookieAhmad, true);
assertHttp($pEv2['code'] === 200, "4. POST /peserta/exams/proctor-event (MULTIPLE_FACES) returns 200 OK", $pEv2);

// Log TAB_SWITCHED
$pEv3 = makeRequest("{$baseUrl}/peserta/exams/proctor-event", "POST", [
    'session_id' => $sessionId,
    'camera_type' => 'primary',
    'event_type' => 'TAB_SWITCHED',
    'confidence' => 0.90,
    'metadata' => ['reason' => 'Peserta beralih ke tab browser lain']
], $cookieAhmad, true);
assertHttp($pEv3['code'] === 200, "5. POST /peserta/exams/proctor-event (TAB_SWITCHED) returns 200 OK", $pEv3);

// 2. Guru Budi Logs In (Exam Owner)
$guruBudiLogged = loginUser($baseUrl, 'guru_budi', 'password123', $cookieGuruBudi);
assertHttp($guruBudiLogged, "6. Guru Budi login successful (302 Redirect)");

// 3. Access Monitoring Overview (/guru/monitoring)
$monIndex = makeRequest("{$baseUrl}/guru/monitoring", "GET", [], $cookieGuruBudi);
assertHttp($monIndex['code'] === 200, "7. GET /guru/monitoring returns 200 OK", $monIndex);
assertHttp(strpos($monIndex['body'], 'Monitoring & AI Proctoring') !== false, "8. Monitoring overview page title verified");
assertHttp(strpos($monIndex['body'], 'Pantau Sesi') !== false, "9. Exam row action button 'Pantau Sesi' present");

// 4. Access Exam Monitoring Sessions (/guru/monitoring/{exam_id})
$monExam = makeRequest("{$baseUrl}/guru/monitoring/{$examId}", "GET", [], $cookieGuruBudi);
assertHttp($monExam['code'] === 200, "10. GET /guru/monitoring/{$examId} returns 200 OK", $monExam);
assertHttp(strpos($monExam['body'], 'Ahmad Pratama') !== false, "11. Student Ahmad Pratama displayed in sessions list");
assertHttp(strpos($monExam['body'], 'Risk Score') !== false || strpos($monExam['body'], 'Rendah') !== false, "12. Risk score displayed in table");
assertHttp(strpos($monExam['body'], 'Monitoring') !== false && strpos($monExam['body'], 'Review') !== false, "13. 'Monitoring' & 'Review' action buttons present");

// 5. Access Session Timeline Detail (/guru/monitoring/{exam_id}/{session_id})
$monDetail = makeRequest("{$baseUrl}/guru/monitoring/{$examId}/{$sessionId}", "GET", [], $cookieGuruBudi);
assertHttp($monDetail['code'] === 200, "14. GET /guru/monitoring/{$examId}/{$sessionId} returns 200 OK", $monDetail);
assertHttp(strpos($monDetail['body'], 'Ahmad Pratama') !== false, "15. Session detail title verified");
assertHttp(strpos($monDetail['body'], 'Wajah Tidak Terdeteksi') !== false, "16. Timeline contains FACE_NOT_DETECTED event");
assertHttp(strpos($monDetail['body'], 'Terdeteksi Lebih Dari Satu Wajah') !== false, "17. Timeline contains MULTIPLE_FACES event");
assertHttp(strpos($monDetail['body'], 'Berpindah Tab') !== false, "18. Timeline contains TAB_SWITCHED event");
assertHttp(strpos($monDetail['body'], 'Catatan & Verifikasi') !== false, "19. Teacher review form present in detail view");

// 6. Guru Budi Submits Review
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $monDetail['body'], $m);
$csrfReview = $m[1] ?? '';

$reviewPost = makeRequest("{$baseUrl}/guru/monitoring/review", "POST", [
    '_csrf_token' => $csrfReview,
    'exam_id' => $examId,
    'session_id' => $sessionId,
    'review_status' => 'reviewed',
    'review_note' => 'E2E Test Note: Siswa terpantau bergerak wajar, kendala tab berpindah telah dikonfirmasi.'
], $cookieGuruBudi);
assertHttp($reviewPost['code'] === 302, "20. POST /guru/monitoring/review returns 302 Redirect", $reviewPost);

// Verify detail page now reflects review status
$monDetailAfter = makeRequest("{$baseUrl}/guru/monitoring/{$examId}/{$sessionId}", "GET", [], $cookieGuruBudi);
assertHttp(strpos($monDetailAfter['body'], 'Reviewed') !== false || strpos($monDetailAfter['body'], 'Telah Direviu') !== false, "21. Detail page shows 'Reviewed' status");

// 7. Cross-Teacher Isolation Security Check (Guru Siti cannot view Guru Budi's Exam Monitoring)
$guruSitiLogged = loginUser($baseUrl, 'guru_siti', 'password123', $cookieGuruSiti);
assertHttp($guruSitiLogged, "22. Guru Siti login successful");

$crossExamResp = makeRequest("{$baseUrl}/guru/monitoring/{$examId}", "GET", [], $cookieGuruSiti);
assertHttp($crossExamResp['code'] === 302 || $crossExamResp['code'] === 403, "23. Cross-teacher exam monitoring access blocked (Redirect/403)", $crossExamResp);

// 8. Role-Based Access Security Check: Student Ahmad cannot access Guru Monitoring
$studentGuruAccess = makeRequest("{$baseUrl}/guru/monitoring", "GET", [], $cookieAhmad);
assertHttp($studentGuruAccess['code'] === 302 || $studentGuruAccess['code'] === 403, "24. Peserta access to /guru/monitoring blocked (RBAC)", $studentGuruAccess);

echo "\n========================================================\n";
echo "  E2E MONITORING TEST SUMMARY: {$pass} / {$total} PASSED\n";
echo "========================================================\n";

@unlink($cookieGuruBudi);
@unlink($cookieGuruSiti);
@unlink($cookieAhmad);

if ($pass === $total) {
    echo "SUCCESS: ALL E2E AI PROCTORING & MONITORING TESTS PASSED!\n";
    exit(0);
} else {
    echo "ERROR: SOME E2E TESTS FAILED.\n";
    exit(1);
}
