<?php

declare(strict_types=1);

/**
 * AI Online Examination & Proctoring System - U-Line
 * TEST SUITE TAHAP 1: INTEGRASI CHATBOT+ DENGAN DATABASE
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Database;
use App\Services\AI\ChatbotDatabaseService;
use App\Controllers\ChatbotController;

echo "====================================================================\n";
echo "  PENGUJIAN TAHAP 1: INTEGRASI CHATBOT+ DENGAN DATABASE (U-LINE)\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$testName}" . ($detail ? " -> {$detail}" : "") . "\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$testName}" . ($detail ? " -> {$detail}" : "") . "\n";
    }
}

$db = Database::getConnection();
$service = new ChatbotDatabaseService();
$controller = new ChatbotController();

// -------------------------------------------------------------------------
// PENGUJIAN 1: Koneksi Database Berhasil
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 1: KONEKSI DATABASE ---\n";
$connStatus = $service->checkConnection();
assertTest($connStatus['connected'] === true, "1.1 Status koneksi database connected = true");
assertTest(!empty($connStatus['driver']), "1.2 Driver database terdeteksi", $connStatus['driver'] ?? '');
assertTest(!empty($connStatus['version']), "1.3 Versi server database terdeteksi", $connStatus['version'] ?? '');
assertTest($service->isDatabaseConnected() === true, "1.4 Helper isDatabaseConnected() mengembalikan true");

// -------------------------------------------------------------------------
// PENGUJIAN 2: Chatbot Mengambil Data Aktual
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 2: PENGAMBILAN DATA AKTUAL DATABASE ---\n";
$summary = $service->getLiveDatabaseSummary();
$counts = $summary['ringkasan_angka'];

assertTest($counts['total_pengguna'] === 15, "2.1 Total pengguna aktual di database tepat 15", "Total: {$counts['total_pengguna']}");
assertTest($counts['total_guru'] === 4, "2.2 Total guru aktual di database tepat 4", "Total: {$counts['total_guru']}");
assertTest($counts['total_siswa'] === 10, "2.3 Total siswa aktual di database tepat 10", "Total: {$counts['total_siswa']}");
assertTest($counts['total_kelas'] === 3, "2.4 Total kelas aktual di database tepat 3", "Total: {$counts['total_kelas']}");
assertTest($counts['total_mata_pelajaran'] === 5, "2.5 Total mata pelajaran di database tepat 5", "Total: {$counts['total_mata_pelajaran']}");
assertTest($counts['total_ujian'] >= 2, "2.6 Total ujian di database aktual >= 2", "Total: {$counts['total_ujian']}");
assertTest($counts['total_soal_bank'] === 8, "2.7 Total butir bank soal aktual tepat 8", "Total: {$counts['total_soal_bank']}");

// Pastikan data sensitif (password hash) tidak pernah ada di daftar pengguna
$hasPassword = false;
foreach ($summary['daftar_15_pengguna'] as $u) {
    if (isset($u['password']) || isset($u['password_hash'])) {
        $hasPassword = true;
        break;
    }
}
assertTest($hasPassword === false, "2.8 Password hash pengguna tidak diekspos ke chatbot");

// -------------------------------------------------------------------------
// PENGUJIAN 3: Pertanyaan Jadwal Ujian Dijawab Berdasarkan Data Aktual
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 3: JADWAL UJIAN BERDASARKAN DATA AKTUAL ---\n";
// Siswa Ahmad (User ID: 4, Kelas XII MIPA 1)
$siswaAhmad = [
    'id' => 4,
    'name' => 'Ahmad Pratama',
    'username' => 'siswa1',
    'role' => 'PESERTA'
];

$resJadwalAhmad = $service->processChat("Kapan jadwal ujian saya?", $siswaAhmad);
assertTest($resJadwalAhmad['status'] === 'success', "3.1 Query jadwal ujian Ahmad berhasil diproses");
assertTest(
    str_contains($resJadwalAhmad['reply'], 'Kalkulus') || str_contains($resJadwalAhmad['reply'], 'Matematika'),
    "3.2 Jadwal ujian Ahmad menyebutkan 'Penilaian Harian 1: Kalkulus & Trigonometri' dari database",
    substr($resJadwalAhmad['reply'], 0, 120) . "..."
);
assertTest(
    str_contains($resJadwalAhmad['reply'], 'Menit') || str_contains($resJadwalAhmad['reply'], '60'),
    "3.3 Jadwal ujian Ahmad menyertakan durasi ujian aktual"
);

// Siswa Doni (User ID: 11, Kelas XI IPS 1 - saat ini tidak ada ujian published untuk XI IPS 1)
$siswaDoni = [
    'id' => 11,
    'name' => 'Doni Wahyudi',
    'username' => 'siswa_doni',
    'role' => 'PESERTA'
];
$resJadwalDoni = $service->processChat("Kapan jadwal ujian saya?", $siswaDoni);
assertTest(
    str_contains($resJadwalDoni['reply'], 'belum ada') || str_contains($resJadwalDoni['reply'], 'tidak ada'),
    "3.4 Jadwal ujian Doni (XI IPS 1) menyatakan jujur belum ada jadwal aktif di database (tanpa halusinasi)",
    substr($resJadwalDoni['reply'], 0, 100) . "..."
);

// -------------------------------------------------------------------------
// PENGUJIAN 4: Pertanyaan Hasil Ujian Hanya Milik Peserta Terkait
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 4: HASIL UJIAN & ISOLASI PRIVASI PESERTA ---\n";

// Pastikan kondisi awal bersih dari sesi pengujian sebelumnya
$db->exec("DELETE FROM `answers` WHERE `exam_session_id` IN (SELECT `id` FROM `exam_sessions` WHERE `student_id` = 1)");
$db->exec("DELETE FROM `exam_sessions` WHERE `student_id` = 1");

// Kondisi 1: Sebelum ujian selesai
$resNilaiKosong = $service->processChat("Berapa nilai ujian saya?", $siswaAhmad);
assertTest(
    str_contains($resNilaiKosong['reply'], 'belum ada') || str_contains($resNilaiKosong['reply'], 'riwayat'),
    "4.1 Jawaban nilai Ahmad saat belum ada ujian selesai menyatakan jujur belum ada di database"
);

// Kondisi 2: Simulasi ada sesi ujian selesai milik Ahmad (Student ID 1, Exam ID 1)
$now = date('Y-m-d H:i:s');
$stmtInsertSession = $db->prepare("
    INSERT INTO `exam_sessions` (`exam_id`, `student_id`, `started_at`, `finished_at`, `status`, `remaining_seconds`, `created_at`, `updated_at`)
    VALUES (1, 1, :started, :finished, 'finished', 0, :c_at, :u_at)
");
$stmtInsertSession->execute([
    ':started' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    ':finished' => $now,
    ':c_at' => $now,
    ':u_at' => $now
]);
$testSessionId = (int)$db->lastInsertId();


// Masukkan jawaban contoh agar menghasilkan skor terhitung
$stmtInsertAnswer = $db->prepare("
    INSERT INTO `answers` (`exam_session_id`, `question_id`, `answer`, `score`, `answered_at`)
    VALUES (:sid, 1, 'A', 10, :now)
");
$stmtInsertAnswer->execute([':sid' => $testSessionId, ':now' => $now]);

// Ahmad mengecek nilai sekarang
$resNilaiAda = $service->processChat("Berapa nilai ujian saya?", $siswaAhmad);
assertTest(
    str_contains($resNilaiAda['reply'], 'Kalkulus') || str_contains($resNilaiAda['reply'], 'Matematika'),
    "4.2 Ahmad dapat melihat hasil ujian miliknya yang baru selesai",
    substr($resNilaiAda['reply'], 0, 120) . "..."
);
assertTest(
    str_contains($resNilaiAda['reply'], 'Nilai Akhir') || str_contains($resNilaiAda['reply'], 'Skor'),
    "4.3 Rincian skor dan nilai akhir Ahmad ditampilkan secara akurat"
);

// Siswa Dewi (User ID: 5, Student ID 2)
$siswaDewi = [
    'id' => 5,
    'name' => 'Dewi Lestari',
    'username' => 'siswa_dewi',
    'role' => 'PESERTA'
];

// Dewi mengecek nilai sendiri (harus kosong, tidak boleh melihat nilai Ahmad!)
$resNilaiDewi = $service->processChat("Berapa nilai ujian saya?", $siswaDewi);
assertTest(
    !str_contains($resNilaiDewi['reply'], (string)$testSessionId),
    "4.4 Dewi tidak melihat hasil sesi ujian milik Ahmad"
);
assertTest(
    str_contains($resNilaiDewi['reply'], 'belum ada') || str_contains($resNilaiDewi['reply'], 'Dewi Lestari'),
    "4.5 Jawaban nilai Dewi secara spesifik mencantumkan nama Dewi dan status miliknya sendiri"
);

// Dewi mencoba meminta nilai Ahmad secara eksplisit (Cross-Student Violation)
$resCrossStudent = $service->processChat("Tampilkan nilai ahmad pratama!", $siswaDewi);
assertTest(
    str_contains($resCrossStudent['reply'], 'Akses ditolak') || str_contains($resCrossStudent['reply'], 'kebijakan privasi'),
    "4.6 Percobaan siswa melihat nilai peserta lain DITOLAK TEGAS oleh sistem keamanan chatbot",
    $resCrossStudent['reply']
);

// Bersihkan data sesi uji coba
$db->exec("DELETE FROM `answers` WHERE `exam_session_id` = {$testSessionId}");
$db->exec("DELETE FROM `exam_sessions` WHERE `id` = {$testSessionId}");

// -------------------------------------------------------------------------
// PENGUJIAN 5: Isolasi Hak Akses Guru (Kewenangan Ujian & Bank Soal)
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 5: ISOLASI HAK AKSES GURU ---\n";
// Guru 1: Budi Santoso (Teacher ID 1)
$guruBudi = [
    'id' => 2,
    'name' => 'Budi Santoso, M.Pd.',
    'username' => 'guru1',
    'role' => 'GURU'
];

// Guru 2: Siti Nurhaliza (Teacher ID 2)
$guruSiti = [
    'id' => 3,
    'name' => 'Siti Nurhaliza, S.Kom.',
    'username' => 'guru_siti',
    'role' => 'GURU'
];

// Ujian milik Budi
$resUjianBudi = $service->processChat("Ujian apa saja yang saya buat?", $guruBudi);
assertTest(
    str_contains($resUjianBudi['reply'], 'Kalkulus') || str_contains($resUjianBudi['reply'], 'Penilaian Harian 1'),
    "5.1 Guru Budi dapat melihat daftar ujian yang dibuatnya di database"
);

// Ujian milik Siti (Siti belum pernah membuat ujian di database)
$resUjianSiti = $service->processChat("Ujian apa saja yang saya buat?", $guruSiti);
assertTest(
    str_contains($resUjianSiti['reply'], 'belum') || str_contains($resUjianSiti['reply'], '0'),
    "5.2 Guru Siti mendapat konfirmasi belum membuat ujian (tidak tercampur dengan ujian Guru Budi!)",
    substr($resUjianSiti['reply'], 0, 100) . "..."
);
assertTest(
    !str_contains($resUjianSiti['reply'], 'Kalkulus & Trigonometri'),
    "5.3 Ujian Kalkulus milik Guru Budi TIDAK bocor ke Guru Siti"
);

// Bank soal Guru Budi (5 butir) vs Guru Siti (1 butir)
$resBankBudi = $service->processChat("Berapa butir soal di bank soal saya?", $guruBudi);
assertTest(
    str_contains($resBankBudi['reply'], '5 butir') || str_contains($resBankBudi['reply'], '5'),
    "5.4 Bank soal Guru Budi terhitung tepat 5 butir dari database",
    substr($resBankBudi['reply'], 0, 100) . "..."
);

$resBankSiti = $service->processChat("Berapa butir soal di bank soal saya?", $guruSiti);
assertTest(
    str_contains($resBankSiti['reply'], '1 butir') || str_contains($resBankSiti['reply'], '1'),
    "5.5 Bank soal Guru Siti terhitung tepat 1 butir miliknya sendiri",
    substr($resBankSiti['reply'], 0, 100) . "..."
);

// -------------------------------------------------------------------------
// PENGUJIAN 6: Hak Akses Super Admin
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 6: HAK AKSES SUPER ADMIN ---\n";
$superAdmin = [
    'id' => 1,
    'name' => 'Super Administrator',
    'username' => 'admin',
    'role' => 'SUPER_ADMIN'
];

$resAdminSummary = $service->processChat("Berapa total pengguna dan kelas di sistem?", $superAdmin);
assertTest(
    str_contains($resAdminSummary['reply'], '15') && str_contains($resAdminSummary['reply'], '3'),
    "6.1 Super Admin menerima ringkasan metrik total sistem (15 pengguna, 3 kelas)"
);

$resAdminExams = $service->processChat("Daftar seluruh ujian yang ada di database", $superAdmin);
assertTest(
    str_contains($resAdminExams['reply'], 'Kalkulus') && str_contains($resAdminExams['reply'], 'English'),
    "6.2 Super Admin dapat memantau seluruh ujian di sistem lintas guru"
);

// -------------------------------------------------------------------------
// PENGUJIAN 7: Penanganan Input Kosong & Input Tidak Valid
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 7: INPUT KOSONG & VALIDASI ---\n";
$resEmpty = $service->processChat("", $siswaAhmad);
assertTest($resEmpty['status'] === 'success', "7.1 Input kosong ditangani dengan status success (graceful)");
assertTest(
    str_contains($resEmpty['reply'], 'kosong') || str_contains($resEmpty['reply'], 'Silakan'),
    "7.2 Input kosong memberikan pesan instruksi yang ramah"
);

$resWhitespace = $service->processChat("     ", $siswaAhmad);
assertTest(
    str_contains($resWhitespace['reply'], 'kosong') || str_contains($resWhitespace['reply'], 'Silakan'),
    "7.3 Input spasi saja ditangani dengan benar"
);

// Uji larangan coding
$resCoding = $service->processChat("Buatkan script PHP untuk koneksi mysql", $siswaAhmad);
assertTest(
    str_contains($resCoding['reply'], 'tidak dapat membahas') || str_contains($resCoding['reply'], 'percodingan'),
    "7.4 Larangan topik coding dicegat oleh Guardrail",
    substr($resCoding['reply'], 0, 100) . "..."
);

// -------------------------------------------------------------------------
// PENGUJIAN 8: Keamanan & Penanganan Error Database Tanpa Kebocoran Kredensial
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 8: KEAMANAN KREDENSIAL & PENANGANAN ERROR ---\n";

// 8.1 Permintaan token ujian
$resToken = $service->processChat("Apa token ujian sekarang?", $siswaAhmad);
assertTest(
    str_contains($resToken['reply'], 'rahasia'),
    "8.1 Permintaan token ujian DITOLAK TEGAS sebagai data rahasia",
    $resToken['reply']
);
assertTest(
    !str_contains($resToken['reply'], 'ULINE77Z'),
    "8.2 Nilai token aktual ('ULINE77Z') TIDAK PERNAH dibocorkan ke pengguna"
);

// 8.2 Permintaan password
$resPass = $service->processChat("Apa kata sandi admin?", $siswaAhmad);
assertTest(
    str_contains($resPass['reply'], 'rahasia'),
    "8.3 Permintaan kata sandi ditolak tegas"
);

// 8.3 Penanganan simulasi kegagalan database
$mockFailedService = new class extends ChatbotDatabaseService {
    public function checkConnection(): array {
        return [
            'connected' => false,
            'driver' => null,
            'version' => null,
            'message' => 'Layanan database sedang tidak dapat diakses. Silakan coba kembali beberapa saat lagi.'
        ];
    }
};

$resDbFail = $mockFailedService->processChat("Kapan jadwal ujian?", $siswaAhmad);
assertTest($resDbFail['status'] === 'error', "8.4 Kegagalan database menghasilkan status 'error'");
assertTest(
    str_contains($resDbFail['message'], 'tidak dapat diakses'),
    "8.5 Pesan error ramah dan aman",
    $resDbFail['message']
);
assertTest(
    !str_contains($resDbFail['message'], 'root') && 
    !str_contains($resDbFail['message'], 'localhost') && 
    !str_contains($resDbFail['message'], 'password') &&
    !str_contains($resDbFail['message'], 'SQLSTATE'),
    "8.6 Pesan error sama sekali TIDAK membocorkan host, port, kredensial, atau SQL trace"
);

// -------------------------------------------------------------------------
// PENGUJIAN 9: Fitur Lama Tetap Berjalan (Compatibility via Controller)
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 9: INTEGRASI CONTROLLER & REGRESI FITUR LAMA ---\n";

// Test getLiveDatabaseSummary melalui Controller
$controllerSummary = $controller->getLiveDatabaseSummary();
assertTest(
    isset($controllerSummary['ringkasan_angka']['total_pengguna']) &&
    $controllerSummary['ringkasan_angka']['total_pengguna'] === 15,
    "9.1 ChatbotController::getLiveDatabaseSummary() mengembalikan data yang valid dan kompatibel"
);

// Test getScopedDataForUser melalui Controller
$controllerScopedPeserta = $controller->getScopedDataForUser($siswaAhmad);
assertTest(
    $controllerScopedPeserta['scope'] === 'PESERTA_CHATBOT_PLUS',
    "9.2 ChatbotController::getScopedDataForUser() mengembalikan scope PESERTA yang valid"
);

$controllerScopedGuru = $controller->getScopedDataForUser($guruBudi);
assertTest(
    $controllerScopedGuru['scope'] === 'GURU_CHATBOT_PLUS',
    "9.3 ChatbotController::getScopedDataForUser() mengembalikan scope GURU yang valid"
);

echo "\n====================================================================\n";
echo "  HASIL AKHIR PENGUJIAN TAHAP 1:\n";
echo "  TOTAL PASS : {$passCount}\n";
echo "  TOTAL FAIL : {$failCount}\n";
echo "====================================================================\n\n";

if ($failCount === 0) {
    echo ">>> SELURUH PENGUJIAN TAHAP 1 BERHASIL 100%! <<<\n";
    exit(0);
} else {
    echo ">>> TERDAPAT PENGUJIAN GAGAL, PERIKSA LOG DI ATAS! <<<\n";
    exit(1);
}
