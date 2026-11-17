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
$userModel = new User();

$totalPass = 0;
$totalFail = 0;

function assertCheck(bool $condition, string $testName, string $detail = ''): void {
    global $totalPass, $totalFail;
    if ($condition) {
        $totalPass++;
        echo "  [PASS] {$testName}\n";
    } else {
        $totalFail++;
        echo "  [FAIL] {$testName}\n";
        if (!empty($detail)) {
            echo "         Detail: {$detail}\n";
        }
    }
}

echo "====================================================================\n";
echo "  PENGUJIAN CHATBOT+: IDENTITAS PENGGUNA & RESPONS ROLE (U-LINE)   \n";
echo "====================================================================\n\n";

// Ambil akun aktual dari database untuk masing-masing role
$adminAccount = (new User())->findByUsernameOrEmail('admin');
$guruAccount = (new User())->findByUsernameOrEmail('guru1');
$siswaAccount = (new User())->findByUsernameOrEmail('siswa1');

// -------------------------------------------------------------------------
// TEST A — SUPER ADMIN
// -------------------------------------------------------------------------
echo "--- TEST A: SUPER ADMINISTRATOR ---\n";
assertCheck(!empty($adminAccount), "A.0 Akun Admin ditemukan di database ({$adminAccount['name']})");

// Pertanyaan: "siapa saya"
$resA1 = $service->processChat('siapa saya', $adminAccount);
assertCheck($resA1['status'] === 'success', "A.1 Query 'siapa saya' status success");
assertCheck(str_contains($resA1['reply'], $adminAccount['name']), "A.2 Respons menyebutkan nama aktual Admin ({$adminAccount['name']})");
assertCheck(str_contains($resA1['reply'], 'Administrator'), "A.3 Respons menyebutkan role Administrator");
assertCheck(!str_contains($resA1['reply'], 'Bank Soal: Jumlah butir soal'), "A.4 Respons BUKAN panduan umum/fallback");

// Pertanyaan: "apa role saya"
$resA2 = $service->processChat('apa role saya', $adminAccount);
assertCheck(str_contains($resA2['reply'], 'Administrator'), "A.5 Query 'apa role saya' dijawab dengan role Administrator");

// -------------------------------------------------------------------------
// TEST B — GURU PENGUJI
// -------------------------------------------------------------------------
echo "\n--- TEST B: GURU PENGUJI ---\n";
assertCheck(!empty($guruAccount), "B.0 Akun Guru ditemukan di database ({$guruAccount['name']})");

// Pertanyaan: "siapa saya"
$resB1 = $service->processChat('siapa saya', $guruAccount);
assertCheck($resB1['status'] === 'success', "B.1 Query 'siapa saya' status success");
assertCheck(str_contains($resB1['reply'], $guruAccount['name']), "B.2 Respons menyebutkan nama aktual Guru ({$guruAccount['name']})");
assertCheck(str_contains($resB1['reply'], 'Guru Penguji'), "B.3 Respons menyebutkan role Guru Penguji");
assertCheck(!str_contains($resB1['reply'], 'Jadwal & Daftar Ujian sesuai kelas Anda'), "B.4 Respons Guru tidak tercampur template Peserta Didik");

// Pertanyaan: "siapa nama saya"
$resB2 = $service->processChat('siapa nama saya', $guruAccount);
assertCheck(str_contains($resB2['reply'], $guruAccount['name']), "B.5 Query 'siapa nama saya' menyebutkan nama Guru");

// -------------------------------------------------------------------------
// TEST C — PESERTA DIDIK
// -------------------------------------------------------------------------
echo "\n--- TEST C: PESERTA DIDIK ---\n";
assertCheck(!empty($siswaAccount), "C.0 Akun Siswa ditemukan di database ({$siswaAccount['name']})");

// Pertanyaan: "siapa saya"
$resC1 = $service->processChat('siapa saya', $siswaAccount);
assertCheck($resC1['status'] === 'success', "C.1 Query 'siapa saya' status success");
assertCheck(str_contains($resC1['reply'], $siswaAccount['name']), "C.2 Respons menyebutkan nama aktual Siswa ({$siswaAccount['name']})");
assertCheck(str_contains($resC1['reply'], 'Peserta Didik'), "C.3 Respons menyebutkan role Peserta Didik");

// Pertanyaan: "saya login sebagai siapa"
$resC2 = $service->processChat('saya login sebagai siapa', $siswaAccount);
assertCheck(str_contains($resC2['reply'], 'Peserta Didik'), "C.4 Query 'saya login sebagai siapa' menyebutkan role Peserta Didik");
assertCheck(str_contains($resC2['reply'], $siswaAccount['name']), "C.5 Query 'saya login sebagai siapa' menyebutkan nama Siswa");

// -------------------------------------------------------------------------
// TEST D — VARIASI PERTANYAAN (HURUF BESAR/KECIL & TANDA BACA)
// -------------------------------------------------------------------------
echo "\n--- TEST D: VARIASI PERTANYAAN IDENTITAS ---\n";
$variations = [
    'Siapa saya?',
    'SIAPA SAYA',
    'siapa nama saya?',
    'apa role saya?',
    'tolong beri tahu siapa yang sedang login',
    'Saya login sebagai siapa?',
    'Apa identitas akun saya?',
    'Siapa nama akun saya?',
    'Siapa pengguna yang sedang login?'
];

foreach ($variations as $idx => $q) {
    $num = $idx + 1;
    $res = $service->processChat($q, $guruAccount);
    $matched = str_contains($res['reply'], $guruAccount['name']) && str_contains($res['reply'], 'Guru Penguji');
    assertCheck($matched, "D.{$num} Variasi '{$q}' dijawab akurat dengan identitas akun", $res['reply']);
}

// -------------------------------------------------------------------------
// TEST E — REGRESSION & FITUR DATABASE CHATBOT LAINNYA
// -------------------------------------------------------------------------
echo "\n--- TEST E: REGRESSION FITUR DATABASE LAINNYA ---\n";

// 1. Pertanyaan Bank Soal Guru
$resE1 = $service->processChat('berapa butir soal saya', $guruAccount);
assertCheck(str_contains($resE1['reply'], 'Bank Soal'), "E.1 Pertanyaan Bank Soal guru tetap bekerja");

// 2. Pertanyaan Jadwal Ujian Siswa
$resE2 = $service->processChat('jadwal ujian kelas saya', $siswaAccount);
assertCheck(str_contains($resE2['reply'], 'jadwal ujian') || str_contains($resE2['reply'], 'Penilaian Harian'), "E.2 Pertanyaan jadwal ujian siswa tetap bekerja");

// 3. Hak Akses Privasi Hasil Ujian
$resE3 = $service->processChat('lihat nilai ahmad', $siswaAccount);
assertCheck(str_contains($resE3['reply'], 'Akses ditolak'), "E.3 Percobaan intip nilai siswa lain tetap dicegat (Privacy Guard)");

// 4. Pertanyaan yang Tidak Relevan Memicu Fallback Ramah (bukan siapa saya)
$resE4 = $service->processChat('bagaimana cara merakit komputer', $adminAccount);
assertCheck(str_contains($resE4['reply'], 'Saya asisten resmi U-Line yang terhubung langsung ke database'), "E.4 Pertanyaan di luar konteks memicu fallback adaptif");

// -------------------------------------------------------------------------
// REKAPITULASI HASIL
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "  HASIL AKHIR PENGUJIAN IDENTITAS & ROLE CHATBOT+:\n";
echo "  TOTAL PASS : {$totalPass}\n";
echo "  TOTAL FAIL : {$totalFail}\n";
echo "====================================================================\n\n";

if ($totalFail === 0) {
    echo ">>> SELURUH PENGUJIAN IDENTITAS DAN ROLE CHATBOT+ BERHASIL 100%! <<<\n";
    exit(0);
} else {
    echo ">>> TERDAPAT PENGUJIAN YANG GAGAL. <<<\n";
    exit(1);
}
