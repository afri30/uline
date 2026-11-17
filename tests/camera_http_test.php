<?php

declare(strict_types=1);

$port = $argv[1] ?? '8099';
$baseUrl = "http://127.0.0.1:{$port}";

$cookieFileAhmad = tempnam(sys_get_temp_dir(), 'ahmad_cam_');
$cookieFileDewi = tempnam(sys_get_temp_dir(), 'dewi_cam_');

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
echo "  E2E HTTP WEBRTC DUAL CAMERA & EVENT LOGGING TEST\n";
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
$sessionRow = $db->query("SELECT id FROM exam_sessions WHERE exam_id = 1 AND student_id = 1 ORDER BY id DESC LIMIT 1")->fetch();
if (!$sessionRow) {
    $db->exec("INSERT INTO exam_sessions (exam_id, student_id, started_at, status, remaining_seconds) VALUES (1, 1, NOW(), 'started', 3600)");
    $sessionId = (int)$db->lastInsertId();
} else {
    $sessionId = (int)$sessionRow['id'];
    $db->exec("UPDATE exam_sessions SET status = 'started', started_at = NOW(), finished_at = NULL WHERE id = {$sessionId}");
}

// 1. Login as Siswa Ahmad
$ahmadLogged = loginUser($baseUrl, 'siswa_ahmad', 'password123', $cookieFileAhmad);
assertHttp($ahmadLogged, "1. Siswa Ahmad login successful (302 Redirect)");

// 2. Access Exam Room (Session ID)
$roomResp = makeRequest("{$baseUrl}/peserta/exams/room?session_id={$sessionId}", "GET", [], $cookieFileAhmad);
assertHttp($roomResp['code'] === 200, "2. /peserta/exams/room?session_id={$sessionId} returns 200 OK", $roomResp);
assertHttp(strpos($roomResp['body'], 'id="videoCamera1"') !== false, "3. Room HTML contains videoCamera1 element");
assertHttp(strpos($roomResp['body'], 'id="videoCamera2"') !== false, "4. Room HTML contains videoCamera2 element");
assertHttp(strpos($roomResp['body'], 'id="cam1StatusBadge"') !== false, "5. Room HTML contains cam1StatusBadge");
assertHttp(strpos($roomResp['body'], 'id="cam2StatusBadge"') !== false, "6. Room HTML contains cam2StatusBadge");
assertHttp(strpos($roomResp['body'], 'id="cameraWarningBanner"') !== false, "7. Room HTML contains cameraWarningBanner");
assertHttp(strpos($roomResp['body'], 'initDualCameraSystem()') !== false, "8. Room JavaScript invokes initDualCameraSystem()");

// 3. Test POST /peserta/exams/camera-event (Camera 1 Connected)
$ev1Resp = makeRequest("{$baseUrl}/peserta/exams/camera-event", "POST", [
    'session_id' => $sessionId,
    'camera_type' => 'camera_1',
    'event_type' => 'connected',
    'metadata' => [
        'deviceId' => 'integrated_webcam_01',
        'label' => 'HD Web Camera'
    ]
], $cookieFileAhmad, true);

assertHttp($ev1Resp['code'] === 200, "9. POST /peserta/exams/camera-event (Cam 1 Connected) returns 200 OK", $ev1Resp);
assertHttp(strpos($ev1Resp['body'], '"status":"success"') !== false, "10. Event 1 JSON response status is success");

// 4. Test POST /peserta/exams/camera-event (Camera 2 Fallback)
$ev2Resp = makeRequest("{$baseUrl}/peserta/exams/camera-event", "POST", [
    'session_id' => $sessionId,
    'camera_type' => 'camera_2',
    'event_type' => 'single_camera_fallback',
    'metadata' => [
        'reason' => 'Hanya 1 kamera video terdeteksi pada perangkat ini.'
    ]
], $cookieFileAhmad, true);

assertHttp($ev2Resp['code'] === 200, "11. POST /peserta/exams/camera-event (Cam 2 Fallback) returns 200 OK", $ev2Resp);
assertHttp(strpos($ev2Resp['body'], '"status":"success"') !== false, "12. Event 2 JSON response status is success");

// 5. Test POST /peserta/exams/camera-event (Camera 1 Disconnect event)
$ev3Resp = makeRequest("{$baseUrl}/peserta/exams/camera-event", "POST", [
    'session_id' => $sessionId,
    'camera_type' => 'camera_1',
    'event_type' => 'disconnected',
    'metadata' => [
        'reason' => 'Kabel webcam terputus (track onended)'
    ]
], $cookieFileAhmad, true);

assertHttp($ev3Resp['code'] === 200, "13. POST /peserta/exams/camera-event (Cam 1 Disconnect) returns 200 OK", $ev3Resp);
assertHttp(strpos($ev3Resp['body'], '"event_id"') !== false, "14. Event 3 returns event_id in payload");

// 6. Security Test: Siswa Dewi attempting to log event to Ahmad's session
$dewiLogged = loginUser($baseUrl, 'siswa_dewi', 'password123', $cookieFileDewi);
assertHttp($dewiLogged, "15. Siswa Dewi login successful");

$dewiCross = makeRequest("{$baseUrl}/peserta/exams/camera-event", "POST", [
    'session_id' => $sessionId, // Ahmad's session
    'camera_type' => 'camera_1',
    'event_type' => 'disconnected'
], $cookieFileDewi, true);

assertHttp($dewiCross['code'] === 403, "16. Cross-session camera event blocked with 403 Forbidden", $dewiCross);

echo "\n========================================================\n";
echo "  E2E CAMERA TEST SUMMARY: {$pass} / {$total} PASSED\n";
echo "========================================================\n";

@unlink($cookieFileAhmad);
@unlink($cookieFileDewi);

if ($pass === $total) {
    echo "SUCCESS: ALL E2E DUAL CAMERA TESTS PASSED!\n";
    exit(0);
} else {
    echo "ERROR: SOME E2E DUAL CAMERA TESTS FAILED.\n";
    exit(1);
}
