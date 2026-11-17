<?php

declare(strict_types=1);

$port = $argv[1] ?? '8099';
$baseUrl = "http://127.0.0.1:{$port}";

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

use App\Services\Proctoring\CameraStreamService;
use App\Core\Database;

$pass = 0;
$fail = 0;

function check(bool $cond, string $msg, string $debug = ''): void {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [PASS] {$msg}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$msg}\n";
        if ($debug !== '') {
            echo "         DEBUG: {$debug}\n";
        }
    }
}

function httpReq(string $url, string $method = 'GET', $data = [], string $cookieFile = '', bool $isJson = false): array {
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ];
    if ($cookieFile) {
        $opts[CURLOPT_COOKIEJAR] = $cookieFile;
        $opts[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($isJson) {
            $opts[CURLOPT_POSTFIELDS] = is_string($data) ? $data : json_encode($data);
            $opts[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ];
        } else {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($data);
        }
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $hdrSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $code,
        'header' => substr((string)$resp, 0, $hdrSize),
        'body' => substr((string)$resp, $hdrSize),
    ];
}

function extractCsrf(string $html): string {
    if (preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/csrfToken:\s*[\'"]([^\'"]+)[\'"]/', $html, $m)) {
        return $m[1];
    }
    return '';
}

function loginAs(string $baseUrl, string $identifier, string $password, string $cookieFile): bool {
    $page = httpReq("{$baseUrl}/login", 'GET', [], $cookieFile);
    $csrf = extractCsrf($page['body']);
    $res = httpReq("{$baseUrl}/login", 'POST', [
        '_csrf_token' => $csrf,
        'identifier' => $identifier,
        'password' => $password
    ], $cookieFile);
    return $res['code'] === 302 && !preg_match('/Location:.*\/login\b/i', $res['header']);
}

// Create minimal valid JPEG binary >= 100 bytes
$dummyJpegBytes = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00\x48\x00\x48\x00\x00" . str_repeat("\x00", 80) . "\xFF\xD9";
$dummyJpegDataUri = 'data:image/jpeg;base64,' . base64_encode($dummyJpegBytes);

echo "========================================================================\n";
echo "  E2E SUITE: LEFT-MENU PROFILES + CAMERA GURU & MURID (CHECK & EXAM)    \n";
echo "========================================================================\n\n";

// -----------------------------------------------------------------------------
// SECTION 1: CameraStreamService Unit Security & Storage Checks
// -----------------------------------------------------------------------------
echo "--- SECTION 1: CameraStreamService Unit Tests ---\n";
$tmpStorage = sys_get_temp_dir() . '/uline_cam_test_' . uniqid();
$camService = new CameraStreamService($tmpStorage);

$savedFrame = $camService->saveBase64Snapshot($dummyJpegDataUri, 'session', 999, 'camera_1');
check(file_exists($savedFrame['path']), "1.1 JPEG camera frame saved to storage");
check($camService->getFrameAgeSeconds('session', 999, 'camera_1') !== null, "1.2 Live frame age lookup succeeds");

$rejected = false;
try {
    $camService->saveBase64Snapshot('data:image/jpeg;base64,' . base64_encode('<?php echo "hack"; ?>' . str_repeat('x', 80)), 'session', 999, 'camera_1');
} catch (\Throwable $e) {
    $rejected = true;
}
check($rejected, "1.3 Non-JPEG disguised PHP payload strictly rejected by magic byte check");
check($camService->getEvidenceFilePath('../../config/database.php') === null, "1.4 Directory traversal prevented in getEvidenceFilePath");

$evName = $camService->copyLiveFrameToEvidence(999, 'camera_1', 'check');
check($evName !== null && $camService->getEvidenceFilePath($evName) !== null, "1.5 Copy live frame to evidence works safely");

// Clean temporary dir
@array_map('unlink', glob($tmpStorage . '/cameras/*') ?: []);
@array_map('unlink', glob($tmpStorage . '/evidence/*') ?: []);
@rmdir($tmpStorage . '/cameras');
@rmdir($tmpStorage . '/evidence');
@rmdir($tmpStorage);

// -----------------------------------------------------------------------------
// SECTION 2: Left Sidebar Visibility for Admin & Guru Profile + Camera Menus
// -----------------------------------------------------------------------------
echo "\n--- SECTION 2: Left Sidebar Profiles (Admin & Guru Not Hidden) ---\n";
$cAdmin = tempnam(sys_get_temp_dir(), 'ck_admin_');
$cGuruBudi = tempnam(sys_get_temp_dir(), 'ck_guru_b_');
$cGuruSiti = tempnam(sys_get_temp_dir(), 'ck_guru_s_');
$cAhmad = tempnam(sys_get_temp_dir(), 'ck_ahmad_');

check(loginAs($baseUrl, 'admin', 'password123', $cAdmin), "2.1 Admin login OK");
$adminDash = httpReq("{$baseUrl}/admin/dashboard", 'GET', [], $cAdmin);
check($adminDash['code'] === 200, "2.2 GET /admin/dashboard returns 200");
check(str_contains($adminDash['body'], 'Profil Admin') && str_contains($adminDash['body'], 'admin/profile'), "2.3 Admin left sidebar explicitly displays 'Profil Admin' (/admin/profile)");
check(!str_contains($adminDash['body'], 'Profil Saya'), "2.4 Top-right navbar dropdown & sidebar do not duplicate 'Profil Saya'");

$adminProf = httpReq("{$baseUrl}/admin/profile", 'GET', [], $cAdmin);
check($adminProf['code'] === 200 && str_contains($adminProf['body'], 'Profil Super Administrator'), "2.5 GET /admin/profile renders Profil Super Administrator page (200 OK)");

$adminSettings = httpReq("{$baseUrl}/admin/settings", 'GET', [], $cAdmin);
check($adminSettings['code'] === 200, "2.6 Existing GET /admin/settings untouched & returns 200 OK");

check(loginAs($baseUrl, 'guru1', 'password123', $cGuruBudi), "2.7 Guru Budi login OK");
$guruDash = httpReq("{$baseUrl}/guru/dashboard", 'GET', [], $cGuruBudi);
check($guruDash['code'] === 200, "2.8 GET /guru/dashboard returns 200");
check(str_contains($guruDash['body'], 'Profil Guru') && str_contains($guruDash['body'], 'guru/profile') && !str_contains($guruDash['body'], 'Profil Saya'), "2.9 Guru left sidebar displays 'Profil Guru' and navbar dropdown has NO 'Profil Saya'");
check(str_contains($guruDash['body'], 'Cek &amp; Monitor Kamera') || str_contains($guruDash['body'], 'Cek & Monitor Kamera'), "2.10 Guru left sidebar displays 'Cek & Monitor Kamera'");

$guruProf = httpReq("{$baseUrl}/guru/profile", 'GET', [], $cGuruBudi);
check($guruProf['code'] === 200 && str_contains($guruProf['body'], 'Profil Guru Penguji'), "2.11 GET /guru/profile returns 200 OK");

// -----------------------------------------------------------------------------
// SECTION 3: Murid (Peserta) Camera Check & Pre-Exam Camera Flow
// -----------------------------------------------------------------------------
echo "\n--- SECTION 3: Murid Camera Check (Pre-Exam & Exam Streaming) ---\n";
$db = Database::getConnection();
$examId = 1;
$sessRow = $db->query("SELECT id FROM exam_sessions WHERE exam_id = 1 AND student_id = 1 ORDER BY id DESC LIMIT 1")->fetch();
if (!$sessRow) {
    $db->exec("INSERT INTO exam_sessions (exam_id, student_id, started_at, status, remaining_seconds) VALUES (1, 1, NOW(), 'started', 3600)");
    $sessionId = (int)$db->lastInsertId();
} else {
    $sessionId = (int)$sessRow['id'];
    $db->exec("UPDATE exam_sessions SET status = 'started', started_at = NOW(), finished_at = NULL WHERE id = {$sessionId}");
}

check(loginAs($baseUrl, 'siswa1', 'password123', $cAhmad), "3.1 Siswa Ahmad login OK");
$pesertaDash = httpReq("{$baseUrl}/peserta/dashboard", 'GET', [], $cAhmad);
check(str_contains($pesertaDash['body'], 'Profil Peserta') && str_contains($pesertaDash['body'], 'Cek Kamera') && !str_contains($pesertaDash['body'], 'Profil Saya'), "3.2 Peserta left sidebar displays 'Profil Peserta' & 'Cek Kamera' and navbar dropdown has NO 'Profil Saya'");

$pCamCheck = httpReq("{$baseUrl}/peserta/camera-check", 'GET', [], $cAhmad);
check($pCamCheck['code'] === 200 && str_contains($pCamCheck['body'], 'Uji Kamera Peserta Ujian'), "3.3 GET /peserta/camera-check returns 200 OK");

$pCamCsrf = extractCsrf($pCamCheck['body']);
$pVerify = httpReq("{$baseUrl}/peserta/camera-check/verify", 'POST', [
    '_csrf_token' => $pCamCsrf,
    'camera_count' => 2,
    'cam1_label' => 'Integrated HD Webcam',
    'cam2_label' => 'USB External Cam',
    'resolution' => '640x480',
    'image_base64' => $dummyJpegDataUri
], $cAhmad, true);
check($pVerify['code'] === 200 && str_contains($pVerify['body'], '"status":"success"'), "3.4 POST /peserta/camera-check/verify saves verification snapshot", $pVerify['body']);

$pSnap = httpReq("{$baseUrl}/peserta/camera-check/snapshot", 'GET', [], $cAhmad);
check($pSnap['code'] === 200 && str_starts_with($pSnap['body'], "\xFF\xD8\xFF"), "3.5 GET /peserta/camera-check/snapshot streams student verification JPEG");

$examDetail = httpReq("{$baseUrl}/peserta/exams/detail?id={$examId}", 'GET', [], $cAhmad);
check($examDetail['code'] === 200 && str_contains($examDetail['body'], 'Pengecekan Kamera Sebelum Ujian'), "3.6 Exam confirmation page (/peserta/exams/detail) includes Pre-Exam Camera Check");

$examRoom = httpReq("{$baseUrl}/peserta/exams/room?session_id={$sessionId}", 'GET', [], $cAhmad);
check($examRoom['code'] === 200 && str_contains($examRoom['body'], 'proctorSupervisorBox'), "3.7 Exam room includes Guru Proctor Camera supervision indicator");

$roomCsrf = extractCsrf($examRoom['body']);
$snapUpload1 = httpReq("{$baseUrl}/peserta/exams/camera-snapshot", 'POST', [
    '_csrf_token' => $roomCsrf,
    'session_id' => $sessionId,
    'camera_type' => 'camera_1',
    'image_base64' => $dummyJpegDataUri
], $cAhmad, true);
check($snapUpload1['code'] === 200 && str_contains($snapUpload1['body'], '"camera_type":"camera_1"'), "3.8 POST /peserta/exams/camera-snapshot (Camera 1) returns 200 OK", $snapUpload1['body']);

$snapUpload2 = httpReq("{$baseUrl}/peserta/exams/camera-snapshot", 'POST', [
    '_csrf_token' => $roomCsrf,
    'session_id' => $sessionId,
    'camera_type' => 'camera_2',
    'image_base64' => $dummyJpegDataUri
], $cAhmad, true);
check($snapUpload2['code'] === 200 && str_contains($snapUpload2['body'], '"camera_type":"camera_2"'), "3.9 POST /peserta/exams/camera-snapshot (Camera 2) returns 200 OK");

// -----------------------------------------------------------------------------
// SECTION 4: Guru Camera Check, Live Grid, Proctor Camera & Evidence Capture
// -----------------------------------------------------------------------------
echo "\n--- SECTION 4: Guru Camera Check & Exam Supervision Cameras ---\n";
$gCamCheck = httpReq("{$baseUrl}/guru/camera-check", 'GET', [], $cGuruBudi);
check($gCamCheck['code'] === 200 && str_contains($gCamCheck['body'], 'Pengecekan Kamera Guru Pengawas'), "4.1 GET /guru/camera-check returns 200 OK");

$gCamCsrf = extractCsrf($gCamCheck['body']);
$gVerify = httpReq("{$baseUrl}/guru/camera-check/verify", 'POST', [
    '_csrf_token' => $gCamCsrf,
    'camera_count' => 1,
    'cam1_label' => 'Proctor Studio Cam',
    'resolution' => '1280x720',
    'image_base64' => $dummyJpegDataUri
], $cGuruBudi, true);
check($gVerify['code'] === 200 && str_contains($gVerify['body'], '"status":"success"'), "4.2 POST /guru/camera-check/verify succeeds");

$monExam = httpReq("{$baseUrl}/guru/monitoring/{$examId}", 'GET', [], $cGuruBudi);
check($monExam['code'] === 200 && str_contains($monExam['body'], 'Kamera Pengawas (Guru)') && str_contains($monExam['body'], 'Pantauan Langsung Kamera Murid'), "4.3 GET /guru/monitoring/{$examId} displays Guru Camera & Live Student Camera Grid");

$monLive = httpReq("{$baseUrl}/guru/monitoring/live-data/{$examId}", 'GET', [], $cGuruBudi);
$liveJson = json_decode($monLive['body'], true);
check($monLive['code'] === 200 && ($liveJson['status'] ?? '') === 'success', "4.4 GET /guru/monitoring/live-data/{$examId} returns valid JSON");
$ahmadEntry = null;
foreach (($liveJson['participants'] ?? []) as $p) {
    if ((int)$p['session_id'] === $sessionId) {
        $ahmadEntry = $p;
        break;
    }
}
check($ahmadEntry !== null && !empty($ahmadEntry['cam1_has_frame']) && !empty($ahmadEntry['cam2_has_frame']), "4.5 Live monitoring JSON confirms both Camera 1 & Camera 2 frames uploaded for Ahmad");

$frameCam1 = httpReq("{$baseUrl}/guru/monitoring/camera-frame/{$examId}/{$sessionId}/camera_1", 'GET', [], $cGuruBudi);
check($frameCam1['code'] === 200 && str_starts_with($frameCam1['body'], "\xFF\xD8\xFF"), "4.6 GET /guru/monitoring/camera-frame/{$examId}/{$sessionId}/camera_1 streams student JPEG");

$monCsrf = extractCsrf($monExam['body']);
$proctorCamStart = httpReq("{$baseUrl}/guru/monitoring/proctor-camera", 'POST', [
    '_csrf_token' => $monCsrf,
    'exam_id' => $examId,
    'action' => 'stream',
    'image_base64' => $dummyJpegDataUri
], $cGuruBudi, true);
check($proctorCamStart['code'] === 200 && str_contains($proctorCamStart['body'], '"proctor_camera_live":true'), "4.7 POST /guru/monitoring/proctor-camera streams Guru live proctor frame");

$studentViewsProctor = httpReq("{$baseUrl}/peserta/exams/proctor-camera?session_id={$sessionId}", 'GET', [], $cAhmad);
check($studentViewsProctor['code'] === 200 && str_starts_with($studentViewsProctor['body'], "\xFF\xD8\xFF"), "4.8 Enrolled student can fetch live Guru Proctor Camera frame (200 OK)");

$captureEv = httpReq("{$baseUrl}/guru/monitoring/capture-evidence", 'POST', [
    '_csrf_token' => $monCsrf,
    'exam_id' => $examId,
    'session_id' => $sessionId,
    'camera_type' => 'camera_1'
], $cGuruBudi, true);
$evJson = json_decode($captureEv['body'], true);
check($captureEv['code'] === 200 && ($evJson['status'] ?? '') === 'success' && !empty($evJson['evidence_url']), "4.9 POST /guru/monitoring/capture-evidence saves permanent verification evidence photo");

$evParsed = parse_url((string)($evJson['evidence_url'] ?? ''));
$evPath = preg_replace('#^/uline#', '', (string)($evParsed['path'] ?? ''));
$evUrlPath = $evPath . (!empty($evParsed['query']) ? '?' . $evParsed['query'] : '');
$evFetch = httpReq("{$baseUrl}{$evUrlPath}", 'GET', [], $cGuruBudi);
check($evFetch['code'] === 200 && str_starts_with($evFetch['body'], "\xFF\xD8\xFF"), "4.10 Exam owner Guru can view saved evidence photo ({$evUrlPath})");

// Also check session detail page renders Live Kamera Murid properly
$monDetail = httpReq("{$baseUrl}/guru/monitoring/{$examId}/{$sessionId}", 'GET', [], $cGuruBudi);
check($monDetail['code'] === 200 && str_contains($monDetail['body'], 'Live Kamera Murid') && str_contains($monDetail['body'], 'Connected'), "4.11 Session detail shows Live Kamera Murid with fixed camera connection status");

// Stop Guru Proctor Cam
$proctorCamStop = httpReq("{$baseUrl}/guru/monitoring/proctor-camera", 'POST', [
    '_csrf_token' => $monCsrf,
    'exam_id' => $examId,
    'action' => 'stop'
], $cGuruBudi, true);
check($proctorCamStop['code'] === 200 && str_contains($proctorCamStop['body'], '"proctor_camera_live":false'), "4.12 Guru can stop proctor camera cleanly");

// -----------------------------------------------------------------------------
// SECTION 5: Security & RBAC Cross-User Isolation
// -----------------------------------------------------------------------------
echo "\n--- SECTION 5: RBAC & Cross-Teacher Security Isolation ---\n";
check(loginAs($baseUrl, 'guru_siti', 'password123', $cGuruSiti), "5.1 Guru Siti login OK");
$sitiFrame = httpReq("{$baseUrl}/guru/monitoring/camera-frame/{$examId}/{$sessionId}/camera_1", 'GET', [], $cGuruSiti);
check($sitiFrame['code'] === 403, "5.2 Cross-teacher access to Budi's student camera frame BLOCKED (403)");

$sitiEv = httpReq("{$baseUrl}{$evUrlPath}", 'GET', [], $cGuruSiti);
check($sitiEv['code'] === 403, "5.3 Cross-teacher access to Budi's evidence image BLOCKED (403)");

$ahmadToGuruCam = httpReq("{$baseUrl}/guru/camera-check", 'GET', [], $cAhmad);
check($ahmadToGuruCam['code'] === 403 || $ahmadToGuruCam['code'] === 302, "5.4 Student access to /guru/camera-check BLOCKED");

$badCsrfSnap = httpReq("{$baseUrl}/peserta/exams/camera-snapshot", 'POST', [
    '_csrf_token' => 'INVALID_CSRF',
    'session_id' => $sessionId,
    'camera_type' => 'camera_1',
    'image_base64' => $dummyJpegDataUri
], $cAhmad, true);
check($badCsrfSnap['code'] === 403, "5.5 Camera snapshot with invalid CSRF BLOCKED (403)");

@unlink($cAdmin);
@unlink($cGuruBudi);
@unlink($cGuruSiti);
@unlink($cAhmad);

echo "\n========================================================================\n";
echo "  FINAL RESULT: {$pass} PASSED, {$fail} FAILED\n";
echo "========================================================================\n";
exit($fail > 0 ? 1 : 0);
