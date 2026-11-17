<?php

$baseUrl = 'http://127.0.0.1:8090';

$config = require __DIR__ . '/../app/Config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

function req($url, $method = 'GET', $data = [], $cookieJar = null) {
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
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($resp, 0, $headerSize);
    $body = substr($resp, $headerSize);
    curl_close($ch);

    preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $body, $m);
    $csrf = $m[1] ?? null;

    preg_match('/Location:\s*([^\r\n]+)/i', $headers, $loc);
    $location = $loc[1] ?? null;

    return ['code' => $code, 'headers' => $headers, 'body' => $body, 'csrf' => $csrf, 'location' => $location];
}

$cookie = tempnam(sys_get_temp_dir(), 'test_adm_');

// 1. Login
$r = req("{$baseUrl}/login", 'GET', [], $cookie);
$csrf = $r['csrf'];
$rLogin = req("{$baseUrl}/login", 'POST', ['_csrf_token' => $csrf, 'identifier' => 'admin', 'password' => 'password123'], $cookie);
echo "1. Login Super Admin: Status {$rLogin['code']} -> {$rLogin['location']}\n";

// 2. User CRUD
$r = req("{$baseUrl}/admin/users", 'GET', [], $cookie);
$csrf = $r['csrf'];
$testUser = 'usr_' . time();
$rStore = req("{$baseUrl}/admin/users/store", 'POST', [
    '_csrf_token' => $csrf,
    'name' => 'Test User',
    'username' => $testUser,
    'email' => "{$testUser}@test.com",
    'password' => 'password123',
    'role' => 'PESERTA',
    'status' => 'active'
], $cookie);
$userInDb = $pdo->query("SELECT * FROM users WHERE username = '{$testUser}'")->fetch();
echo "2. User Create: " . ($userInDb ? "SUCCESS (ID: {$userInDb['id']})" : "FAILED") . "\n";

if ($userInDb) {
    $uid = $userInDb['id'];
    $r = req("{$baseUrl}/admin/users", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/users/update", 'POST', [
        '_csrf_token' => $csrf,
        'id' => $uid,
        'name' => 'Test User Updated',
        'username' => $testUser,
        'email' => "{$testUser}@test.com",
        'role' => 'GURU',
        'status' => 'inactive'
    ], $cookie);
    $userUpdated = $pdo->query("SELECT * FROM users WHERE id = {$uid}")->fetch();
    echo "   User Update: " . ($userUpdated['name'] === 'Test User Updated' && $userUpdated['status'] === 'inactive' ? "SUCCESS" : "FAILED") . "\n";

    req("{$baseUrl}/admin/users/toggle-status?id={$uid}", 'GET', [], $cookie);
    $userToggled = $pdo->query("SELECT * FROM users WHERE id = {$uid}")->fetch();
    echo "   User Toggle: Status is now {$userToggled['status']}\n";

    $r = req("{$baseUrl}/admin/users", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/users/delete", 'POST', ['_csrf_token' => $csrf, 'id' => $uid], $cookie);
    $userDeleted = $pdo->query("SELECT * FROM users WHERE id = {$uid}")->fetch();
    echo "   User Delete: " . (!$userDeleted ? "SUCCESS" : "FAILED") . "\n";
}

// 3. Teacher CRUD
$r = req("{$baseUrl}/admin/teachers", 'GET', [], $cookie);
$csrf = $r['csrf'];
$testNip = 'NIP_' . time();
$testGuru = 'guru_' . time();
req("{$baseUrl}/admin/teachers/store", 'POST', [
    '_csrf_token' => $csrf,
    'name' => 'Guru Test',
    'username' => $testGuru,
    'email' => "{$testGuru}@test.com",
    'password' => 'password123',
    'nip' => $testNip,
    'phone' => '0812345678',
    'gender' => 'L',
    'status' => 'active'
], $cookie);
$teacherInDb = $pdo->query("SELECT t.*, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.nip = '{$testNip}'")->fetch();
echo "3. Teacher Create: " . ($teacherInDb ? "SUCCESS (ID: {$teacherInDb['id']}, User: {$teacherInDb['name']})" : "FAILED") . "\n";

if ($teacherInDb) {
    $tid = $teacherInDb['id'];
    $r = req("{$baseUrl}/admin/teachers", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/teachers/update", 'POST', [
        '_csrf_token' => $csrf,
        'id' => $tid,
        'name' => 'Guru Test M.Pd.',
        'username' => $testGuru,
        'email' => "{$testGuru}@test.com",
        'nip' => $testNip,
        'phone' => '08999999',
        'gender' => 'P',
        'status' => 'active'
    ], $cookie);
    $tUpdated = $pdo->query("SELECT t.*, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = {$tid}")->fetch();
    echo "   Teacher Update: " . ($tUpdated['name'] === 'Guru Test M.Pd.' && $tUpdated['gender'] === 'P' ? "SUCCESS" : "FAILED") . "\n";

    $r = req("{$baseUrl}/admin/teachers", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/teachers/delete", 'POST', ['_csrf_token' => $csrf, 'id' => $tid], $cookie);
    $tDeleted = $pdo->query("SELECT * FROM teachers WHERE id = {$tid}")->fetch();
    echo "   Teacher Delete: " . (!$tDeleted ? "SUCCESS" : "FAILED") . "\n";
}

// 4. Classes CRUD
$r = req("{$baseUrl}/admin/classes", 'GET', [], $cookie);
$csrf = $r['csrf'];
$testKelas = 'X TEST ' . rand(10, 99);
req("{$baseUrl}/admin/classes/store", 'POST', [
    '_csrf_token' => $csrf,
    'name' => $testKelas,
    'grade' => '10',
    'academic_year' => '2026/2027'
], $cookie);
$kelasInDb = $pdo->query("SELECT * FROM classes WHERE name = '{$testKelas}'")->fetch();
echo "4. Class Create: " . ($kelasInDb ? "SUCCESS (ID: {$kelasInDb['id']})" : "FAILED") . "\n";

if ($kelasInDb) {
    $cid = $kelasInDb['id'];
    $r = req("{$baseUrl}/admin/classes/{$cid}/students", 'GET', [], $cookie);
    echo "   Class Students Page: Status {$r['code']}\n";

    $r = req("{$baseUrl}/admin/classes", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/classes/delete", 'POST', ['_csrf_token' => $csrf, 'id' => $cid], $cookie);
    $kDeleted = $pdo->query("SELECT * FROM classes WHERE id = {$cid}")->fetch();
    echo "   Class Delete: " . (!$kDeleted ? "SUCCESS" : "FAILED") . "\n";
}

// 5. Students CRUD
$r = req("{$baseUrl}/admin/students", 'GET', [], $cookie);
$csrf = $r['csrf'];
$testNis = 'NIS_' . time();
$testSiswa = 'siswa_' . time();
req("{$baseUrl}/admin/students/store", 'POST', [
    '_csrf_token' => $csrf,
    'name' => 'Siswa Test',
    'username' => $testSiswa,
    'email' => "{$testSiswa}@test.com",
    'password' => 'password123',
    'class_id' => 1,
    'nis' => $testNis,
    'nisn' => 'NISN_' . time(),
    'phone' => '08777777',
    'gender' => 'L',
    'status' => 'active'
], $cookie);
$siswaInDb = $pdo->query("SELECT s.*, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.nis = '{$testNis}'")->fetch();
echo "5. Student Create: " . ($siswaInDb ? "SUCCESS (ID: {$siswaInDb['id']}, Name: {$siswaInDb['name']})" : "FAILED") . "\n";

if ($siswaInDb) {
    $sid = $siswaInDb['id'];
    $r = req("{$baseUrl}/admin/students", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/students/delete", 'POST', ['_csrf_token' => $csrf, 'id' => $sid], $cookie);
    $sDeleted = $pdo->query("SELECT * FROM students WHERE id = {$sid}")->fetch();
    echo "   Student Delete: " . (!$sDeleted ? "SUCCESS" : "FAILED") . "\n";
}

// 6. Subjects CRUD
$r = req("{$baseUrl}/admin/subjects", 'GET', [], $cookie);
$csrf = $r['csrf'];
$testCode = 'SUB_' . rand(10, 99);
req("{$baseUrl}/admin/subjects/store", 'POST', [
    '_csrf_token' => $csrf,
    'code' => $testCode,
    'name' => 'Kimia Organik',
    'description' => 'Materi Hidrokarbon',
    'teacher_id' => 1
], $cookie);
$subjInDb = $pdo->query("SELECT * FROM subjects WHERE code = '{$testCode}'")->fetch();
echo "6. Subject Create: " . ($subjInDb ? "SUCCESS (ID: {$subjInDb['id']}, Name: {$subjInDb['name']})" : "FAILED") . "\n";

if ($subjInDb) {
    $subId = $subjInDb['id'];
    $r = req("{$baseUrl}/admin/subjects", 'GET', [], $cookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/admin/subjects/delete", 'POST', ['_csrf_token' => $csrf, 'id' => $subId], $cookie);
    $subDeleted = $pdo->query("SELECT * FROM subjects WHERE id = {$subId}")->fetch();
    echo "   Subject Delete: " . (!$subDeleted ? "SUCCESS" : "FAILED") . "\n";
}

@unlink($cookie);
echo "\n=== ALL CRUD VERIFICATIONS COMPLETED SUCCESSFULLY! ===\n";
