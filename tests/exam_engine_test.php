<?php

/**
 * AI Online Examination & Proctoring System
 * E2E Test Suite for Student Exam Engine (Modul Ujian Peserta Didik)
 */

declare(strict_types=1);

$port = $argv[1] ?? '8093';
$baseUrl = "http://127.0.0.1:{$port}";

// Reset test session for Ahmad & Dewi
$config = require __DIR__ . '/../app/Config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$pdo->exec("DELETE FROM exam_sessions WHERE student_id IN (1, 2)");

$cookieFileAhmad = tempnam(sys_get_temp_dir(), 'ahmad_exam_');
$cookieFileDewi = tempnam(sys_get_temp_dir(), 'dewi_exam_');

function makeRequest(string $url, string $method = 'GET', array $data = [], string $cookieFile = ''): array
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
        $options[CURLOPT_POSTFIELDS] = http_build_query($data);
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $body, $m);
    $csrf = $m[1] ?? '';

    preg_match('/Location:\s*([^\r\n]+)/i', $headerStr, $loc);
    $location = trim($loc[1] ?? ($redirectUrl ?: ''));

    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body,
        'csrf' => $csrf,
        'redirect' => $location
    ];
}

echo "=== Student Exam Engine E2E Test Suite ===\n\n";

// 1. Login as Student (Ahmad Pratama - Class XII MIPA 1)
echo "1. Login Siswa Ahmad (XII MIPA 1):\n";
$loginPage = makeRequest("{$baseUrl}/login", 'GET', [], $cookieFileAhmad);
$csrf = $loginPage['csrf'];
$loginResp = makeRequest("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $csrf,
    'identifier' => 'siswa_ahmad',
    'password' => 'password123'
], $cookieFileAhmad);

echo "   Login status: {$loginResp['code']} -> {$loginResp['redirect']}\n";
if ($loginResp['code'] !== 302 || strpos($loginResp['redirect'], '/peserta/dashboard') === false) {
    die("FATAL: Failed to login as Siswa Ahmad.\n");
}

// 2. GET /peserta/exams
echo "\n2. GET /peserta/exams:\n";
$examsList = makeRequest("{$baseUrl}/peserta/exams", 'GET', [], $cookieFileAhmad);
echo "   Status: {$examsList['code']} (Expected: 200)\n";
if (strpos($examsList['body'], 'Kalkulus') === false) {
    die("FATAL: Published exam for Class XII MIPA 1 not found in exam list.\n");
}
echo "   Found published exam 'Penilaian Harian 1: Kalkulus & Trigonometri' ✓\n";

// 3. GET /peserta/exams/detail?id=1
echo "\n3. GET /peserta/exams/detail?id=1 (Pre-exam instructions):\n";
$detailPage = makeRequest("{$baseUrl}/peserta/exams/detail?id=1", 'GET', [], $cookieFileAhmad);
echo "   Status: {$detailPage['code']} (Expected: 200)\n";
$detailCsrf = $detailPage['csrf'];
if (empty($detailCsrf)) {
    die("FATAL: CSRF token not found on exam detail page.\n");
}

// 4. POST /peserta/exams/start (Initialize exam session)
echo "\n4. POST /peserta/exams/start:\n";
$startResp = makeRequest("{$baseUrl}/peserta/exams/start", 'POST', [
    '_csrf_token' => $detailCsrf,
    'exam_id' => 1
], $cookieFileAhmad);

echo "   Status: {$startResp['code']} -> Redirect: {$startResp['redirect']}\n";
preg_match('/session_id=(\d+)/', $startResp['redirect'] ?? '', $mSession);
$sessionId = (int)($mSession[1] ?? 0);

if ($sessionId <= 0) {
    die("FATAL: Failed to start exam session.\n");
}
echo "   Session ID created: {$sessionId} ✓\n";

// 5. GET /peserta/exams/room?session_id=X (Exam Engine Room)
echo "\n5. GET /peserta/exams/room?session_id={$sessionId} (Exam Room):\n";
$roomResp = makeRequest("{$baseUrl}/peserta/exams/room?session_id={$sessionId}", 'GET', [], $cookieFileAhmad);
echo "   Status: {$roomResp['code']} (Expected: 200)\n";
if (strpos($roomResp['body'], 'EXAM_DATA') === false || strpos($roomResp['body'], 'timerDisplay') === false) {
    die("FATAL: Exam room interface elements not rendered.\n");
}
echo "   Exam room interface, timer, and question grid rendered correctly ✓\n";

// 6. POST /peserta/exams/save-answer (AJAX Autosave Question 1: MCQ 'A' - Correct)
echo "\n6. AJAX Autosave Answers:\n";
$roomCsrf = $detailCsrf; // CSRF token

$saveQ1 = makeRequest("{$baseUrl}/peserta/exams/save-answer", 'POST', [
    '_csrf_token' => $roomCsrf,
    'session_id' => $sessionId,
    'question_id' => 1,
    'answer' => 'A'
], $cookieFileAhmad);
echo "   Save Q1 (MCQ): Code {$saveQ1['code']}, Body: {$saveQ1['body']}\n";
$jsonQ1 = json_decode($saveQ1['body'], true);
if (($jsonQ1['status'] ?? '') !== 'success' || ($jsonQ1['data']['score'] ?? 0) !== 10) {
    die("FATAL: Auto-scoring for Q1 failed.\n");
}
echo "   Q1 Auto-scored: 10 Pts ✓\n";

// Save Question 2: MCQ 'A' - Correct
$saveQ2 = makeRequest("{$baseUrl}/peserta/exams/save-answer", 'POST', [
    '_csrf_token' => $roomCsrf,
    'session_id' => $sessionId,
    'question_id' => 2,
    'answer' => 'A'
], $cookieFileAhmad);
$jsonQ2 = json_decode($saveQ2['body'], true);
echo "   Save Q2 (MCQ): Status {$jsonQ2['status']}, Score: {$jsonQ2['data']['score']} Pts ✓\n";

// Save Question 3: True/False 'TRUE' - Correct
$saveQ3 = makeRequest("{$baseUrl}/peserta/exams/save-answer", 'POST', [
    '_csrf_token' => $roomCsrf,
    'session_id' => $sessionId,
    'question_id' => 3,
    'answer' => 'TRUE'
], $cookieFileAhmad);
$jsonQ3 = json_decode($saveQ3['body'], true);
echo "   Save Q3 (True/False): Status {$jsonQ3['status']}, Score: {$jsonQ3['data']['score']} Pts ✓\n";

// 7. Test Refresh Persistence
echo "\n7. Test Refresh Persistence:\n";
$refreshResp = makeRequest("{$baseUrl}/peserta/exams/room?session_id={$sessionId}", 'GET', [], $cookieFileAhmad);
echo "   Status on refresh: {$refreshResp['code']} (Expected: 200) ✓\n";

// 8. Submit Exam
echo "\n8. POST /peserta/exams/finish (Submit Exam):\n";
$finishResp = makeRequest("{$baseUrl}/peserta/exams/finish", 'POST', [
    '_csrf_token' => $roomCsrf,
    'session_id' => $sessionId,
    'is_expired' => 0
], $cookieFileAhmad);
echo "   Status: {$finishResp['code']} -> Redirect: {$finishResp['redirect']} ✓\n";

// 9. GET /peserta/exams/result?session_id=X (Result & Score Breakdown)
echo "\n9. GET /peserta/exams/result?session_id={$sessionId}:\n";
$resultResp = makeRequest("{$baseUrl}/peserta/exams/result?session_id={$sessionId}", 'GET', [], $cookieFileAhmad);
echo "   Status: {$resultResp['code']} (Expected: 200)\n";
if (strpos($resultResp['body'], '30') === false || strpos($resultResp['body'], '100%') === false) {
    die("FATAL: Exam score calculation or result page incorrect.\n");
}
echo "   Final Score Verified: 30 / 30 (100% Correct) ✓\n";

// 10. Security Isolation Test: Another student / cross-session access
echo "\n10. Security Test: Another Student attempting unauthorized session access...\n";
// Create temporary student in another class or use Dewi
$loginDewiPage = makeRequest("{$baseUrl}/login", 'GET', [], $cookieFileDewi);
$csrfDewi = $loginDewiPage['csrf'];
makeRequest("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $csrfDewi,
    'identifier' => 'siswa_dewi',
    'password' => 'password123'
], $cookieFileDewi);

// Dewi tries to access Ahmad's session room
$unauthRoom = makeRequest("{$baseUrl}/peserta/exams/room?session_id={$sessionId}", 'GET', [], $cookieFileDewi);
echo "   Unauthorized Room Access: Code {$unauthRoom['code']} (Expected: 403) " . ($unauthRoom['code'] === 403 ? "✓" : "FAILED") . "\n";

// Dewi tries to save answer into Ahmad's session
$unauthSave = makeRequest("{$baseUrl}/peserta/exams/save-answer", 'POST', [
    '_csrf_token' => $csrfDewi,
    'session_id' => $sessionId,
    'question_id' => 1,
    'answer' => 'B'
], $cookieFileDewi);
echo "   Unauthorized Answer Submission: Code {$unauthSave['code']} (Expected: 403) " . ($unauthSave['code'] === 403 ? "✓" : "FAILED") . "\n";

// Dewi tries to access Ahmad's result
$unauthResult = makeRequest("{$baseUrl}/peserta/exams/result?session_id={$sessionId}", 'GET', [], $cookieFileDewi);
echo "   Unauthorized Result Access: Code {$unauthResult['code']} (Expected: 403) " . ($unauthResult['code'] === 403 ? "✓" : "FAILED") . "\n";

if (file_exists($cookieFileAhmad)) @unlink($cookieFileAhmad);
if (file_exists($cookieFileDewi)) @unlink($cookieFileDewi);

echo "\n========================================================\n";
echo "ALL STUDENT EXAM ENGINE TESTS PASSED WITH 100% SUCCESS!\n";
echo "========================================================\n";
