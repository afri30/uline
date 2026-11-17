<?php

declare(strict_types=1);

/**
 * CLI / Scheduler Script: Pengingat Jadwal Ujian Otomatis via WhatsApp (Fonnte API)
 *
 * Jalankan script ini secara berkala melalui Cron Job (Linux) atau Task Scheduler (Windows).
 * Contoh konfigurasi cron (setiap 5 menit):
 * * /5 * * * * php /var/www/html/cron/whatsapp_reminder.php >> /var/log/whatsapp_reminder.log 2>&1
 */

// Batasi eksekusi hanya melalui CLI atau request aman dengan secret key
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'uline_cron_secret')) {
    http_response_code(403);
    die("Akses ditolak: Script ini hanya dapat dijalankan melalui CLI (Scheduler/Cron Job).\n");
}

require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/WhatsAppSetting.php';
require_once __DIR__ . '/../app/Services/Notification/FonnteService.php';

use App\Core\Env;
use App\Core\Database;
use App\Models\WhatsAppSetting;
use App\Services\Notification\FonnteService;

Env::load();

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Memulai scheduler pengingat ujian WhatsApp...\n";

$fonnteService = new FonnteService();
$settingModel = new WhatsAppSetting();

// 1. Cek apakah fitur pengingat diaktifkan di admin settings
$isEnabled = $settingModel->get('notify_exam_reminder', '1');
if ($isEnabled !== '1') {
    echo "[{$timestamp}] Pengingat ujian dinonaktifkan di pengaturan Admin. Selesai.\n";
    exit(0);
}

// 2. Cek apakah token Fonnte sudah tersedia
if (!$fonnteService->isConfigured()) {
    echo "[{$timestamp}] FONNTE_TOKEN belum dikonfigurasi di file .env. Pengiriman dilewati.\n";
    exit(0);
}

// 3. Ambil lead minutes (default 15 menit)
$leadMinutes = (int)$settingModel->get('reminder_lead_minutes', '15');
if ($leadMinutes <= 0) {
    $leadMinutes = 15;
}

$db = Database::getConnection();

// 4. Cari ujian aktif yang akan dimulai dalam rentang sekarang s.d. :leadMinutes menit ke depan
$stmt = $db->prepare("
    SELECT e.id, e.title, e.start_at, e.duration, e.class_id, s.name AS subject_name, c.name AS class_name
    FROM `exams` e
    JOIN `subjects` s ON e.subject_id = s.id
    JOIN `classes` c ON e.class_id = c.id
    WHERE e.status IN ('published', 'active')
      AND e.start_at IS NOT NULL
      AND e.start_at > NOW()
      AND e.start_at <= DATE_ADD(NOW(), INTERVAL :lead MINUTE)
");
$stmt->bindValue(':lead', $leadMinutes, PDO::PARAM_INT);
$stmt->execute();
$upcomingExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalExams = count($upcomingExams);
echo "[{$timestamp}] Ditemukan {$totalExams} ujian yang akan dimulai dalam {$leadMinutes} menit ke depan.\n";

$sentCount = 0;
$skippedCount = 0;

foreach ($upcomingExams as $exam) {
    $examId = (int)$exam['id'];
    $examTitle = (string)$exam['title'];
    $startAt = (string)$exam['start_at'];
    $startTimeStr = date('H:i', strtotime($startAt));

    // Hitung sisa menit secara presisi
    $remainingMinutes = max(1, (int)round((strtotime($startAt) - time()) / 60));
    $leadText = "{$remainingMinutes} menit";

    // Ambil semua peserta di kelas ujian ini
    $stmtStudents = $db->prepare("
        SELECT s.id AS student_id, s.user_id, s.phone AS student_phone, u.name AS student_name, u.phone_number AS user_phone, u.whatsapp_opt_in
        FROM `students` s
        JOIN `users` u ON s.user_id = u.id
        WHERE s.class_id = :cid AND u.status = 'active'
    ");
    $stmtStudents->execute([':cid' => $exam['class_id']]);
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $student) {
        $userId = (int)$student['user_id'];
        $phone = !empty($student['user_phone']) ? (string)$student['user_phone'] : (!empty($student['student_phone']) ? (string)$student['student_phone'] : '');

        if (empty($phone)) {
            $skippedCount++;
            continue;
        }

        $res = $fonnteService->sendExamReminderNotification(
            $phone,
            (string)$student['student_name'],
            $examTitle,
            $startTimeStr,
            $leadText,
            $userId,
            $examId
        );

        if ($res['success']) {
            $sentCount++;
            echo "  [OK] Pengingat ujian '{$examTitle}' dikirim ke {$student['student_name']} ({$phone})\n";
        } else {
            // Status duplikat atau skip opt-in dicatat informatif
            $reason = $res['status'] ?? 'UNKNOWN';
            if ($reason === 'SKIPPED_DUPLICATE') {
                $skippedCount++;
            } else {
                echo "  [SKIP/FAIL] {$student['student_name']}: {$reason} - " . ($res['message'] ?? '') . "\n";
            }
        }
    }
}

echo "[{$timestamp}] Selesai. Pengingat terkirim: {$sentCount}, Dilewati/Duplikat: {$skippedCount}.\n";
