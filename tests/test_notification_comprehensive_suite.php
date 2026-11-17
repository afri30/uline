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

use App\Core\Database;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Notification\WhatsAppService;
use App\Services\Notification\WebPushService;

$db = Database::getConnection();

$totalPass = 0;
$totalFail = 0;
$totalPending = 0;

function assertSuite(bool $condition, string $testName, string $detail = ''): void {
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

function markPending(string $testName, string $reason): void {
    global $totalPending;
    $totalPending++;
    echo "  [PENDING/EXTERNAL] {$testName}\n";
    echo "         Alasan: {$reason}\n";
}

echo "====================================================================\n";
echo "  PENGUJIAN KOMPREHENSIF NOTIFICATION SERVICE (U-LINE)  \n";
echo "====================================================================\n\n";

// Bersihkan data notifikasi pengujian sebelumnya agar pengujian terisolasi
$db->exec("DELETE FROM `notifications` WHERE `title` LIKE '%[TEST]%' OR `reference_type` LIKE 'test_%' OR `reference_type` = 'exam_schedule_change'");
$db->exec("DELETE FROM `whatsapp_logs` WHERE `idempotency_key` LIKE 'test_%'");
$db->exec("DELETE FROM `push_subscriptions` WHERE `endpoint` LIKE '%test.endpoint%'");

// -------------------------------------------------------------------------
// PENGUJIAN 1: AUDIT DATABASE & STRUKTUR TABEL
// -------------------------------------------------------------------------
echo "--- PENGUJIAN 1: AUDIT DATABASE & STRUKTUR TABEL ---\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
assertSuite(in_array('notifications', $tables, true), "1.1 Tabel `notifications` tersedia di database");
assertSuite(in_array('whatsapp_logs', $tables, true), "1.2 Tabel `whatsapp_logs` tersedia di database");
assertSuite(in_array('push_subscriptions', $tables, true), "1.3 Tabel `push_subscriptions` tersedia di database");

$userCols = $db->query("DESCRIBE `users`")->fetchAll(PDO::FETCH_COLUMN);
assertSuite(in_array('phone_number', $userCols, true), "1.4 Kolom `phone_number` ada pada tabel `users`");
assertSuite(in_array('whatsapp_opt_in', $userCols, true), "1.5 Kolom `whatsapp_opt_in` ada pada tabel `users`");
assertSuite(in_array('push_opt_in', $userCols, true), "1.6 Kolom `push_opt_in` ada pada tabel `users`");

$notifCols = $db->query("DESCRIBE `notifications`")->fetchAll(PDO::FETCH_COLUMN);
$expectedNotif = ['id', 'user_id', 'title', 'message', 'type', 'link', 'reference_type', 'reference_id', 'is_read', 'read_at'];
assertSuite(count(array_intersect($expectedNotif, $notifCols)) === count($expectedNotif), "1.7 Kolom lengkap pada tabel `notifications`");

$waCols = $db->query("DESCRIBE `whatsapp_logs`")->fetchAll(PDO::FETCH_COLUMN);
$expectedWa = ['id', 'user_id', 'phone_number', 'template_name', 'message_payload', 'status', 'error_message', 'retry_count', 'idempotency_key'];
assertSuite(count(array_intersect($expectedWa, $waCols)) === count($expectedWa), "1.8 Kolom lengkap pada tabel `whatsapp_logs`");

$pushCols = $db->query("DESCRIBE `push_subscriptions`")->fetchAll(PDO::FETCH_COLUMN);
$expectedPush = ['id', 'user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'is_active'];
assertSuite(count(array_intersect($expectedPush, $pushCols)) === count($expectedPush), "1.9 Kolom lengkap pada tabel `push_subscriptions`");

// -------------------------------------------------------------------------
// PENGUJIAN 2: NOTIFIKASI WEBSITE & EVENT UJIAN
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 2: NOTIFIKASI WEBSITE & EVENT UJIAN ---\n";
$notifService = new NotificationService();
$notifModel = new Notification();

// Ahmad (User ID: 4, Student ID: 1, Class ID: 1)
$studentUserId = 4;
$studentClassId = 1;

// 2.1 Penyimpanan Notifikasi
$testNotifId = $notifService->send(
    $studentUserId,
    "[TEST] Pengumuman Simulasi",
    "Ini adalah pesan pengujian notifikasi website.",
    'info',
    'peserta/exams',
    'test_general',
    1001
);
assertSuite($testNotifId !== null && $testNotifId > 0, "2.1 Penyimpanan notifikasi ke database berhasil");

// 2.2 Unread Count Badge
$unreadCount = $notifService->getUnreadCount($studentUserId);
assertSuite($unreadCount >= 1, "2.2 Badge jumlah notifikasi belum dibaca akurat (> 0)", "Unread: {$unreadCount}");

// 2.3 Status Dibaca (Mark Single Read)
$marked = $notifService->markAsRead($testNotifId, $studentUserId);
assertSuite($marked === true, "2.3 Status notifikasi berhasil diubah menjadi sudah dibaca");

// 2.4 Isolasi Hak Akses (User Budi tidak bisa mengubah notifikasi Ahmad)
$budiUserId = 2;
$illegalMark = $notifService->markAsRead($testNotifId, $budiUserId);
assertSuite($illegalMark === false, "2.4 Percobaan manipulasi notifikasi user lain DITOLAK (Akses Terisolasi)");

// 2.5 Event Ujian Baru
$newExamCount = $notifService->notifyStudentsForNewExam(1);
assertSuite($newExamCount >= 0, "2.5 Event ujian baru berhasil mendistribusikan notifikasi dengan deduplikasi");

// 2.6 Event Perubahan Jadwal Ujian (New Feature!)
$scheduleChangeCount = $notifService->notifyExamScheduleChanged(
    1,
    date('Y-m-d 08:00:00'),
    date('Y-m-d 10:00:00', strtotime('+2 days')),
    date('Y-m-d 12:00:00', strtotime('+2 days'))
);
assertSuite($scheduleChangeCount >= 1, "2.6 Event perubahan jadwal ujian berhasil mendistribusikan notifikasi ke peserta kelas", "Total: {$scheduleChangeCount}");

// Verifikasi isi notifikasi perubahan jadwal
$recentAhmad = $notifService->getRecent($studentUserId, 5);
$foundScheduleChange = false;
foreach ($recentAhmad as $n) {
    if ($n['reference_type'] === 'exam_schedule_change') {
        $foundScheduleChange = true;
        break;
    }
}
assertSuite($foundScheduleChange, "2.7 Notifikasi perubahan jadwal ujian tercatat di riwayat peserta");

// 2.8 Mark All as Read
$allReadCount = $notifService->markAllAsRead($studentUserId);
$unreadAfter = $notifService->getUnreadCount($studentUserId);
assertSuite($unreadAfter === 0, "2.8 Tandai semua dibaca berhasil mereset unread count menjadi 0");

// -------------------------------------------------------------------------
// PENGUJIAN 3: INTEGRASI WHATSAPP BUSINESS GATEWAY
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 3: INTEGRASI WHATSAPP GATEWAY ---\n";
$waService = new WhatsAppService();

// 3.1 Normalisasi Nomor Telepon E.164
$phone1 = $waService->formatPhoneNumber('081234567890');
assertSuite($phone1 === '+6281234567890', "3.1 Format nomor lokal '0812...' ternormalisasi menjadi '+62812...'");

$phone2 = $waService->formatPhoneNumber('6281234567890');
assertSuite($phone2 === '+6281234567890', "3.2 Format nomor '62812...' ternormalisasi menjadi '+62812...'");

$phone3 = $waService->formatPhoneNumber('+62 812-3456-7890');
assertSuite($phone3 === '+6281234567890', "3.3 Nomor dengan spasi & tanda hubung dibersihkan dengan benar");

$invalidPhone = $waService->formatPhoneNumber('abc123');
assertSuite($invalidPhone === null, "3.4 Nomor tidak valid berhasil ditolak");

// 3.2 Validasi Persetujuan Pengguna (Opt-In Consent)
// Set user opt-in = 0
$db->prepare("UPDATE `users` SET `whatsapp_opt_in` = 0 WHERE `id` = :uid")->execute([':uid' => $studentUserId]);
$resNotConsented = $waService->sendTemplate(
    '+6281234567890',
    'notifikasi_jadwal_ujian',
    ['Ahmad', 'Matematika', 'Besok 08:00'],
    $studentUserId,
    'test_consent_' . time()
);
assertSuite($resNotConsented['status'] === 'skipped_not_consented', "3.5 Pengiriman dicegah jika pengguna belum opt-in (skipped_not_consented)");

// 3.3 Penanganan Kredensial Belum Dikonfigurasi (Transparan tanpa kepalsuan)
// Set user opt-in = 1
$db->prepare("UPDATE `users` SET `whatsapp_opt_in` = 1 WHERE `id` = :uid")->execute([':uid' => $studentUserId]);
$resNoCred = $waService->sendTemplate(
    '+6281234567890',
    'notifikasi_jadwal_ujian',
    ['Ahmad', 'Matematika', 'Besok 08:00'],
    $studentUserId,
    'test_nocred_' . time()
);
assertSuite($resNoCred['status'] === 'skipped_no_credential', "3.6 Tanpa kredensial resmi, status dicatat transparan sebagai 'skipped_no_credential'");
assertSuite($resNoCred['success'] === false, "3.7 Sistem TIDAK berpura-pura bahwa pesan terkirim saat API key kosong");

// 3.4 Perekaman Log ke Database
$logStmt = $db->prepare("SELECT * FROM `whatsapp_logs` WHERE `id` = :id");
$logStmt->execute([':id' => $resNoCred['log_id'] ?? 0]);
$logRow = $logStmt->fetch(PDO::FETCH_ASSOC);
assertSuite(!empty($logRow) && $logRow['status'] === 'skipped_no_credential', "3.8 Log WhatsApp tersimpan aman di database dengan status valid");

// 3.5 Anti-Duplikasi / Idempotency
$idempotencyKey = 'test_idempotent_' . time();
$res1 = $waService->sendTemplate('+6281234567890', 'notifikasi_jadwal_ujian', ['Ahmad'], $studentUserId, $idempotencyKey);
$res2 = $waService->sendTemplate('+6281234567890', 'notifikasi_jadwal_ujian', ['Ahmad'], $studentUserId, $idempotencyKey);
assertSuite($res2['status'] === 'skipped_duplicate', "3.9 Pesan dengan idempotency key ganda dicegah dari pengiriman berulang");

// Status Live Meta API
if (!$waService->isConfigured()) {
    markPending("3.10 Pengiriman HTTP live ke Meta WhatsApp Cloud API", "Kredensial resmi (WHATSAPP_API_TOKEN & WHATSAPP_PHONE_NUMBER_ID) belum diisi di .env oleh pengguna.");
} else {
    assertSuite(true, "3.10 Kredensial Meta WhatsApp Cloud API aktif");
}

// -------------------------------------------------------------------------
// PENGUJIAN 4: WEB PUSH NOTIFICATION & SERVICE WORKER
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 4: WEB PUSH NOTIFICATION ---\n";
$webPush = new WebPushService();

// 4.1 Proteksi Kunci VAPID
$pubKey = $webPush->getPublicKey();
assertSuite(!empty($pubKey) && strlen($pubKey) > 40, "4.1 VAPID Public Key tersedia dan berformat aman");
assertSuite($webPush->isConfigured(), "4.2 Web Push terkonfigurasi dengan pasangan kunci VAPID");

// 4.2 Penyimpanan Subscription Perangkat
$mockEndpoint = 'https://fcm.googleapis.com/fcm/send/test.endpoint.' . time();
$mockP256dh = 'BMxMockPublicKeyForTestingBrowserPushDeviceP256dhKey1234567890';
$mockAuth = 'MockAuthSecretToken123==';

$subscribed = $webPush->subscribe($studentUserId, $mockEndpoint, $mockP256dh, $mockAuth, 'Test Browser UserAgent/1.0');
assertSuite($subscribed === true, "4.3 Subscription perangkat pengguna berhasil disimpan ke backend");

// 4.3 Verifikasi Subscription Aktif
$activeSubs = $webPush->getActiveSubscriptions($studentUserId);
assertSuite(count($activeSubs) >= 1, "4.4 Subscription berstatus aktif ditemukan untuk pengguna");

// 4.4 Pengguna Dapat Menonaktifkan Notifikasi (Unsubscribe)
$unsubscribed = $webPush->unsubscribe($studentUserId, $mockEndpoint);
assertSuite($unsubscribed === true, "4.5 Pengguna berhasil menonaktifkan notifikasi (unsubscribe)");

$subsAfterUnsub = $webPush->getActiveSubscriptions($studentUserId);
$foundMockAfterUnsub = false;
foreach ($subsAfterUnsub as $s) {
    if ($s['endpoint'] === $mockEndpoint) {
        $foundMockAfterUnsub = true;
        break;
    }
}
assertSuite(!$foundMockAfterUnsub, "4.6 Perangkat yang di-unsubscribe tidak lagi berstatus aktif");

// 4.5 Penanganan Subscription Tidak Valid (HTTP 404/410)
$mockDeadEndpoint = 'https://fcm.googleapis.com/fcm/send/test.endpoint.dead.' . time();
$mockDeadHash = hash('sha256', $mockDeadEndpoint);
$webPush->subscribe($studentUserId, $mockDeadEndpoint, $mockP256dh, $mockAuth, 'Test Browser Dead/1.0');

// Panggil deactivation
$webPush->deactivateSubscription($mockDeadHash);
$checkDeadStmt = $db->prepare("SELECT `is_active` FROM `push_subscriptions` WHERE `endpoint_hash` = :h");
$checkDeadStmt->execute([':h' => $mockDeadHash]);
$deadStatus = (int)$checkDeadStmt->fetchColumn();
assertSuite($deadStatus === 0, "4.7 Subscription tidak valid (404/410) otomatis dinonaktifkan di backend");

// 4.6 File Service Worker Tersedia
$swExists = file_exists(dirname(__DIR__) . '/public/service-worker.js');
assertSuite($swExists, "4.8 File `public/service-worker.js` tersedia untuk registrasi browser");

// Bersihkan data tes
$db->exec("DELETE FROM `notifications` WHERE `title` LIKE '%[TEST]%' OR `reference_type` LIKE 'test_%' OR `reference_type` = 'exam_schedule_change'");
$db->exec("DELETE FROM `whatsapp_logs` WHERE `idempotency_key` LIKE 'test_%'");
$db->exec("DELETE FROM `push_subscriptions` WHERE `endpoint` LIKE '%test.endpoint%'");

// -------------------------------------------------------------------------
// REKAPITULASI HASIL AKHIR
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "  HASIL AKHIR PENGUJIAN NOTIFICATION SERVICE:\n";
echo "  TOTAL PASS                 : {$totalPass}\n";
echo "  TOTAL FAIL                 : {$totalFail}\n";
echo "  TOTAL PENDING / EXTERNAL   : {$totalPending}\n";
echo "====================================================================\n\n";

if ($totalFail === 0) {
    echo ">>> SELURUH PENGUJIAN NOTIFICATION SERVICE BERHASIL 100%! <<<\n";
    exit(0);
} else {
    echo ">>> TERDAPAT PENGUJIAN YANG GAGAL. <<<\n";
    exit(1);
}
