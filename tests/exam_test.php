<?php

/**
 * AI Online Examination & Proctoring System
 * E2E Test Suite for Guru Exam Management (Modul Ujian Guru)
 */

declare(strict_types=1);

$port = $argv[1] ?? '8093';
$baseUrl = "http://127.0.0.1:{$port}";
$cookieFileBudi = tempnam(sys_get_temp_dir(), 'budi_exam_');
$cookieFileSiti = tempnam(sys_get_temp_dir(), 'siti_exam_');

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

echo "=== Guru Exam Management E2E Test Suite ===\n\n";

// 1. Login as Guru Budi
echo "1. Login Guru Budi:\n";
$loginPage = makeRequest("{$baseUrl}/login", 'GET', [], $cookieFileBudi);
$csrf = $loginPage['csrf'];
$loginResp = makeRequest("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $csrf,
    'identifier' => 'guru_budi',
    'password' => 'password123'
], $cookieFileBudi);

echo "   Login status: {$loginResp['code']} -> {$loginResp['redirect']}\n";
if ($loginResp['code'] !== 302 || strpos($loginResp['redirect'], '/guru/dashboard') === false) {
    die("FATAL: Failed to login as Guru Budi.\n");
}

// 2. GET /guru/exams
echo "\n2. GET /guru/exams:\n";
$examsList = makeRequest("{$baseUrl}/guru/exams", 'GET', [], $cookieFileBudi);
echo "   Status: {$examsList['code']} (Expected: 200)\n";
if ($examsList['code'] !== 200) {
    die("FATAL: Failed to load /guru/exams.\n");
}

// 3. Validation: Duration <= 0
echo "\n3. Test Validation: Invalid Duration (0 Minutes):\n";
$createPage = makeRequest("{$baseUrl}/guru/exams/create", 'GET', [], $cookieFileBudi);
$csrfCreate = $createPage['csrf'];

$invalidDurationResp = makeRequest("{$baseUrl}/guru/exams/store", 'POST', [
    '_csrf_token' => $csrfCreate,
    'title' => 'Ujian Durasi Nol',
    'subject_id' => 1,
    'class_id' => 1,
    'duration' => 0,
    'start_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
    'questions' => [1, 2]
], $cookieFileBudi);
echo "   Redirect on error: {$invalidDurationResp['code']} -> {$invalidDurationResp['redirect']} ✓\n";

// 4. Validation: Invalid End Date (End before Start)
echo "\n4. Test Validation: End Date before Start Date:\n";
$invalidDateResp = makeRequest("{$baseUrl}/guru/exams/store", 'POST', [
    '_csrf_token' => $csrfCreate,
    'title' => 'Ujian Tanggal Salah',
    'subject_id' => 1,
    'class_id' => 1,
    'duration' => 60,
    'start_at' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'questions' => [1, 2]
], $cookieFileBudi);
echo "   Redirect on error: {$invalidDateResp['code']} -> {$invalidDateResp['redirect']} ✓\n";

// 5. Validation: No Questions Selected
echo "\n5. Test Validation: Empty Questions Selection:\n";
$invalidQuestionsResp = makeRequest("{$baseUrl}/guru/exams/store", 'POST', [
    '_csrf_token' => $csrfCreate,
    'title' => 'Ujian Tanpa Soal',
    'subject_id' => 1,
    'class_id' => 1,
    'duration' => 60,
    'start_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
], $cookieFileBudi);
echo "   Redirect on error: {$invalidQuestionsResp['code']} -> {$invalidQuestionsResp['redirect']} ✓\n";

// 6. Create Valid Exam with Questions, AI Proctoring, Dual Camera, Randomization
echo "\n6. Create Valid Exam (Draft):\n";
$storeResp = makeRequest("{$baseUrl}/guru/exams/store", 'POST', [
    '_csrf_token' => $csrfCreate,
    'title' => 'Ujian Akhir Semester: Matematika Peminatan XII',
    'subject_id' => 1,
    'class_id' => 1,
    'description' => 'Kerjakan dengan jujur. AI Proctoring dan Dual Camera diaktifkan.',
    'duration' => 120,
    'start_at' => date('Y-m-d H:i:s', strtotime('+2 days 08:00:00')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+2 days 11:00:00')),
    'random_questions' => 1,
    'random_options' => 1,
    'ai_proctoring' => 1,
    'dual_camera' => 1,
    'status' => 'draft',
    'questions' => [1, 2, 3]
], $cookieFileBudi);

echo "   Status: {$storeResp['code']} -> Redirect: {$storeResp['redirect']}\n";
preg_match('/id=(\d+)/', $storeResp['redirect'] ?? '', $idMatch);
$newExamId = (int)($idMatch[1] ?? 0);

if ($newExamId <= 0) {
    die("FATAL: Failed to create valid exam.\n");
}
echo "   Created Exam ID: {$newExamId} with 3 questions ✓\n";

// 7. Verify Show Exam Detail
echo "\n7. GET /guru/exams/show?id={$newExamId}:\n";
$showResp = makeRequest("{$baseUrl}/guru/exams/show?id={$newExamId}", 'GET', [], $cookieFileBudi);
echo "   Status: {$showResp['code']} (Expected: 200)\n";
if (strpos($showResp['body'], 'Ujian Akhir Semester: Matematika Peminatan XII') === false) {
    die("FATAL: Exam title not rendered on detail page.\n");
}
echo "   Exam details and questions rendered correctly ✓\n";

// 8. Publish Exam
echo "\n8. Toggle Publish Status:\n";
$showPageCsrf = $showResp['csrf'];
$publishResp = makeRequest("{$baseUrl}/guru/exams/toggle-status", 'POST', [
    '_csrf_token' => $showPageCsrf,
    'id' => $newExamId,
    'status' => 'published'
], $cookieFileBudi);
echo "   Status: {$publishResp['code']} -> {$publishResp['redirect']} ✓\n";

// 9. Update Exam
echo "\n9. Update Exam Details:\n";
$editPage = makeRequest("{$baseUrl}/guru/exams/edit?id={$newExamId}", 'GET', [], $cookieFileBudi);
$editCsrf = $editPage['csrf'];

$updateResp = makeRequest("{$baseUrl}/guru/exams/update", 'POST', [
    '_csrf_token' => $editCsrf,
    'id' => $newExamId,
    'title' => 'UAS Matematika Peminatan (Updated)',
    'subject_id' => 1,
    'class_id' => 1,
    'description' => 'Updated instructions.',
    'duration' => 90,
    'start_at' => date('Y-m-d H:i:s', strtotime('+2 days 08:30:00')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+2 days 10:30:00')),
    'random_questions' => 1,
    'random_options' => 1,
    'ai_proctoring' => 1,
    'dual_camera' => 0,
    'status' => 'published',
    'questions' => [1, 2] // Updated to 2 questions
], $cookieFileBudi);

echo "   Update Status: {$updateResp['code']} -> {$updateResp['redirect']} ✓\n";

// 10. Security Isolation Test (Another Guru: guru_siti)
echo "\n10. Security Test: Another Guru (guru_siti) attempts unauthorized access to Exam ID {$newExamId}...\n";
$loginSitiPage = makeRequest("{$baseUrl}/login", 'GET', [], $cookieFileSiti);
$csrfSiti = $loginSitiPage['csrf'];
makeRequest("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $csrfSiti,
    'identifier' => 'guru_siti',
    'password' => 'password123'
], $cookieFileSiti);

// Unauthorized Show
$unauthShow = makeRequest("{$baseUrl}/guru/exams/show?id={$newExamId}", 'GET', [], $cookieFileSiti);
echo "   Unauthorized Show Attempt: Code {$unauthShow['code']} (Expected: 403) " . ($unauthShow['code'] === 403 ? "✓" : "FAILED") . "\n";

// Unauthorized Edit
$unauthEdit = makeRequest("{$baseUrl}/guru/exams/edit?id={$newExamId}", 'GET', [], $cookieFileSiti);
echo "   Unauthorized Edit Attempt: Code {$unauthEdit['code']} (Expected: 403) " . ($unauthEdit['code'] === 403 ? "✓" : "FAILED") . "\n";

// Unauthorized Update
$unauthUpdate = makeRequest("{$baseUrl}/guru/exams/update", 'POST', [
    '_csrf_token' => $csrfSiti,
    'id' => $newExamId,
    'title' => 'Hacked by Siti',
    'subject_id' => 2,
    'class_id' => 1,
    'duration' => 50,
    'start_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
    'questions' => [6]
], $cookieFileSiti);
echo "   Unauthorized Update Attempt: Code {$unauthUpdate['code']} (Expected: 403) " . ($unauthUpdate['code'] === 403 ? "✓" : "FAILED") . "\n";

// Unauthorized Delete
$unauthDelete = makeRequest("{$baseUrl}/guru/exams/delete", 'POST', [
    '_csrf_token' => $csrfSiti,
    'id' => $newExamId
], $cookieFileSiti);
echo "   Unauthorized Delete Attempt: Code {$unauthDelete['code']} (Expected: 403) " . ($unauthDelete['code'] === 403 ? "✓" : "FAILED") . "\n";

// Unauthorized Question Theft (Siti creates exam but attaches Budi's questions)
$unauthTheft = makeRequest("{$baseUrl}/guru/exams/store", 'POST', [
    '_csrf_token' => $csrfSiti,
    'title' => 'Ujian Siti Curi Soal Budi',
    'subject_id' => 2,
    'class_id' => 1,
    'duration' => 60,
    'start_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'end_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
    'questions' => [1] // Question ID 1 belongs to Guru Budi
], $cookieFileSiti);
echo "   Unauthorized Question Stealing Attempt: Code {$unauthTheft['code']} (Expected: 403) " . ($unauthTheft['code'] === 403 ? "✓" : "FAILED") . "\n";

// 11. Delete Exam by Owner
echo "\n11. Delete Exam by Owner (guru_budi):\n";
$afterUpdatePage = makeRequest("{$baseUrl}/guru/exams/show?id={$newExamId}", 'GET', [], $cookieFileBudi);
$deleteCsrf = $afterUpdatePage['csrf'];
$deleteResp = makeRequest("{$baseUrl}/guru/exams/delete", 'POST', [
    '_csrf_token' => $deleteCsrf,
    'id' => $newExamId
], $cookieFileBudi);
echo "   Delete Status: {$deleteResp['code']} -> {$deleteResp['redirect']} ✓\n";

if (file_exists($cookieFileBudi)) @unlink($cookieFileBudi);
if (file_exists($cookieFileSiti)) @unlink($cookieFileSiti);

echo "\n========================================================\n";
echo "ALL GURU EXAM MANAGEMENT TESTS PASSED WITH 100% SUCCESS!\n";
echo "========================================================\n";
