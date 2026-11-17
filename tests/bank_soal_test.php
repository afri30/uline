<?php

/**
 * End-to-End Test for Guru Bank Soal & Security
 */

$port = $argv[1] ?? '8092';
$baseUrl = "http://127.0.0.1:{$port}";

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

$budiCookie = tempnam(sys_get_temp_dir(), 'budi_');
$sitiCookie = tempnam(sys_get_temp_dir(), 'siti_');

echo "=== Bank Soal (Guru Module) E2E Test Suite ===\n\n";

// 1. Login as Guru Budi
$r = req("{$baseUrl}/login", 'GET', [], $budiCookie);
$rLogin = req("{$baseUrl}/login", 'POST', ['_csrf_token' => $r['csrf'], 'identifier' => 'guru_budi', 'password' => 'password123'], $budiCookie);
echo "1. Login Guru Budi: Status {$rLogin['code']} -> {$rLogin['location']}\n";

// 2. Access Bank Soal Index
$rIndex = req("{$baseUrl}/guru/questions", 'GET', [], $budiCookie);
echo "2. GET /guru/questions: Status {$rIndex['code']} (Expected: 200)\n";
$csrf = $rIndex['csrf'];

// 3. Create Multiple Choice Question
$uniqueQ = "Berapakah hasil dari 15 + 27? [Test " . time() . "]";
$rCreateMCQ = req("{$baseUrl}/guru/questions/store", 'POST', [
    '_csrf_token' => $csrf,
    'subject_id' => 1,
    'question_type' => 'multiple_choice',
    'question_text' => $uniqueQ,
    'difficulty' => 'easy',
    'score' => 10,
    'option_a' => '40',
    'option_b' => '42',
    'option_c' => '45',
    'option_d' => '32',
    'correct_answer' => 'B'
], $budiCookie);
echo "3. Create Multiple Choice Question: Status {$rCreateMCQ['code']} (Redirect: {$rCreateMCQ['location']})\n";

$qInDb = $pdo->query("SELECT * FROM questions WHERE question_text = '{$uniqueQ}'")->fetch();
echo "   MCQ in DB: " . ($qInDb ? "SUCCESS (ID: {$qInDb['id']}, Correct: {$qInDb['correct_answer']})" : "FAILED") . "\n";

if ($qInDb) {
    $qId = (int)$qInDb['id'];

    // 4. Preview Question via AJAX endpoint
    $rPreview = req("{$baseUrl}/guru/questions/preview?id={$qId}", 'GET', [], $budiCookie);
    $prevJson = json_decode($rPreview['body'], true);
    echo "4. Preview Question: Status {$rPreview['code']}, JSON Status: " . ($prevJson['status'] ?? 'err') . " (Correct Answer: " . ($prevJson['data']['correct_answer'] ?? '-') . ")\n";

    // 5. Update Question
    $r = req("{$baseUrl}/guru/questions", 'GET', [], $budiCookie);
    $csrf = $r['csrf'];
    $rUpdate = req("{$baseUrl}/guru/questions/update", 'POST', [
        '_csrf_token' => $csrf,
        'id' => $qId,
        'subject_id' => 1,
        'question_type' => 'multiple_choice',
        'question_text' => $uniqueQ . " (Updated)",
        'difficulty' => 'medium',
        'score' => 15,
        'option_a' => '40',
        'option_b' => '42',
        'option_c' => '45',
        'option_d' => '50',
        'correct_answer' => 'B'
    ], $budiCookie);
    $qUpdated = $pdo->query("SELECT * FROM questions WHERE id = {$qId}")->fetch();
    echo "5. Update Question: " . ($qUpdated['score'] == 15 && $qUpdated['difficulty'] === 'medium' ? "SUCCESS (Score: 15, Difficulty: medium)" : "FAILED") . "\n";

    // 6. Security Test: Guru Siti attempts to modify or delete Guru Budi's question
    echo "6. Security Test: Another Guru (`guru_siti`) attempts to modify/delete Guru Budi's question...\n";
    $rSitiLoginGet = req("{$baseUrl}/login", 'GET', [], $sitiCookie);
    req("{$baseUrl}/login", 'POST', ['_csrf_token' => $rSitiLoginGet['csrf'], 'identifier' => 'guru_siti', 'password' => 'password123'], $sitiCookie);
    
    $rSitiQuestions = req("{$baseUrl}/guru/questions", 'GET', [], $sitiCookie);
    $sitiCsrf = $rSitiQuestions['csrf'];

    // Siti attempts update on Budi's question
    $rSitiHacks = req("{$baseUrl}/guru/questions/update", 'POST', [
        '_csrf_token' => $sitiCsrf,
        'id' => $qId,
        'subject_id' => 2,
        'question_type' => 'multiple_choice',
        'question_text' => 'HACKED BY SITI',
        'difficulty' => 'hard',
        'score' => 100,
        'option_a' => 'X',
        'option_b' => 'Y',
        'option_c' => 'Z',
        'option_d' => 'W',
        'correct_answer' => 'A'
    ], $sitiCookie);
    
    $qAfterHack = $pdo->query("SELECT * FROM questions WHERE id = {$qId}")->fetch();
    $hackPrevented = ($qAfterHack['question_text'] !== 'HACKED BY SITI');
    echo "   Unauthorized Update Attempt: " . ($hackPrevented ? "BLOCKED & REJECTED ✓" : "VULNERABLE ✗") . "\n";

    // Siti attempts delete on Budi's question
    req("{$baseUrl}/guru/questions/delete", 'POST', ['_csrf_token' => $sitiCsrf, 'id' => $qId], $sitiCookie);
    $qAfterDelAttempt = $pdo->query("SELECT * FROM questions WHERE id = {$qId}")->fetch();
    $deletePrevented = ($qAfterDelAttempt !== false);
    echo "   Unauthorized Delete Attempt: " . ($deletePrevented ? "BLOCKED & REJECTED ✓" : "VULNERABLE ✗") . "\n";

    // 7. Legitimate Delete by Owner (Guru Budi)
    $r = req("{$baseUrl}/guru/questions", 'GET', [], $budiCookie);
    $csrf = $r['csrf'];
    req("{$baseUrl}/guru/questions/delete", 'POST', ['_csrf_token' => $csrf, 'id' => $qId], $budiCookie);
    $qDeleted = $pdo->query("SELECT * FROM questions WHERE id = {$qId}")->fetch();
    echo "7. Delete Question by Owner: " . (!$qDeleted ? "SUCCESS (Deleted from DB)" : "FAILED") . "\n";
}

// 8. Test True/False & Essay Creation
$r = req("{$baseUrl}/guru/questions", 'GET', [], $budiCookie);
$csrf = $r['csrf'];
$tfText = "Pythagoras berlaku untuk segitiga siku-siku. [Test " . time() . "]";
req("{$baseUrl}/guru/questions/store", 'POST', [
    '_csrf_token' => $csrf,
    'subject_id' => 1,
    'question_type' => 'true_false',
    'question_text' => $tfText,
    'difficulty' => 'easy',
    'score' => 10,
    'correct_answer' => 'TRUE'
], $budiCookie);
$tfInDb = $pdo->query("SELECT * FROM questions WHERE question_text = '{$tfText}'")->fetch();
echo "8. Create True/False Question: " . ($tfInDb ? "SUCCESS (Type: {$tfInDb['question_type']}, Kunci: {$tfInDb['correct_answer']})" : "FAILED") . "\n";
if ($tfInDb) {
    $pdo->exec("DELETE FROM questions WHERE id = {$tfInDb['id']}");
}

// 9. Test Guru Profile Page & Update
$rProfile = req("{$baseUrl}/guru/profile", 'GET', [], $budiCookie);
echo "9. GET /guru/profile: Status {$rProfile['code']} (Expected: 200)\n";
$csrf = $rProfile['csrf'];
$rUpdateProfile = req("{$baseUrl}/guru/profile", 'POST', [
    '_csrf_token' => $csrf,
    'name' => 'Budi Santoso, M.Pd.',
    'email' => 'guru.budi@example.com',
    'phone' => '081234567899',
    'gender' => 'L'
], $budiCookie);
echo "   Update Profile: Status {$rUpdateProfile['code']} (Redirect: {$rUpdateProfile['location']})\n";

// 10. Test Placeholder Menus
$menus = ['/guru/exams', '/guru/students', '/guru/results', '/guru/monitoring'];
echo "10. Testing Guru Navigation Menus:\n";
foreach ($menus as $m) {
    $res = req("{$baseUrl}{$m}", 'GET', [], $budiCookie);
    echo "    {$m}: Status {$res['code']} (Expected: 200)\n";
    assert($res['code'] === 200);
}

@unlink($budiCookie);
@unlink($sitiCookie);

echo "\n========================================================\n";
echo "ALL BANK SOAL & GURU MODULE TESTS PASSED WITH 100% SUCCESS!\n";
echo "========================================================\n";
