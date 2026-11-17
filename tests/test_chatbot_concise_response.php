<?php

declare(strict_types=1);

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

use App\Services\AI\ChatbotDatabaseService;
use App\Models\User;

$service = new ChatbotDatabaseService();
$admin = (new User())->findByUsernameOrEmail('admin');

function checkTest(string $desc, bool $condition, string $got = ''): void {
    if ($condition) {
        echo "  [PASS] {$desc}\n";
    } else {
        echo "  [FAIL] {$desc}\n";
        if (!empty($got)) {
            echo "         Got: {$got}\n";
        }
    }
}

echo "====================================================================\n";
echo "  TEST CONCISE & FOCUSED CHATBOT RESPONSES (ANTI-HALU / ANTI-BLOAT) \n";
echo "====================================================================\n\n";

// 1. "ada berapa total pengguna" (User Screenshot Case)
$r1 = $service->processChat('ada berapa total pengguna', $admin);
$rep1 = $r1['reply'];
echo "--- 1. Query: 'ada berapa total pengguna' ---\n";
echo "Bot Reply:\n{$rep1}\n\n";

checkTest("1.1 Menyebutkan 15 pengguna", str_contains($rep1, '15 pengguna'));
checkTest("1.2 Menyebutkan 1 Super Administrator", str_contains($rep1, '1') && str_contains($rep1, 'Super Administrator'));
checkTest("1.3 Menyebutkan 4 Guru Penguji", str_contains($rep1, '4') && str_contains($rep1, 'Guru Penguji'));
checkTest("1.4 Menyebutkan 10 Peserta Didik", str_contains($rep1, '10') && str_contains($rep1, 'Peserta Didik'));
checkTest("1.5 TIDAK menyebutkan Kelas / Rombel", !str_contains($rep1, 'Kelas') && !str_contains($rep1, 'Rombel'));
checkTest("1.6 TIDAK menyebutkan Mata Pelajaran", !str_contains($rep1, 'Mata Pelajaran') && !str_contains($rep1, 'Mapel'));
checkTest("1.7 TIDAK menyebutkan Ujian", !str_contains($rep1, 'Ujian Terdaftar') && !str_contains($rep1, 'Bank Soal'));
checkTest("1.8 TIDAK memuat menu promosi (/admin/users)", !str_contains($rep1, '/admin/users'));

// 2. "ada berapa data guru" (Count only)
$r2 = $service->processChat('ada berapa data guru', $admin);
$rep2 = $r2['reply'];
echo "\n--- 2. Query: 'ada berapa data guru' ---\n";
echo "Bot Reply:\n{$rep2}\n\n";
checkTest("2.1 Menjawab ringkas jumlah 4 guru penguji", str_contains($rep2, '4') && str_contains($rep2, 'Guru Penguji'));
checkTest("2.2 Jawaban ringkas satu kalimat tanpa daftar panjang", !str_contains($rep2, "1. ") && !str_contains($rep2, "2. "));

// 3. "ada berapa siswa" (Count only)
$r3 = $service->processChat('ada berapa siswa', $admin);
$rep3 = $r3['reply'];
echo "\n--- 3. Query: 'ada berapa siswa' ---\n";
echo "Bot Reply:\n{$rep3}\n\n";
checkTest("3.1 Menjawab ringkas jumlah 10 siswa", str_contains($rep3, '10') && str_contains($rep3, 'Siswa'));
checkTest("3.2 Tidak membuang seluruh daftar nama siswa", !str_contains($rep3, 'Daftar Siswa'));

// 4. "ada berapa kelas" (Count only)
$r4 = $service->processChat('ada berapa kelas', $admin);
$rep4 = $r4['reply'];
echo "\n--- 4. Query: 'ada berapa kelas' ---\n";
echo "Bot Reply:\n{$rep4}\n\n";
checkTest("4.1 Menjawab ringkas jumlah 3 kelas / rombel", str_contains($rep4, '3') && str_contains($rep4, 'kelas'));

// 5. "ada berapa ujian" (Count only)
$r5 = $service->processChat('ada berapa ujian', $admin);
$rep5 = $r5['reply'];
echo "\n--- 5. Query: 'ada berapa ujian' ---\n";
echo "Bot Reply:\n{$rep5}\n\n";
checkTest("5.1 Menjawab ringkas jumlah 2 ujian", str_contains($rep5, '2') && str_contains($rep5, 'ujian'));

echo "\n====================================================================\n";

