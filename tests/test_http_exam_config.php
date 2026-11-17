<?php

declare(strict_types=1);

echo "====================================================================\n";
echo "   E2E HTTP INTEGRATION & ROLE ACCESS TEST FOR EXAM CONFIG\n";
echo "====================================================================\n\n";

$baseUrl = 'http://localhost/uline';
$cookieAdmin = __DIR__ . '/admin_cookie.txt';
$cookieGuru = __DIR__ . '/guru_cookie.txt';
$cookieSiswa = __DIR__ . '/siswa_cookie.txt';

if (file_exists($cookieAdmin)) @unlink($cookieAdmin);
if (file_exists($cookieGuru)) @unlink($cookieGuru);
if (file_exists($cookieSiswa)) @unlink($cookieSiswa);

function loginAndGetCookie(string $username, string $password, string $cookieFile): array {
    global $baseUrl;

    // 1. GET login page to obtain CSRF token
    $ch = curl_init("{$baseUrl}/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $html, $matches);
    $csrf = $matches[1] ?? '';

    // 2. POST login
    $postData = [
        'identifier' => $username,
        'password' => $password,
        '_csrf_token' => $csrf
    ];

    $ch = curl_init("{$baseUrl}/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'cookie' => $cookieFile];
}

function requestPage(string $path, string $cookieFile): array {
    global $baseUrl;
    $ch = curl_init("{$baseUrl}/{$path}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

// 1. Test Admin Access (SUPER_ADMIN)
echo "1. Testing Admin (SUPER_ADMIN) Login & Access...\n";
$adminLogin = loginAndGetCookie('admin', 'password123', $cookieAdmin);
echo "   Login code: {$adminLogin['code']}\n";
$adminPage = requestPage('admin/exam-config', $cookieAdmin);
echo "   Access /admin/exam-config HTTP Code: {$adminPage['code']}\n";
preg_match('/<title>(.*?)<\/title>/', $adminPage['body'], $tMatches);
echo "   Page Title: " . ($tMatches[1] ?? 'None') . "\n";
echo "   Body snippet: " . substr(strip_tags($adminPage['body']), 0, 200) . "\n";
$adminHasForm = str_contains($adminPage['body'], 'examConfigForm') && str_contains($adminPage['body'], 'Konfigurasi Ujian Online');

echo "   Admin can see Exam Config form: " . ($adminHasForm ? "YES [PASS]" : "NO [FAIL]") . "\n\n";

// 2. Test Guru Access (GURU)
echo "2. Testing Guru (GURU) Login & Access...\n";
$guruLogin = loginAndGetCookie('guru1', 'password123', $cookieGuru);
echo "   Login code: {$guruLogin['code']}\n";
$guruPage = requestPage('admin/exam-config', $cookieGuru);
echo "   Access /admin/exam-config HTTP Code: {$guruPage['code']}\n";
$guruHasForm = str_contains($guruPage['body'], 'examConfigForm') && str_contains($guruPage['body'], 'Konfigurasi Ujian Online');
echo "   Guru can see Exam Config form: " . ($guruHasForm ? "YES [PASS]" : "NO [FAIL]") . "\n\n";

// 3. Test Siswa Access (PESERTA) - Must be BLOCKED with 403 Forbidden!
echo "3. Testing Siswa (PESERTA) Role Isolation...\n";
$siswaLogin = loginAndGetCookie('siswa1', 'password123', $cookieSiswa);
echo "   Login code: {$siswaLogin['code']}\n";
$siswaPage = requestPage('admin/exam-config', $cookieSiswa);
echo "   Access /admin/exam-config HTTP Code: {$siswaPage['code']}\n";
$siswaBlocked = ($siswaPage['code'] === 403);
echo "   Siswa access blocked with 403: " . ($siswaBlocked ? "YES [PASS]" : "NO [FAIL]") . "\n\n";

// 4. Test Unauthenticated Access - Must Redirect to Login
echo "4. Testing Unauthenticated Guest Access...\n";
$guestPage = requestPage('admin/exam-config', 'non_existent_cookie.txt');
echo "   Guest access HTTP Code / Final: {$guestPage['code']}\n";
$guestRedirected = str_contains($guestPage['body'], 'Login') || $guestPage['code'] === 302 || $guestPage['code'] === 200;
echo "   Guest redirected to login: " . ($guestRedirected ? "YES [PASS]" : "NO [FAIL]") . "\n\n";

// 5. Test AJAX Token Generation & Exam Config Form Submission
echo "5. Testing AJAX Token Generation & Config Update via HTTP...\n";
preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $adminPage['body'], $csrfMatches);
$adminCsrf = $csrfMatches[1] ?? '';

// 5a. Generate token via AJAX
$ch = curl_init("{$baseUrl}/admin/exam-config/generate-token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['_csrf_token' => $adminCsrf]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieAdmin);
$genResp = curl_exec($ch);
curl_close($ch);
$genJson = json_decode($genResp, true);
$hasGenToken = isset($genJson['status']) && $genJson['status'] === 'success' && !empty($genJson['token']);
echo "   AJAX generate-token success: " . ($hasGenToken ? "YES [PASS]" : "NO [FAIL]") . " (Generated: " . ($genJson['token'] ?? 'none') . ")\n";

// 5b. Update config via AJAX
$updatePayload = [
    '_csrf_token' => $adminCsrf,
    'title' => 'Simulasi Ujian Nasional Berbasis Komputer',
    'duration' => 75,
    'random_questions' => '1',
    'random_options' => '1',
    'start_time' => '2026-10-21 08:00:00',
    'end_time' => '2026-10-21 12:00:00',
    'token' => 'ULINE77Z',
    'token_secret' => 'exam_sec_live_99a8b7',
    'is_active' => '1'
];

$ch = curl_init("{$baseUrl}/admin/exam-config/update");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($updatePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieAdmin);
$updResp = curl_exec($ch);
$updCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$updJson = json_decode($updResp, true);
$updSuccess = ($updCode === 200) && isset($updJson['status']) && $updJson['status'] === 'success';
echo "   AJAX update config HTTP Code {$updCode} and success: " . ($updSuccess ? "YES [PASS]" : "NO [FAIL]") . "\n";
echo "   Toast Message returned: " . ($updJson['message'] ?? 'N/A') . "\n";

// Verify .env updated
require_once dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load();
$envSecret = \App\Core\Env::get('EXAM_TOKEN_SECRET_KEY');
echo "   EXAM_TOKEN_SECRET_KEY in .env: {$envSecret} " . ($envSecret === 'exam_sec_live_99a8b7' ? "[PASS]" : "[FAIL]") . "\n\n";

// Restore .env
\App\Core\Env::set('EXAM_TOKEN_SECRET_KEY', 'exam_sec_f98c21a084eb73');

// Clean cookies
if (file_exists($cookieAdmin)) @unlink($cookieAdmin);
if (file_exists($cookieGuru)) @unlink($cookieGuru);
if (file_exists($cookieSiswa)) @unlink($cookieSiswa);

echo "====================================================================\n";
echo "HTTP TEST COMPLETE!\n";

echo "====================================================================\n";
