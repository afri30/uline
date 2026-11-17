<?php

declare(strict_types=1);

$port = $argv[1] ?? '8096';
$baseUrl = "http://127.0.0.1:{$port}";

$cookieFileGuru = tempnam(sys_get_temp_dir(), 'guru_res_');
$cookieFileSiti = tempnam(sys_get_temp_dir(), 'siti_res_');
$cookieFileSiswa = tempnam(sys_get_temp_dir(), 'siswa_res_');

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

echo "========================================================\n";
echo "  E2E HTTP ROUTE TESTING - EXAM RESULTS & CSV EXPORT\n";
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

// 1. Login as Guru Budi
$guruLogged = loginUser($baseUrl, 'guru_budi', 'password123', $cookieFileGuru);
assertHttp($guruLogged, "1. Guru Budi login successful (302 Redirect)");

// 2. Guru Results Index (/guru/results)
$resIndex = makeRequest("{$baseUrl}/guru/results", "GET", [], $cookieFileGuru);
assertHttp($resIndex['code'] === 200, "2. /guru/results returns 200 OK", $resIndex);
assertHttp(strpos($resIndex['body'], 'Hasil &amp; Penilaian Ujian') !== false || strpos($resIndex['body'], 'Hasil & Penilaian Ujian') !== false, "3. /guru/results contains heading title");
assertHttp(strpos($resIndex['body'], 'Penilaian Harian 1') !== false, "4. /guru/results lists Exam 1");

// 3. Guru Exam Detail & Ranking (/guru/results/1)
$resExam = makeRequest("{$baseUrl}/guru/results/1", "GET", [], $cookieFileGuru);
assertHttp($resExam['code'] === 200, "5. /guru/results/1 returns 200 OK");
assertHttp(strpos($resExam['body'], 'Hasil &amp; Peringkat Peserta') !== false || strpos($resExam['body'], 'Hasil & Peringkat Peserta') !== false, "6. /guru/results/1 contains ranking header");
assertHttp(strpos($resExam['body'], 'Ahmad Pratama') !== false, "7. /guru/results/1 lists participant Ahmad Pratama");
assertHttp(strpos($resExam['body'], 'Export Nilai (CSV)') !== false, "8. /guru/results/1 contains Export CSV button");

// 4. Guru Student Answer Sheet Detail (/guru/results/1/1)
$resStudent = makeRequest("{$baseUrl}/guru/results/1/1", "GET", [], $cookieFileGuru);
assertHttp($resStudent['code'] === 200, "9. /guru/results/1/1 returns 200 OK");
assertHttp(strpos($resStudent['body'], 'Lembar Jawaban Peserta') !== false, "10. /guru/results/1/1 contains answer sheet header");
assertHttp(strpos($resStudent['body'], 'Nilai Akhir') !== false, "11. /guru/results/1/1 contains scoreboard summary");

// 5. CSV Export (/guru/results/export?exam_id=1)
$resCsv = makeRequest("{$baseUrl}/guru/results/export?exam_id=1", "GET", [], $cookieFileGuru);
assertHttp($resCsv['code'] === 200, "12. /guru/results/export?exam_id=1 returns 200 OK");
assertHttp(strpos($resCsv['header'], 'text/csv') !== false, "13. CSV export response header is Content-Type: text/csv");
assertHttp(strpos($resCsv['body'], 'LAPORAN HASIL PENILAIAN UJIAN') !== false, "14. CSV export contains report title");
assertHttp(strpos($resCsv['body'], 'Ahmad Pratama') !== false, "15. CSV export body contains student rows");

// 6. Guru Siti trying to access Guru Budi's exam (Cross-teacher access control)
$sitiLogged = loginUser($baseUrl, 'guru_siti', 'password123', $cookieFileSiti);
assertHttp($sitiLogged, "16. Guru Siti login successful");

$resSitiCross = makeRequest("{$baseUrl}/guru/results/1", "GET", [], $cookieFileSiti);
assertHttp($resSitiCross['code'] === 302, "17. Guru Siti is blocked from viewing Guru Budi's exam result (302 Redirect to /guru/results)", $resSitiCross);

// 7. Login as Siswa Ahmad
$siswaLogged = loginUser($baseUrl, 'siswa_ahmad', 'password123', $cookieFileSiswa);
assertHttp($siswaLogged, "18. Siswa Ahmad login successful");

// 8. Siswa Results Page (/peserta/results)
$resPeserta = makeRequest("{$baseUrl}/peserta/results", "GET", [], $cookieFileSiswa);
assertHttp($resPeserta['code'] === 200, "19. /peserta/results returns 200 OK");
assertHttp(strpos($resPeserta['body'], 'Riwayat &amp; Hasil Ujian Saya') !== false || strpos($resPeserta['body'], 'Riwayat & Hasil Ujian Saya') !== false, "20. /peserta/results contains student result header");
assertHttp(strpos($resPeserta['body'], 'Penilaian Harian 1') !== false, "21. /peserta/results contains student's completed exam");

echo "\n========================================================\n";
echo "  E2E TEST SUMMARY: {$pass} / {$total} PASSED\n";
echo "========================================================\n";

@unlink($cookieFileGuru);
@unlink($cookieFileSiti);
@unlink($cookieFileSiswa);

if ($pass === $total) {
    echo "SUCCESS: ALL E2E HTTP TESTS PASSED!\n";
    exit(0);
} else {
    echo "ERROR: SOME E2E HTTP TESTS FAILED.\n";
    exit(1);
}
