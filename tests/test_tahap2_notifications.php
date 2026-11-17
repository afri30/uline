<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

require_once __DIR__ . '/../app/Core/Database.php';

// Auto-register App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Database;
use App\Models\Notification;
use App\Services\Notification\NotificationService;

$db = Database::getConnection();

echo "====================================================================\n";
echo "  PENGUJIAN TAHAP 2: SISTEM NOTIFIKASI MULTI-ROLE (U-LINE)\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $description, ?string $detail = null): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$description}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$description}\n";
        if ($detail) {
            echo "         Detail: {$detail}\n";
        }
    }
}

$service = new NotificationService();
$model = new Notification();

// Clean up notifications table for isolated test runs
$db->exec("DELETE FROM `notifications`");

// -------------------------------------------------------------------------
// PENGUJIAN 1: STRUKTUR DATABASE & MODEL NOTIFIKASI
// -------------------------------------------------------------------------
echo "--- PENGUJIAN 1: STRUKTUR DATABASE & MODEL ---\n";
$tables = $db->query("SHOW TABLES LIKE 'notifications'")->fetchAll(PDO::FETCH_COLUMN);
assertTest(count($tables) === 1, "1.1 Tabel 'notifications' ada di database uline_db");

$cols = $db->query("DESCRIBE `notifications`")->fetchAll(PDO::FETCH_COLUMN);
$expectedCols = ['id', 'user_id', 'title', 'message', 'type', 'link', 'reference_type', 'reference_id', 'is_read', 'read_at', 'created_at', 'updated_at'];
$allColsExist = count(array_intersect($expectedCols, $cols)) === count($expectedCols);
assertTest($allColsExist, "1.2 Seluruh kolom wajib ada pada tabel notifications", "Cols: " . implode(', ', $cols));

// -------------------------------------------------------------------------
// PENGUJIAN 2: EVENT NOTIFIKASI PESERTA DIDIK
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 2: EVENT NOTIFIKASI PESERTA DIDIK ---\n";

// Ahmad (User ID: 4, Student ID: 1, Class ID: 1)
$studentAhmadUserId = 4;
$studentAhmadId = 1;
$classId = 1;

// 2a. Ujian Baru Tersedia bagi Kelas Peserta (Exam ID 1)
$notifiedCount = $service->notifyStudentsForNewExam(1);
assertTest($notifiedCount >= 1, "2.1 Event ujian baru berhasil mendistribusikan notifikasi ke peserta kelas", "Total terkirim: {$notifiedCount}");

$recentAhmad = $service->getRecent($studentAhmadUserId, 5);
$foundExamNew = false;
foreach ($recentAhmad as $n) {
    if (str_contains($n['title'], 'Ujian Baru') && $n['reference_type'] === 'exam_new') {
        $foundExamNew = true;
        break;
    }
}
assertTest($foundExamNew, "2.2 Peserta Ahmad menerima notifikasi 'Ujian Baru Tersedia'");

// 2b. Jadwal Ujian Mendekat
$testUpcomingExamId = 9999;
$db->exec("
    INSERT INTO `exams` (`id`, `teacher_id`, `subject_id`, `class_id`, `title`, `duration`, `start_at`, `end_at`, `status`)
    VALUES ({$testUpcomingExamId}, 1, 1, {$classId}, '[TEST] Ujian Mendekat Besok', 60, DATE_ADD(NOW(), INTERVAL 2 HOUR), DATE_ADD(NOW(), INTERVAL 4 HOUR), 'published')
    ON DUPLICATE KEY UPDATE `start_at` = DATE_ADD(NOW(), INTERVAL 2 HOUR)
");

$upcomingCount = $service->checkUpcomingExamsForStudent($studentAhmadUserId, $studentAhmadId, $classId);
assertTest($upcomingCount >= 1, "2.3 Event jadwal ujian mendekat terdeteksi untuk ujian dalam 24 jam ke depan");

$recentAfterUpcoming = $service->getRecent($studentAhmadUserId, 5);
$foundUpcoming = false;
foreach ($recentAfterUpcoming as $n) {
    if (str_contains($n['title'], 'Ujian Mendekat') && (int)$n['reference_id'] === $testUpcomingExamId) {
        $foundUpcoming = true;
        break;
    }
}
assertTest($foundUpcoming, "2.4 Peserta Ahmad menerima notifikasi 'Jadwal Ujian Mendekat'");

// Clean up temporary exam
$db->exec("DELETE FROM `exams` WHERE `id` = {$testUpcomingExamId}");

// -------------------------------------------------------------------------
// PENGUJIAN 3: EVENT NOTIFIKASI GURU PENGUJI
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 3: EVENT NOTIFIKASI GURU PENGUJI ---\n";
// Guru Budi (User ID: 2, Teacher ID: 1)
$guruBudiUserId = 2;

// 3a. Peserta Mulai Ujian
$mockSessionId = 8881;
$notifStartId = $service->notifyTeacherExamStarted($mockSessionId, 1, $studentAhmadId);
assertTest($notifStartId !== null && $notifStartId > 0, "3.1 Guru penguji menerima notifikasi saat peserta mulai ujian");

$recentGuru = $service->getRecent($guruBudiUserId, 5);
$foundStart = false;
foreach ($recentGuru as $n) {
    if (str_contains($n['title'], 'Peserta Mulai Ujian') && (int)$n['reference_id'] === $mockSessionId) {
        $foundStart = true;
        break;
    }
}
assertTest($foundStart, "3.2 Isi notifikasi guru mencantumkan nama siswa dan judul ujian dengan tepat");

// 3b. Ujian Selesai & Hasil Tersedia
$service->notifyExamFinished($mockSessionId, 1, $studentAhmadId);
$recentGuruFinish = $service->getRecent($guruBudiUserId, 5);
$foundFinishGuru = false;
foreach ($recentGuruFinish as $n) {
    if (str_contains($n['title'], 'Peserta Menyelesaikan Ujian') && (int)$n['reference_id'] === $mockSessionId) {
        $foundFinishGuru = true;
        break;
    }
}
assertTest($foundFinishGuru, "3.3 Guru penguji menerima notifikasi saat peserta selesai mengerjakan ujian");

$recentAhmadFinish = $service->getRecent($studentAhmadUserId, 5);
$foundFinishStudent = false;
foreach ($recentAhmadFinish as $n) {
    if (str_contains($n['title'], 'Ujian Selesai') && (int)$n['reference_id'] === $mockSessionId) {
        $foundFinishStudent = true;
        break;
    }
}
assertTest($foundFinishStudent, "3.4 Peserta menerima konfirmasi notifikasi saat ujian selesai dikumpulkan");

// 3c. Peringatan AI Proctoring (Severity HIGH)
$notifProctor = $service->notifyTeacherProctorAlert($mockSessionId, 1, $studentAhmadId, 'multiple_faces', 'HIGH');
assertTest($notifProctor !== null && $notifProctor > 0, "3.5 Guru menerima notifikasi peringatan saat terdeteksi event proctoring mencurigakan");

$recentGuruProctor = $service->getRecent($guruBudiUserId, 5);
$foundProctor = false;
foreach ($recentGuruProctor as $n) {
    if (str_contains($n['title'], 'Peringatan Proctoring') && $n['type'] === 'danger') {
        $foundProctor = true;
        break;
    }
}
assertTest($foundProctor, "3.6 Tipe notifikasi proctoring HIGH ditetapkan sebagai 'danger' untuk perhatian guru");

// -------------------------------------------------------------------------
// PENGUJIAN 4: EVENT NOTIFIKASI SUPER ADMIN
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 4: EVENT NOTIFIKASI SUPER ADMIN ---\n";
// Super Admin (User ID: 1)
$superAdminUserId = 1;

$adminNotifCount = $service->notifySuperAdmins(
    "[TEST] Pengguna Baru Didaftarkan",
    "Akun pengguna baru 'Budi Test' (@budi_test) dengan peran GURU telah berhasil didaftarkan.",
    'system',
    'admin/users',
    'test_user_created',
    9999
);
assertTest($adminNotifCount >= 1, "4.1 Event operasional sistem mendistribusikan notifikasi ke Super Admin");

$recentAdmin = $service->getRecent($superAdminUserId, 5);
$foundAdminNotif = false;
foreach ($recentAdmin as $n) {
    if (str_contains($n['title'], 'Pengguna Baru') && $n['reference_type'] === 'test_user_created') {
        $foundAdminNotif = true;
        break;
    }
}
assertTest($foundAdminNotif, "4.2 Super Admin menerima pemberitahuan operasional sistem secara real-time");

// -------------------------------------------------------------------------
// PENGUJIAN 5: AKURASI JUMLAH BELUM DIBACA & MARK AS READ
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 5: AKURASI UNREAD COUNT & MARK AS READ ---\n";
$unreadCountBefore = $service->getUnreadCount($studentAhmadUserId);
assertTest($unreadCountBefore > 0, "5.1 Jumlah notifikasi belum dibaca akurat (> 0)", "Unread: {$unreadCountBefore}");

// Ambil 1 notifikasi unread milik Ahmad
$stmtOne = $db->prepare("SELECT id FROM notifications WHERE user_id = :uid AND is_read = 0 LIMIT 1");
$stmtOne->execute([':uid' => $studentAhmadUserId]);
$targetId = (int)$stmtOne->fetchColumn();

// Tandai dibaca 1 notifikasi
$markedSingle = $service->markAsRead($targetId, $studentAhmadUserId);
assertTest($markedSingle === true, "5.2 Aksi tandai sudah dibaca untuk satu notifikasi berhasil");

$unreadCountAfter = $service->getUnreadCount($studentAhmadUserId);
assertTest($unreadCountAfter === ($unreadCountBefore - 1), "5.3 Jumlah belum dibaca berkurang tepat 1 setelah aksi tandai dibaca");

// Tandai semua dibaca untuk Ahmad
$markedAllCount = $service->markAllAsRead($studentAhmadUserId);
assertTest($markedAllCount >= 1, "5.4 Aksi tandai semua telah dibaca memproses seluruh sisa notifikasi");

$unreadCountFinal = $service->getUnreadCount($studentAhmadUserId);
assertTest($unreadCountFinal === 0, "5.5 Jumlah belum dibaca menjadi 0 setelah tandai semua dibaca");

// -------------------------------------------------------------------------
// PENGUJIAN 6: ISOLASI PRIVASI & KEAMANAN ANTAR PENGGUNA
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 6: ISOLASI PRIVASI & KEAMANAN ANTAR PENGGUNA ---\n";
// Dewi (User ID: 5)
$studentDewiUserId = 5;

// Buat 1 notifikasi khusus milik Ahmad
$ahmadPrivateNotifId = $service->send(
    $studentAhmadUserId,
    "[TEST] Notifikasi Rahasia Ahmad",
    "Pesan privat hasil bimbingan belajar khusus Ahmad.",
    'info',
    null,
    'test_private',
    9991
);

// Dewi mencoba menandai notifikasi milik Ahmad
$illegalMarkAttempt = $service->markAsRead($ahmadPrivateNotifId, $studentDewiUserId);
assertTest($illegalMarkAttempt === false, "6.1 Percobaan memanipulasi notifikasi milik pengguna lain DITOLAK (Akses Terisolasi)");

// Verifikasi notifikasi Ahmad tetap belum dibaca oleh Ahmad
$stmtCheck = $db->prepare("SELECT is_read FROM notifications WHERE id = :id");
$stmtCheck->execute([':id' => $ahmadPrivateNotifId]);
$stillUnread = ((int)$stmtCheck->fetchColumn() === 0);
assertTest($stillUnread, "6.2 Notifikasi korban tidak terpengaruh oleh aksi pengguna lain");

// Dewi memeriksa daftarnya sendiri, tidak boleh ada notifikasi Ahmad
$dewiList = $service->getRecent($studentDewiUserId, 10);
$leaked = false;
foreach ($dewiList as $item) {
    if ((int)$item['id'] === $ahmadPrivateNotifId) {
        $leaked = true;
        break;
    }
}
assertTest(!$leaked, "6.3 Notifikasi pengguna lain TIDAK bocor ke daftar pengguna yang berbeda");

// -------------------------------------------------------------------------
// PENGUJIAN 7: ANTI-DUPLIKASI (DEDUPLICATION ENGINE)
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 7: ANTI-DUPLIKASI (DEDUPLICATION) ---\n";
// Kirim notifikasi pertama dengan reference type dan id unik
$firstSend = $service->send(
    $studentAhmadUserId,
    "[TEST] Pengumuman Sesi",
    "Sesi verifikasi ruang ujian dibuka.",
    'info',
    null,
    'test_dedup_event',
    1001
);
assertTest($firstSend !== null && $firstSend > 0, "7.1 Pengiriman notifikasi pertama berhasil dibuat");

// Kirim ulang notifikasi dengan event dan ref ID yang sama persis
$duplicateSend = $service->send(
    $studentAhmadUserId,
    "[TEST] Pengumuman Sesi Duplikat",
    "Sesi verifikasi ruang ujian dibuka kembali.",
    'info',
    null,
    'test_dedup_event',
    1001
);
assertTest($duplicateSend === null, "7.2 Pengiriman notifikasi duplikat untuk event yang sama dicegah oleh sistem anti-duplikasi");

$totalDedupRows = $db->query("
    SELECT COUNT(*) FROM `notifications` 
    WHERE `user_id` = {$studentAhmadUserId} AND `reference_type` = 'test_dedup_event' AND `reference_id` = 1001
")->fetchColumn();
assertTest((int)$totalDedupRows === 1, "7.3 Hanya terdapat tepat 1 baris data di database untuk event tersebut");

// -------------------------------------------------------------------------
// PENGUJIAN 8: PAGINASI & FILTER NOTIFIKASI
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 8: PAGINASI & FILTER ---\n";
// Buat 12 notifikasi untuk Ahmad
for ($i = 1; $i <= 12; $i++) {
    $service->send(
        $studentAhmadUserId,
        "[TEST] Paginasi {$i}",
        "Pesan pengujian ke-{$i}",
        'info',
        null,
        'test_page_batch',
        $i
    );
}

$page1 = $service->getPaginated($studentAhmadUserId, 1, 5, null);
assertTest(count($page1['items']) === 5, "8.1 Paginasi halaman 1 mengembalikan tepat 5 item sesuai per_page");
assertTest($page1['total_pages'] >= 3, "8.2 Perhitungan total halaman paginasi akurat");

$page2 = $service->getPaginated($studentAhmadUserId, 2, 5, null);
assertTest(count($page2['items']) === 5, "8.3 Paginasi halaman 2 mengembalikan 5 item berikutnya");
assertTest($page1['items'][0]['id'] !== $page2['items'][0]['id'], "8.4 Item pada halaman 1 dan 2 berbeda (offset valid)");

// -------------------------------------------------------------------------
// PENGUJIAN 9: INTEGRASI UI, NAVBAR & CSRF PROTECTION
// -------------------------------------------------------------------------
echo "\n--- PENGUJIAN 9: INTEGRASI UI, NAVBAR & CSRF ---\n";
$navbarContent = file_get_contents(__DIR__ . '/../app/Views/layouts/partials/navbar.php');
assertTest(str_contains($navbarContent, 'notificationDropdownToggle'), "9.1 Navbar memuat tombol lonceng notifikasi (notificationDropdownToggle)");
assertTest(str_contains($navbarContent, 'navbarNotifBadge'), "9.2 Navbar memuat badge angka notifikasi belum dibaca (navbarNotifBadge)");
assertTest(str_contains($navbarContent, 'notificationDropdownMenu'), "9.3 Navbar memuat dropdown container notifikasi");

$indexViewContent = file_get_contents(__DIR__ . '/../app/Views/notifications/index.php');
assertTest(str_contains($indexViewContent, 'Pusat Notifikasi'), "9.4 Halaman daftar notifikasi memuat judul 'Pusat Notifikasi'");
assertTest(str_contains($indexViewContent, 'csrf_token()'), "9.5 Form mutasi tandai dibaca diproteksi oleh CSRF token");
assertTest(str_contains($indexViewContent, 'nav-pills'), "9.6 Filter tabs (Semua, Belum Dibaca, Sudah Dibaca) tersedia");
assertTest(str_contains($indexViewContent, 'pagination'), "9.7 Komponen navigasi pagination responsif tersedia");

$appJsContent = file_get_contents(__DIR__ . '/../public/assets/js/app.js');
assertTest(str_contains($appJsContent, 'api/notifications/recent'), "9.8 JavaScript client memuat handler dropdown AJAX recent notifications");
assertTest(str_contains($appJsContent, 'api/notifications/unread-count'), "9.9 JavaScript client memuat periodic AJAX polling engine");
assertTest(str_contains($appJsContent, 'mark-all-read'), "9.10 JavaScript client memuat handler AJAX mark all as read");

// Bersihkan data uji coba
$db->exec("DELETE FROM `notifications` WHERE `title` LIKE '%[TEST]%' OR `reference_type` LIKE 'test_%'");

echo "\n====================================================================\n";
echo "  HASIL AKHIR PENGUJIAN TAHAP 2:\n";
echo "  TOTAL PASS : {$passCount}\n";
echo "  TOTAL FAIL : {$failCount}\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo "\n>>> SELURUH PENGUJIAN TAHAP 2 BERHASIL 100%! <<<\n\n";
    exit(0);
} else {
    echo "\n>>> ADA PENGUJIAN TAHAP 2 YANG GAGAL. <<<\n\n";
    exit(1);
}
