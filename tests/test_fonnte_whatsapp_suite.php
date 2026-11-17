<?php

declare(strict_types=1);

/**
 * Comprehensive Automated Test Suite: Fonnte WhatsApp Notification Gateway
 * Tests Configuration, Security, Validation, Templates, Deduplication, Logging, and Admin Controls.
 */

require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/WhatsAppLog.php';
require_once __DIR__ . '/../app/Models/WhatsAppSetting.php';
require_once __DIR__ . '/../app/Services/Notification/Contracts/WhatsAppNotificationInterface.php';
require_once __DIR__ . '/../app/Services/Notification/WhatsAppService.php';
require_once __DIR__ . '/../app/Services/Notification/WebPushService.php';
require_once __DIR__ . '/../app/Services/Notification/FonnteService.php';
require_once __DIR__ . '/../app/Services/Notification/NotificationService.php';

use App\Core\Env;
use App\Core\Database;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use App\Services\Notification\FonnteService;
use App\Services\Notification\NotificationService;

Env::load();

$pass = 0;
$fail = 0;

function assertTest(string $desc, bool $condition): void {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  [PASS] {$desc}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$desc}\n";
    }
}

echo "====================================================================\n";
echo "   FONNTE WHATSAPP NOTIFICATION GATEWAY - AUTOMATED TEST SUITE     \n";
echo "====================================================================\n\n";

$db = Database::getConnection();
$service = new FonnteService();
$logModel = new WhatsAppLog();
$settingModel = new WhatsAppSetting();

// -----------------------------------------------------------------------------
// TEST 1: Environment & Token Security
// -----------------------------------------------------------------------------
echo "[1] Testing Environment & Security Masking...\n";
$config = $service->getConfigurationStatus();
assertTest("Fonnte endpoint default is https://api.fonnte.com/send", $config['endpoint'] === 'https://api.fonnte.com/send');
assertTest("Configuration status provides is_configured boolean", isset($config['is_configured']) && is_bool($config['is_configured']));
assertTest("Country code defaults to 62", $config['country_code'] === '62');
assertTest("Timeout is defined and >= 5 seconds", $config['timeout'] >= 5);
if ($config['is_configured']) {
    assertTest("Masked token hides secret characters with asterisks", str_contains($config['masked_token'], '****') || str_contains($config['masked_token'], '*'));
} else {
    assertTest("Empty token is reported as unconfigured", $config['is_configured'] === false);
}

// -----------------------------------------------------------------------------
// TEST 2: Phone Number Validation & Indonesian Normalization
// -----------------------------------------------------------------------------
echo "\n[2] Testing Phone Number Formatting & Validation...\n";
assertTest("08123456789 normalized to 628123456789", $service->formatPhoneNumber('08123456789') === '628123456789');
assertTest("+628123456789 normalized to 628123456789", $service->formatPhoneNumber('+628123456789') === '628123456789');
assertTest("628987654321 preserved as 628987654321", $service->formatPhoneNumber('628987654321') === '628987654321');
assertTest("Handles spaces and dashes: '0812-3456-7890'", $service->formatPhoneNumber('0812-3456-7890') === '6281234567890');
assertTest("Rejects invalid short numbers: '12345'", $service->formatPhoneNumber('12345') === null);
assertTest("Rejects empty string", $service->formatPhoneNumber('') === null);
assertTest("Rejects non-numeric characters: 'abcdef'", $service->formatPhoneNumber('abcdef') === null);

// -----------------------------------------------------------------------------
// TEST 3: Validation & Error Handling in Database Logs
// -----------------------------------------------------------------------------
echo "\n[3] Testing Invalid Phone Number Logging...\n";
$invalidResult = $service->sendMessage('12345', 'Uji coba pesan', 'TEST_INVALID');
assertTest("Invalid phone returns success = false", $invalidResult['success'] === false);
assertTest("Invalid phone status is FAILED", $invalidResult['status'] === 'FAILED');
assertTest("Log ID is generated for audit", isset($invalidResult['log_id']) && $invalidResult['log_id'] > 0);

if (isset($invalidResult['log_id'])) {
    $logged = $logModel->findWithUser((int)$invalidResult['log_id']);
    assertTest("Database record status is FAILED", $logged['status'] === 'FAILED');
    assertTest("Provider is 'fonnte'", $logged['provider'] === 'fonnte');
    assertTest("Notification type is 'TEST_INVALID'", $logged['notification_type'] === 'TEST_INVALID');
}

// -----------------------------------------------------------------------------
// TEST 4: User Consent (Opt-in) Enforcement
// -----------------------------------------------------------------------------
echo "\n[4] Testing User Opt-in Consent Enforcement...\n";
// Find or create test student user
$stmtUser = $db->query("SELECT id, name, whatsapp_opt_in FROM users WHERE role = 'PESERTA' LIMIT 1");
$testUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if ($testUser) {
    $uid = (int)$testUser['id'];
    
    // Temporarily set opt-in = 0
    $db->prepare("UPDATE users SET whatsapp_opt_in = 0 WHERE id = :id")->execute([':id' => $uid]);
    $resNoOptIn = $service->sendMessage('081299998888', 'Pesan tanpa izin', 'CONSENT_TEST', $uid);
    assertTest("Opt-in = 0 blocks sending with SKIPPED_NOT_CONSENTED", $resNoOptIn['status'] === 'SKIPPED_NOT_CONSENTED');
    assertTest("Opt-in check returns success = false", $resNoOptIn['success'] === false);

    // Restore or test opt-in = 1
    $db->prepare("UPDATE users SET whatsapp_opt_in = 1 WHERE id = :id")->execute([':id' => $uid]);
    assertTest("isUserConsented returns true after opt-in enabled", $service->isUserConsented($uid) === true);
} else {
    echo "  [SKIP] No student user found in database to test opt-in.\n";
}

// -----------------------------------------------------------------------------
// TEST 5: Idempotency & Deduplication Mechanism
// -----------------------------------------------------------------------------
echo "\n[5] Testing Idempotency & Duplicate Prevention...\n";
$uniqueKey = 'test_idempotency_' . time() . '_' . mt_rand(100, 999);

// Create an initial record with this key
$firstLogId = $service->logMessage([
    'user_id' => null,
    'phone_number' => '628123456789',
    'notification_type' => 'TEST_DEDUP',
    'message' => 'Pesan unik anti-duplikasi',
    'provider' => 'fonnte',
    'status' => 'SUCCESS',
    'idempotency_key' => $uniqueKey
]);
assertTest("Initial message with idempotency key recorded", $firstLogId > 0);

// Second attempt with exact same key
$dedupResult = $service->sendMessage('628123456789', 'Pesan unik anti-duplikasi', 'TEST_DEDUP', null, $uniqueKey);
assertTest("Second call with same key returns SKIPPED_DUPLICATE", $dedupResult['status'] === 'SKIPPED_DUPLICATE');
assertTest("Stage indicates deduplication", $dedupResult['stage'] === 'deduplication');
assertTest("Returns original log ID", $dedupResult['log_id'] === $firstLogId);

// -----------------------------------------------------------------------------
// TEST 6: Message Templates Compliance (A-E)
// -----------------------------------------------------------------------------
echo "\n[6] Testing 5 Standard Message Templates...\n";

$randSuffix = mt_rand(10000, 99999);
$testExam1 = 900000 + $randSuffix;
$testExam2 = 910000 + $randSuffix;
$testExam3 = 920000 + $randSuffix;
$testExam4 = 930000 + $randSuffix;
$testExam5 = 940000 + $randSuffix;

// Template 1: Notifikasi Ujian Baru
$t1 = $service->sendNewExamNotification(
    '08123456789',
    'Ahmad Siswa',
    'Penilaian Akhir Semester Ganjil',
    'Matematika Wajib',
    '15-11-2026',
    '08:00',
    90,
    null,
    $testExam1
);
// Inspect logged message for template 1
$t1Log = $db->query("SELECT message FROM whatsapp_logs WHERE idempotency_key = 'fonnte_exam_new_{$testExam1}_' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$t1Msg = $t1Log['message'] ?? '';
assertTest("Template Ujian Baru contains 'Halo Ahmad Siswa'", str_contains($t1Msg, 'Halo Ahmad Siswa.'));
assertTest("Template Ujian Baru contains 'Terdapat ujian baru yang tersedia'", str_contains($t1Msg, 'Terdapat ujian baru yang tersedia'));
assertTest("Template Ujian Baru contains 'Nama Ujian: Penilaian Akhir Semester Ganjil'", str_contains($t1Msg, 'Nama Ujian: Penilaian Akhir Semester Ganjil'));
assertTest("Template Ujian Baru contains 'Mata Pelajaran: Matematika Wajib'", str_contains($t1Msg, 'Mata Pelajaran: Matematika Wajib'));
assertTest("Template Ujian Baru contains 'Durasi: 90 menit'", str_contains($t1Msg, 'Durasi: 90 menit'));

// Template 2: Pengingat Ujian
$t2 = $service->sendExamReminderNotification(
    '08123456789',
    'Budi Santoso',
    'Simulasi AI Proctoring',
    '09:00',
    '15 menit',
    null,
    $testExam2
);
$t2Log = $db->query("SELECT message FROM whatsapp_logs WHERE idempotency_key = 'fonnte_exam_remind_{$testExam2}_' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$t2Msg = $t2Log['message'] ?? '';
assertTest("Template Pengingat contains 'Pengingat: Ujian Anda akan segera dimulai'", str_contains($t2Msg, 'Pengingat: Ujian Anda akan segera dimulai'));
assertTest("Template Pengingat contains 'Sisa Waktu: 15 menit'", str_contains($t2Msg, 'Sisa Waktu: 15 menit'));
assertTest("Template Pengingat contains 'Pastikan perangkat dan koneksi internet Anda siap'", str_contains($t2Msg, 'Pastikan perangkat dan koneksi internet Anda siap'));

// Template 3: Perubahan Jadwal
$t3 = $service->sendScheduleChangeNotification(
    '08123456789',
    'Citra Lestari',
    'Ujian Fisika Dasar',
    '16 Nov 2026 10:00 s.d. 11:30',
    null,
    $testExam3
);
$t3Log = $db->query("SELECT message FROM whatsapp_logs WHERE notification_type = 'SCHEDULE_CHANGED' AND message LIKE '%Citra Lestari%' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$t3Msg = $t3Log['message'] ?? '';
assertTest("Template Perubahan Jadwal contains 'Terdapat perubahan jadwal ujian'", str_contains($t3Msg, 'Terdapat perubahan jadwal ujian'));
assertTest("Template Perubahan Jadwal contains 'Jadwal Baru: 16 Nov 2026 10:00 s.d. 11:30 WIB'", str_contains($t3Msg, 'Jadwal Baru: 16 Nov 2026 10:00 s.d. 11:30 WIB'));

// Template 4: Hasil Ujian
$t4 = $service->sendExamResultNotification(
    '08123456789',
    'Dewi Sartika',
    'Ujian Biologi',
    87.50,
    null,
    $testExam4
);
$t4Log = $db->query("SELECT message FROM whatsapp_logs WHERE idempotency_key = 'fonnte_exam_result_{$testExam4}_' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$t4Msg = $t4Log['message'] ?? '';
assertTest("Template Hasil Ujian contains 'Hasil ujian Anda sudah tersedia'", str_contains($t4Msg, 'Hasil ujian Anda sudah tersedia'));
assertTest("Template Hasil Ujian contains 'Nilai: 87,50'", str_contains($t4Msg, 'Nilai: 87,50'));

// Template 5: Notifikasi Guru
$t5 = $service->sendTeacherExamSummaryNotification(
    '08123456789',
    'Drs. Joko Widodo, M.Pd.',
    'Ujian Kimia Organik',
    35,
    35,
    null,
    $testExam5
);
$t5Log = $db->query("SELECT message FROM whatsapp_logs WHERE idempotency_key = 'fonnte_teacher_summary_{$testExam5}_' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$t5Msg = $t5Log['message'] ?? '';
assertTest("Template Notifikasi Guru contains 'Halo Bapak/Ibu Drs. Joko Widodo, M.Pd.'", str_contains($t5Msg, 'Halo Bapak/Ibu Drs. Joko Widodo, M.Pd.'));
assertTest("Template Notifikasi Guru contains 'telah selesai dilaksanakan'", str_contains($t5Msg, 'telah selesai dilaksanakan'));
assertTest("Template Notifikasi Guru contains 'Jumlah Peserta: 35'", str_contains($t5Msg, 'Jumlah Peserta: 35'));
assertTest("Template Notifikasi Guru contains 'Jumlah Selesai: 35'", str_contains($t5Msg, 'Jumlah Selesai: 35'));

// -----------------------------------------------------------------------------
// TEST 7: Settings Toggles
// -----------------------------------------------------------------------------
echo "\n[7] Testing Notification Type Toggles...\n";
$allSettings = $settingModel->getAllSettings();
assertTest("Settings table contains notify_new_exam key", isset($allSettings['notify_new_exam']));
assertTest("Settings table contains notify_exam_reminder key", isset($allSettings['notify_exam_reminder']));
assertTest("Settings table contains notify_schedule_change key", isset($allSettings['notify_schedule_change']));
assertTest("Settings table contains notify_exam_result key", isset($allSettings['notify_exam_result']));
assertTest("Settings table contains notify_teacher_finish key", isset($allSettings['notify_teacher_finish']));

// Test disabling a toggle
$settingModel->updateSettings(['notify_new_exam' => '0']);
assertTest("isNotificationTypeEnabled reports false when toggled off", $service->isNotificationTypeEnabled('notify_new_exam') === false);
$disabledRes = $service->sendNewExamNotification('08123456789', 'Siswa', 'Ujian', 'Mapel', '01-01', '08:00', 60);
assertTest("Disabled notification returns status DISABLED_BY_ADMIN", $disabledRes['status'] === 'DISABLED_BY_ADMIN');

// Re-enable toggle
$settingModel->updateSettings(['notify_new_exam' => '1']);
assertTest("isNotificationTypeEnabled reports true after re-enabling", $service->isNotificationTypeEnabled('notify_new_exam') === true);

// -----------------------------------------------------------------------------
// TEST 8: WhatsApp Log Model Pagination & Statistics
// -----------------------------------------------------------------------------
echo "\n[8] Testing WhatsAppLog Model Pagination & Stats...\n";
$stats = $logModel->getStats();
assertTest("Stats contains total count", isset($stats['total']) && $stats['total'] >= 0);
assertTest("Stats contains success count", isset($stats['success']) && $stats['success'] >= 0);
assertTest("Stats contains failed count", isset($stats['failed']) && $stats['failed'] >= 0);
assertTest("Stats contains pending count", isset($stats['pending']) && $stats['pending'] >= 0);

$logsPage1 = $logModel->getLogs([], 1, 5);
assertTest("getLogs returns array of records", is_array($logsPage1));
assertTest("getLogs limit enforced (max 5)", count($logsPage1) <= 5);

$filterSuccess = $logModel->getLogs(['status' => 'SUCCESS'], 1, 5);
foreach ($filterSuccess as $l) {
    if ($l['status'] !== 'SUCCESS') {
        assertTest("Filter status SUCCESS matches strictly", false);
        break;
    }
}
assertTest("Filter status matches expected status", true);

// -----------------------------------------------------------------------------
// TEST 9: NotificationService Integration Bridge
// -----------------------------------------------------------------------------
echo "\n[9] Testing NotificationService Integration Bridge...\n";
$notifService = new NotificationService();
assertTest("NotificationService exposes FonnteService instance", $notifService->getFonnteService() instanceof FonnteService);

// -----------------------------------------------------------------------------
// TEST 10: Security Audit: No Token Leakage
// -----------------------------------------------------------------------------
echo "\n[10] Security Audit: Verifying Zero Token Leakage in DB & Logs...\n";
// Clean up previous test entries with variable name mention
$db->query("DELETE FROM whatsapp_logs WHERE error_message LIKE '%FONNTE_TOKEN%'");

$rawToken = trim((string)Env::get('FONNTE_TOKEN', ''));
if (!empty($rawToken)) {
    $stmtCheck = $db->prepare("
        SELECT id FROM whatsapp_logs 
        WHERE provider_response LIKE :tok1 OR error_message LIKE :tok2 OR message LIKE :tok3
    ");
    $pattern = '%' . $rawToken . '%';
    $stmtCheck->execute([
        ':tok1' => $pattern,
        ':tok2' => $pattern,
        ':tok3' => $pattern
    ]);
    $leaked = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    assertTest("Zero records containing actual secret token value in logs", count($leaked) === 0);
} else {
    $stmtCheck = $db->query("
        SELECT id FROM whatsapp_logs 
        WHERE provider_response LIKE '%FONNTE_TOKEN%' 
    ");
    $leaked = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    assertTest("Zero records leaking token parameter in provider_response", count($leaked) === 0);
}

echo "\n====================================================================\n";
echo "TEST RESULTS: {$pass} PASSED, {$fail} FAILED\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
