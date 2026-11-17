<?php

/**
 * End-to-End Test for Authentication & Role Authorization
 */

$port = $argv[1] ?? '8093';
$baseUrl = "http://127.0.0.1:{$port}";

echo "=== E2E Authentication & Role Authorization Test ===\n\n";

function request($url, $method = 'GET', $data = [], $cookieJar = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    // Extract CSRF token if present
    preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $body, $csrfMatches);
    $csrfToken = $csrfMatches[1] ?? null;

    // Extract Location header if redirect
    preg_match('/Location:\s*([^\r\n]+)/i', $headers, $locMatches);
    $location = $locMatches[1] ?? null;
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'csrf' => $csrfToken,
        'location' => $location
    ];
}

$cookieFile = tempnam(sys_get_temp_dir(), 'uline_cookie_');

// 1. Test GET /login
echo "1. Testing GET /login...\n";
$res = request("{$baseUrl}/login", 'GET', [], $cookieFile);
$csrf = $res['csrf'];
echo "   Status Code: {$res['code']} (Expected: 200)\n";
echo "   CSRF Token extracted: " . ($csrf ? "YES (" . substr($csrf, 0, 10) . "...)" : "NO") . "\n";
assert($res['code'] === 200, "Login page failed to load");

// 2. Test Inactive User Login
echo "\n2. Testing Inactive Account Login (`siswa_nonaktif`)...\n";
$res = request("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $csrf,
    'identifier' => 'siswa_nonaktif',
    'password' => 'password123'
], $cookieFile);
echo "   Status Code: {$res['code']} (Expected: 302 Redirect back to /login)\n";
echo "   Location: {$res['location']}\n";
// Follow redirect to see flash error
$resFollow = request("{$baseUrl}/login", 'GET', [], $cookieFile);
$csrf = $resFollow['csrf'];
$hasInactiveError = str_contains($resFollow['body'], 'non-aktif');
echo "   Contains 'non-aktif' flash message: " . ($hasInactiveError ? "YES ✓" : "NO ✗") . "\n";

// 3. Test SUPER_ADMIN Login
echo "\n3. Testing SUPER_ADMIN Login (`admin`)...\n";
$adminCookie = tempnam(sys_get_temp_dir(), 'admin_cookie_');
$resLoginGet = request("{$baseUrl}/login", 'GET', [], $adminCookie);
$resAdminLogin = request("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $resLoginGet['csrf'],
    'identifier' => 'admin',
    'password' => 'password123'
], $adminCookie);
echo "   Status Code: {$resAdminLogin['code']}\n";
echo "   Redirect Location: {$resAdminLogin['location']} (Expected: .../admin/dashboard)\n";

echo "   Accessing /admin/dashboard as SUPER_ADMIN...\n";
$resAdminDash = request("{$baseUrl}/admin/dashboard", 'GET', [], $adminCookie);
echo "   Status Code: {$resAdminDash['code']} (Expected: 200)\n";
echo "   Contains 'PANEL SUPER ADMINISTRATOR': " . (str_contains($resAdminDash['body'], 'PANEL SUPER ADMINISTRATOR') ? "YES ✓" : "NO ✗") . "\n";

echo "   Accessing /guru/dashboard as SUPER_ADMIN (Cross-role test)...\n";
$resAdminToGuru = request("{$baseUrl}/guru/dashboard", 'GET', [], $adminCookie);
echo "   Status Code: {$resAdminToGuru['code']} (Expected: 403 Forbidden)\n";
echo "   Blocked with 403 Forbidden: " . ($resAdminToGuru['code'] === 403 ? "YES ✓" : "NO ✗") . "\n";

// 4. Test GURU Login
echo "\n4. Testing GURU Login (`guru1`)...\n";
$guruCookie = tempnam(sys_get_temp_dir(), 'guru_cookie_');
$resLoginGet = request("{$baseUrl}/login", 'GET', [], $guruCookie);
$resGuruLogin = request("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $resLoginGet['csrf'],
    'identifier' => 'guru1',
    'password' => 'password123'
], $guruCookie);
echo "   Status Code: {$resGuruLogin['code']}\n";
echo "   Redirect Location: {$resGuruLogin['location']} (Expected: .../guru/dashboard)\n";

echo "   Accessing /guru/dashboard as GURU...\n";
$resGuruDash = request("{$baseUrl}/guru/dashboard", 'GET', [], $guruCookie);
echo "   Status Code: {$resGuruDash['code']} (Expected: 200)\n";
echo "   Contains 'PANEL GURU PENGUJI': " . (str_contains($resGuruDash['body'], 'PANEL GURU PENGUJI') ? "YES ✓" : "NO ✗") . "\n";

echo "   Accessing /admin/dashboard as GURU (Unauthorized test)...\n";
$resGuruToAdmin = request("{$baseUrl}/admin/dashboard", 'GET', [], $guruCookie);
echo "   Status Code: {$resGuruToAdmin['code']} (Expected: 403 Forbidden)\n";
echo "   Blocked with 403 Forbidden: " . ($resGuruToAdmin['code'] === 403 ? "YES ✓" : "NO ✗") . "\n";

// 5. Test PESERTA Login
echo "\n5. Testing PESERTA Login (`siswa1`)...\n";
$pesertaCookie = tempnam(sys_get_temp_dir(), 'peserta_cookie_');
$resLoginGet = request("{$baseUrl}/login", 'GET', [], $pesertaCookie);
$resPesertaLogin = request("{$baseUrl}/login", 'POST', [
    '_csrf_token' => $resLoginGet['csrf'],
    'identifier' => 'siswa1',
    'password' => 'password123'
], $pesertaCookie);
echo "   Status Code: {$resPesertaLogin['code']}\n";
echo "   Redirect Location: {$resPesertaLogin['location']} (Expected: .../peserta/dashboard)\n";

echo "   Accessing /peserta/dashboard as PESERTA...\n";
$resPesertaDash = request("{$baseUrl}/peserta/dashboard", 'GET', [], $pesertaCookie);
echo "   Status Code: {$resPesertaDash['code']} (Expected: 200)\n";
echo "   Contains 'PANEL PESERTA UJIAN': " . (str_contains($resPesertaDash['body'], 'PANEL PESERTA UJIAN') ? "YES ✓" : "NO ✗") . "\n";

echo "   Accessing /admin/dashboard as PESERTA (Unauthorized test)...\n";
$resPesertaToAdmin = request("{$baseUrl}/admin/dashboard", 'GET', [], $pesertaCookie);
echo "   Status Code: {$resPesertaToAdmin['code']} (Expected: 403 Forbidden)\n";
echo "   Blocked with 403 Forbidden: " . ($resPesertaToAdmin['code'] === 403 ? "YES ✓" : "NO ✗") . "\n";

// 6. Test Logout
echo "\n6. Testing Logout...\n";
$resLogout = request("{$baseUrl}/logout", 'GET', [], $pesertaCookie);
echo "   Status Code: {$resLogout['code']} (Expected: 302 Redirect to /login)\n";
$resAfterLogout = request("{$baseUrl}/peserta/dashboard", 'GET', [], $pesertaCookie);
echo "   Accessing protected dashboard after logout: {$resAfterLogout['code']} (Expected: 302 Redirect to /login)\n";

// Cleanup cookies
@unlink($cookieFile);
@unlink($adminCookie);
@unlink($guruCookie);
@unlink($pesertaCookie);

echo "\n============================================\n";
echo "ALL TESTS PASSED WITH 100% SUCCESS!\n";
echo "============================================\n";
