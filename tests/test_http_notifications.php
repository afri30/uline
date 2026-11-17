<?php

declare(strict_types=1);

$port = $argv[1] ?? '8099';
$baseUrl = "http://127.0.0.1:{$port}";

echo "========================================================\n";
echo "  E2E HTTP NOTIFICATION SYSTEM TEST\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertHttp(bool $condition, string $testName, array $debugInfo = []): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$testName}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$testName}\n";
        if (!empty($debugInfo)) {
            echo "         DEBUG CODE: " . ($debugInfo['code'] ?? 'N/A') . "\n";
            echo "         DEBUG BODY: " . substr((string)($debugInfo['body'] ?? ''), 0, 300) . "\n";
        }
    }
}

function makeRequest(string $url, string $method = 'GET', $data = [], string $cookieFile = '', bool $isJson = false): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if (!empty($cookieFile)) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    $headers = ['Accept: application/json, text/html'];

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => (int)$code,
        'body' => (string)$body
    ];
}

function loginUser(string $baseUrl, string $username, string $password, string $cookieFile): bool
{
    $loginPage = makeRequest("{$baseUrl}/login", 'GET', [], $cookieFile);
    preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $matches);
    $csrfToken = $matches[1] ?? '';

    $loginResp = makeRequest("{$baseUrl}/login", 'POST', [
        'identifier' => $username,
        'password' => $password,
        '_csrf_token' => $csrfToken
    ], $cookieFile);

    return in_array($loginResp['code'], [302, 200], true);
}

$cookieAhmad = __DIR__ . '/test_notif_cookie_ahmad.txt';
$cookieDewi = __DIR__ . '/test_notif_cookie_dewi.txt';
$cookieGuru = __DIR__ . '/test_notif_cookie_guru.txt';
$cookieAdmin = __DIR__ . '/test_notif_cookie_admin.txt';

if (file_exists($cookieAhmad)) @unlink($cookieAhmad);
if (file_exists($cookieDewi)) @unlink($cookieDewi);
if (file_exists($cookieGuru)) @unlink($cookieGuru);
if (file_exists($cookieAdmin)) @unlink($cookieAdmin);

// 1. Unauthenticated request to /notifications blocked (Redirect 302 or 401)
$unauth = makeRequest("{$baseUrl}/notifications", 'GET');
assertHttp(in_array($unauth['code'], [302, 401], true), "1. Unauthenticated /notifications blocked (Redirect 302/401)", $unauth);

// 2. Unauthenticated API request returns 401
$unauthApi = makeRequest("{$baseUrl}/api/notifications/recent", 'GET');
assertHttp($unauthApi['code'] === 401, "2. Unauthenticated /api/notifications/recent returns 401 Unauthorized", $unauthApi);

// 3. Login Siswa Ahmad
$loginAhmad = loginUser($baseUrl, 'siswa1', 'password123', $cookieAhmad);
assertHttp($loginAhmad, "3. Login Siswa Ahmad (siswa1) berhasil");

// Seed 1 notification for Ahmad directly in DB
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});
$notifService = new App\Services\Notification\NotificationService();
$ahmadNotifId = $notifService->send(
    4, // User ID Ahmad
    "Ujian Penilaian Tengah Semester",
    "Ujian PTS Matematika telah dibuka untuk kelas Anda.",
    "exam",
    "peserta/exams",
    "test_http_notif",
    901
);

// 4. Ahmad accesses /notifications page
$ahmadPage = makeRequest("{$baseUrl}/notifications", 'GET', [], $cookieAhmad);
assertHttp($ahmadPage['code'] === 200, "4. Ahmad membuka /notifications mengembalikan HTTP 200 OK", $ahmadPage);
assertHttp(str_contains($ahmadPage['body'], 'Pusat Notifikasi'), "5. Tampilan halaman notifikasi memuat 'Pusat Notifikasi'");

// 5. Ahmad accesses API unread count
$ahmadUnread = makeRequest("{$baseUrl}/api/notifications/unread-count", 'GET', [], $cookieAhmad);
assertHttp($ahmadUnread['code'] === 200, "6. GET /api/notifications/unread-count mengembalikan HTTP 200 OK", $ahmadUnread);
$unreadData = json_decode($ahmadUnread['body'], true);
assertHttp(isset($unreadData['unread_count']) && $unreadData['unread_count'] >= 1, "7. Jumlah unread count valid dan > 0");

// 6. Ahmad accesses API recent notifications
$ahmadRecent = makeRequest("{$baseUrl}/api/notifications/recent", 'GET', [], $cookieAhmad);
assertHttp($ahmadRecent['code'] === 200, "8. GET /api/notifications/recent mengembalikan HTTP 200 OK", $ahmadRecent);
$recentData = json_decode($ahmadRecent['body'], true);
assertHttp(!empty($recentData['notifications']), "9. Payload notifications memuat data notifikasi terkini");

// 7. Cross-User Security Check: Siswa Dewi tries to mark Ahmad's notification as read
$loginDewi = loginUser($baseUrl, 'siswa_dewi', 'password123', $cookieDewi);
assertHttp($loginDewi, "10. Login Siswa Dewi berhasil");

$dewiAttack = makeRequest("{$baseUrl}/api/notifications/{$ahmadNotifId}/read", 'POST', [], $cookieDewi);
$dewiAttackData = json_decode($dewiAttack['body'], true);
assertHttp(($dewiAttackData['status'] ?? '') === 'not_found_or_forbidden', "11. Percobaan Dewi memanipulasi notifikasi Ahmad DITOLAK (not_found_or_forbidden)", $dewiAttack);

// 8. Ahmad marks his own notification as read
$ahmadMark = makeRequest("{$baseUrl}/api/notifications/{$ahmadNotifId}/read", 'POST', [], $cookieAhmad);
$ahmadMarkData = json_decode($ahmadMark['body'], true);
assertHttp(($ahmadMarkData['status'] ?? '') === 'success', "12. Ahmad berhasil menandai notifikasinya sendiri via AJAX API");

// 9. Ahmad marks all as read
$ahmadMarkAll = makeRequest("{$baseUrl}/api/notifications/mark-all-read", 'POST', [], $cookieAhmad);
$ahmadMarkAllData = json_decode($ahmadMarkAll['body'], true);
assertHttp(($ahmadMarkAllData['status'] ?? '') === 'success' && $ahmadMarkAllData['unread_count'] === 0, "13. Ahmad menandai semua notifikasi telah dibaca via AJAX API");

// 10. Login Guru Budi accesses /notifications
$loginGuru = loginUser($baseUrl, 'guru1', 'password123', $cookieGuru);
assertHttp($loginGuru, "14. Login Guru Budi berhasil");
$guruPage = makeRequest("{$baseUrl}/notifications", 'GET', [], $cookieGuru);
assertHttp($guruPage['code'] === 200, "15. Guru Budi membuka /notifications mengembalikan HTTP 200 OK");

// 11. Login Super Admin accesses /notifications
$loginAdmin = loginUser($baseUrl, 'admin', 'password123', $cookieAdmin);
assertHttp($loginAdmin, "16. Login Super Admin berhasil");
$adminPage = makeRequest("{$baseUrl}/notifications", 'GET', [], $cookieAdmin);
assertHttp($adminPage['code'] === 200, "17. Super Admin membuka /notifications mengembalikan HTTP 200 OK");

// Cleanup test cookies and mock notif
$db = App\Core\Database::getConnection();
$db->exec("DELETE FROM notifications WHERE reference_type = 'test_http_notif'");

if (file_exists($cookieAhmad)) @unlink($cookieAhmad);
if (file_exists($cookieDewi)) @unlink($cookieDewi);
if (file_exists($cookieGuru)) @unlink($cookieGuru);
if (file_exists($cookieAdmin)) @unlink($cookieAdmin);

echo "\n========================================================\n";
echo "E2E NOTIFICATION HTTP TEST: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

exit($failCount > 0 ? 1 : 0);
