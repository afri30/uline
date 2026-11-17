<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Database;
use App\Core\Env;
use PDO;
use Exception;
use Throwable;

/**
 * Service Notifikasi WhatsApp Resmi Berbasis Fonnte API
 * Mengelola pengiriman pesan, pemformatan template, pencegahan duplikasi, dan pencatatan log.
 */
class FonnteService
{
    private PDO $db;
    private string $token;
    private string $endpoint;
    private int $timeout;
    private string $defaultCountryCode;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->token = trim((string)Env::get('FONNTE_TOKEN', ''));
        $this->endpoint = trim((string)Env::get('FONNTE_ENDPOINT', 'https://api.fonnte.com/send'));
        $this->timeout = max(5, (int)Env::get('FONNTE_TIMEOUT', 15));
        $this->defaultCountryCode = trim((string)Env::get('FONNTE_COUNTRY_CODE', '62'));
    }

    /**
     * Cek apakah token Fonnte telah dikonfigurasi di file environment
     */
    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Ambil status konfigurasi Fonnte tanpa membocorkan token asli ke publik
     */
    public function getConfigurationStatus(): array
    {
        $isConfigured = $this->isConfigured();
        $maskedToken = '';
        if ($isConfigured) {
            $len = strlen($this->token);
            if ($len > 8) {
                $maskedToken = substr($this->token, 0, 4) . str_repeat('*', $len - 8) . substr($this->token, -4);
            } else {
                $maskedToken = str_repeat('*', $len);
            }
        }

        return [
            'is_configured' => $isConfigured,
            'endpoint' => $this->endpoint,
            'masked_token' => $maskedToken,
            'timeout' => $this->timeout,
            'country_code' => $this->defaultCountryCode
        ];
    }

    /**
     * Ambil status perangkat WhatsApp dari Fonnte API secara realtime
     */
    public function getDeviceStatus(): array
    {
        if (!$this->isConfigured()) {
            return [
                'status' => false,
                'connected' => false,
                'device' => '-',
                'device_status' => 'Belum Dikonfigurasi',
                'quota' => '0',
                'name' => '-',
                'package' => '-',
                'expired' => '-'
            ];
        }

        try {
            $ch = curl_init('https://api.fonnte.com/device');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $this->token
                ],
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && !empty($body)) {
                $decoded = json_decode((string)$body, true);
                if (is_array($decoded) && ($decoded['status'] ?? false) === true) {
                    $devStatus = strtolower((string)($decoded['device_status'] ?? 'disconnect'));
                    return [
                        'status' => true,
                        'connected' => in_array($devStatus, ['connect', 'connected']),
                        'device' => $decoded['device'] ?? '-',
                        'device_status' => $decoded['device_status'] ?? 'unknown',
                        'quota' => (string)($decoded['quota'] ?? '0'),
                        'name' => $decoded['name'] ?? '-',
                        'package' => $decoded['package'] ?? 'Free',
                        'expired' => $decoded['expired'] ?? '-'
                    ];
                }
            }
        } catch (Throwable $e) {}

        return [
            'status' => false,
            'connected' => false,
            'device' => '-',
            'device_status' => 'Tidak Terhubung',
            'quota' => '0',
            'name' => '-',
            'package' => '-',
            'expired' => '-'
        ];
    }

    /**
     * Cek apakah jenis notifikasi tertentu diaktifkan di tabel whatsapp_settings
     */
    public function isNotificationTypeEnabled(string $key): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT `setting_value` FROM `whatsapp_settings` WHERE `setting_key` = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();
            return ($val === false) ? true : ($val === '1' || $val === 'true');
        } catch (Throwable $e) {
            return true; // Default fallback enabled
        }
    }

    /**
     * Format dan validasi nomor WhatsApp penerima
     * Menerima format lokal (08xxx) atau internasional (628xxx / +628xxx)
     * Mengembalikan format bersih siap kirim untuk Fonnte (e.g. 08123456789 atau 628123456789)
     */
    public function formatPhoneNumber(string $phone): ?string
    {
        $cleaned = trim($phone);
        // Hapus karakter non-digit selain tanda plus di depan
        $hasPlus = str_starts_with($cleaned, '+');
        $digits = preg_replace('/[^\d]/', '', $cleaned);

        if (empty($digits)) {
            return null;
        }

        // Normalisasi format Indonesia
        if ($hasPlus && str_starts_with($digits, '62')) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '62')) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '0')) {
            // Ubah 08xxx menjadi 628xxx agar universal di gateway WhatsApp
            $normalized = '62' . substr($digits, 1);
        } else {
            $normalized = $this->defaultCountryCode . $digits;
        }

        // Validasi panjang nomor (minimal 10 digit, maksimal 16 digit)
        if (strlen($normalized) >= 10 && strlen($normalized) <= 16) {
            return $normalized;
        }

        return null;
    }

    /**
     * Cek apakah user telah mengaktifkan persetujuan WhatsApp (opt-in)
     */
    public function isUserConsented(?int $userId): bool
    {
        if ($userId === null || $userId <= 0) {
            return true; // Pesan langsung/test tanpa user_id spesifik dianggap diizinkan
        }

        try {
            $stmt = $this->db->prepare("SELECT `whatsapp_opt_in` FROM `users` WHERE `id` = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $val = $stmt->fetchColumn();
            return ($val !== false && (int)$val === 1);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Eksekusi pengiriman pesan WhatsApp via Fonnte API
     *
     * @param string $targetNumber Nomor tujuan
     * @param string $message Isi pesan teks
     * @param string $notificationType Jenis notifikasi (e.g. EXAM_NEW, EXAM_REMINDER, dll.)
     * @param int|null $userId ID Pengguna penerima
     * @param string|null $idempotencyKey Kunci unik anti-duplikasi
     * @return array Hasil pengiriman terstruktur
     */
    public function sendMessage(
        string $targetNumber,
        string $message,
        string $notificationType = 'GENERAL',
        ?int $userId = null,
        ?string $idempotencyKey = null
    ): array {
        $message = trim($message);

        // 1. Validasi Nomor Telepon
        $validPhone = $this->formatPhoneNumber($targetNumber);
        if ($validPhone === null) {
            $logId = $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $targetNumber,
                'notification_type' => $notificationType,
                'message' => $message,
                'provider' => 'fonnte',
                'provider_response' => json_encode(['error' => 'Invalid phone number format']),
                'status' => 'FAILED',
                'error_message' => 'Format nomor WhatsApp tidak valid (harus 10-15 digit angka).',
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'FAILED',
                'stage' => 'validation',
                'message' => 'Nomor WhatsApp tidak valid. Pastikan format nomor diawali 08xx atau 628xx.',
                'log_id' => $logId
            ];
        }

        // 2. Cek Persetujuan Pengguna (Opt-In Consent)
        if ($userId !== null && !$this->isUserConsented($userId)) {
            $logId = $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $validPhone,
                'notification_type' => $notificationType,
                'message' => $message,
                'provider' => 'fonnte',
                'provider_response' => json_encode(['skipped' => 'User has not opted in for WhatsApp notifications']),
                'status' => 'FAILED',
                'error_message' => 'Pengguna belum mengaktifkan izin notifikasi WhatsApp (opt-in).',
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'SKIPPED_NOT_CONSENTED',
                'stage' => 'consent_check',
                'message' => 'Pengiriman dilewati karena pengguna belum menyetujui penerimaan pesan WhatsApp.',
                'log_id' => $logId
            ];
        }

        // 3. Pencegahan Pesan Duplikat (Idempotency)
        if (!empty($idempotencyKey)) {
            $stmt = $this->db->prepare("
                SELECT `id`, `status` FROM `whatsapp_logs`
                WHERE `idempotency_key` = :ikey
                LIMIT 1
            ");
            $stmt->execute([':ikey' => $idempotencyKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return [
                    'success' => ($existing['status'] === 'SUCCESS'),
                    'status' => 'SKIPPED_DUPLICATE',
                    'stage' => 'deduplication',
                    'message' => 'Pesan dengan kunci deduplikasi ini sudah pernah diproses sebelumnya.',
                    'log_id' => (int)$existing['id']
                ];
            }
        }

        // 4. Periksa Ketersediaan Token Fonnte
        if (!$this->isConfigured()) {
            $logId = $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $validPhone,
                'notification_type' => $notificationType,
                'message' => $message,
                'provider' => 'fonnte',
                'provider_response' => json_encode(['error' => 'Gateway token is not configured in environment']),
                'status' => 'FAILED',
                'error_message' => 'Token gateway WhatsApp belum dikonfigurasi di file environment (.env). Pengiriman dilewati.',
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'FAILED',
                'stage' => 'configuration_check',
                'message' => 'Layanan WhatsApp belum siap: Token gateway belum dikonfigurasi di backend (.env).',
                'log_id' => $logId
            ];
        }

        // 5. Catat log awal berstatus 'PENDING'
        $logId = $this->logMessage([
            'user_id' => $userId,
            'phone_number' => $validPhone,
            'notification_type' => $notificationType,
            'message' => $message,
            'provider' => 'fonnte',
            'provider_response' => null,
            'status' => 'PENDING',
            'idempotency_key' => $idempotencyKey
        ]);

        // 6. Eksekusi HTTP Request ke Fonnte API dengan Retry Terbatas
        return $this->executeFonnteRequest($logId, $validPhone, $message);
    }

    /**
     * Eksekusi request cURL ke Fonnte API dengan penanganan timeout & retry terkendali
     */
    private function executeFonnteRequest(int $logId, string $target, string $message): array
    {
        $maxRetries = 2;
        $attempt = 0;
        $lastError = '';
        $responseBody = '';
        $httpCode = 0;

        $postData = [
            'target' => $target,
            'message' => $message,
            'countryCode' => $this->defaultCountryCode
        ];

        while ($attempt <= $maxRetries) {
            $attempt++;

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $this->token
                ],
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $responseBody = (string)curl_exec($curl);
            $curlError = curl_error($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($curlError) {
                $lastError = "Koneksi cURL gagal (Percobaan {$attempt}): {$curlError}";
                if ($attempt <= $maxRetries) {
                    usleep(500000); // 500ms backoff
                    continue;
                }
                break;
            }

            // Jika menerima respons dari server Fonnte (HTTP 200 atau 4xx/5xx)
            $decoded = json_decode($responseBody, true);

            // Periksa payload status resmi Fonnte
            // Dokumentasi Fonnte: status === true artinya permintaan diterima gateway
            if ($httpCode === 200 && is_array($decoded) && ($decoded['status'] ?? false) === true) {
                // Jangan simpan token atau informasi sensitif pada provider_response
                $sanitizedResponse = json_encode([
                    'status' => true,
                    'process' => $decoded['process'] ?? 'processing',
                    'target' => $decoded['target'] ?? [$target],
                    'id' => $decoded['id'] ?? []
                ], JSON_UNESCAPED_UNICODE);

                $this->updateMessageLog($logId, [
                    'status' => 'SUCCESS',
                    'provider_response' => $sanitizedResponse,
                    'error_message' => null,
                    'retry_count' => $attempt - 1,
                    'sent_at' => date('Y-m-d H:i:s')
                ]);

                return [
                    'success' => true,
                    'status' => 'SUCCESS',
                    'stage' => 'gateway_accepted',
                    'message' => 'Pesan WhatsApp berhasil diterima oleh gateway Fonnte dan dijadwalkan terkirim.',
                    'http_code' => $httpCode,
                    'fonnte_id' => $decoded['id'][0] ?? null,
                    'process' => $decoded['process'] ?? 'processing',
                    'log_id' => $logId
                ];
            }

            // Respons Fonnte status false (misal token salah, perangkat disconnect, nomor tidak terdaftar)
            if (is_array($decoded) && ($decoded['status'] ?? true) === false) {
                $reason = $decoded['reason'] ?? 'Permintaan ditolak oleh Fonnte API';
                $lastError = "Fonnte API Error: {$reason}";
                break; // Tidak perlu retry untuk error autentikasi/kredensial/target salah
            }

            // 5xx Server Error: Coba retry jika masih ada kuota percobaan
            if ($httpCode >= 500) {
                $lastError = "Fonnte Server Error (HTTP {$httpCode}): {$responseBody}";
                if ($attempt <= $maxRetries) {
                    usleep(800000); // 800ms backoff
                    continue;
                }
            } else {
                $lastError = "Respons HTTP {$httpCode} tidak valid: {$responseBody}";
                break;
            }
        }

        // Simpan log status FAILED setelah retry selesai
        $sanitizedFailResponse = json_encode([
            'http_code' => $httpCode,
            'raw_response' => mb_substr($responseBody, 0, 500)
        ], JSON_UNESCAPED_UNICODE);

        $this->updateMessageLog($logId, [
            'status' => 'FAILED',
            'provider_response' => $sanitizedFailResponse,
            'error_message' => $lastError,
            'retry_count' => $attempt - 1
        ]);

        return [
            'success' => false,
            'status' => 'FAILED',
            'stage' => 'gateway_rejected',
            'message' => 'Gagal mengirim pesan WhatsApp: ' . $lastError,
            'error' => $lastError,
            'http_code' => $httpCode,
            'log_id' => $logId
        ];
    }

    /**
     * 1. TEMPLATE: NOTIFIKASI UJIAN BARU
     */
    public function sendNewExamNotification(
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        string $subjectName,
        string $examDate,
        string $examTime,
        int $durationMinutes,
        ?int $userId = null,
        ?int $examId = null
    ): array {
        if (!$this->isNotificationTypeEnabled('notify_new_exam')) {
            return ['success' => false, 'status' => 'DISABLED_BY_ADMIN', 'message' => 'Notifikasi ujian baru dinonaktifkan oleh Administrator.'];
        }

        $message = "📢 *INFORMASI JADWAL UJIAN BARU (U-LINE)*\n\n" .
                   "Halo *{$studentName}*,\n" .
                   "Telah dijadwalkan ujian online baru untuk kelas Anda:\n\n" .
                   "📝 *Nama Ujian*: {$examTitle}\n" .
                   "📚 *Mata Pelajaran*: {$subjectName}\n" .
                   "📅 *Tanggal*: {$examDate}\n" .
                   "⏰ *Waktu*: {$examTime} WIB\n" .
                   "⏱️ *Durasi*: {$durationMinutes} Menit\n\n" .
                   "📌 Silakan login ke sistem *U-Line* sebelum waktu ujian dimulai dan pastikan koneksi internet stabil.";

        $idempotencyKey = $examId ? "fonnte_exam_new_{$examId}_{$userId}" : null;

        return $this->sendMessage($phoneNumber, $message, 'EXAM_NEW', $userId, $idempotencyKey);
    }

    /**
     * 2. TEMPLATE: PENGINGAT UJIAN (REMINDER)
     */
    public function sendExamReminderNotification(
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        string $startTime,
        string $leadTimeText = '15 menit',
        ?int $userId = null,
        ?int $examId = null
    ): array {
        if (!$this->isNotificationTypeEnabled('notify_exam_reminder')) {
            return ['success' => false, 'status' => 'DISABLED_BY_ADMIN', 'message' => 'Pengingat jadwal ujian dinonaktifkan oleh Administrator.'];
        }

        $message = "⏰ *PENGINGAT UJIAN ONLINE (U-LINE)*\n\n" .
                   "Halo *{$studentName}*,\n" .
                   "Ujian Anda akan segera dimulai dalam waktu *{$leadTimeText}*:\n\n" .
                   "📝 *Nama Ujian*: {$examTitle}\n" .
                   "⏰ *Waktu Mulai*: {$startTime} WIB\n" .
                   "⏳ *Sisa Waktu*: {$leadTimeText}\n\n" .
                   "⚠️ Segera login dan bersiap di ruang ujian. Pastikan kamera wajah dan meja terhubung dengan baik.";

        $idempotencyKey = $examId ? "fonnte_exam_remind_{$examId}_{$userId}" : null;

        return $this->sendMessage($phoneNumber, $message, 'EXAM_REMINDER', $userId, $idempotencyKey);
    }

    /**
     * 3. TEMPLATE: PERUBAHAN JADWAL
     */
    public function sendScheduleChangeNotification(
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        string $newScheduleDateTime,
        ?int $userId = null,
        ?int $examId = null
    ): array {
        if (!$this->isNotificationTypeEnabled('notify_schedule_change')) {
            return ['success' => false, 'status' => 'DISABLED_BY_ADMIN', 'message' => 'Pemberitahuan perubahan jadwal dinonaktifkan oleh Administrator.'];
        }

        $message = "📅 *PEMBERITAHUAN PERUBAHAN JADWAL UJIAN*\n\n" .
                   "Halo *{$studentName}*,\n" .
                   "Terdapat penyesuaian jadwal pelaksanaan ujian pada sistem *U-Line*:\n\n" .
                   "📝 *Nama Ujian*: {$examTitle}\n" .
                   "🔄 *Jadwal Baru*: {$newScheduleDateTime} WIB\n\n" .
                   "Silakan periksa dashboard akun Anda untuk informasi selengkapnya.";

        $hashSched = md5($newScheduleDateTime);
        $idempotencyKey = $examId ? "fonnte_sched_change_{$examId}_{$userId}_{$hashSched}" : null;

        return $this->sendMessage($phoneNumber, $message, 'SCHEDULE_CHANGED', $userId, $idempotencyKey);
    }

    /**
     * 4. TEMPLATE: HASIL UJIAN
     */
    public function sendExamResultNotification(
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        float $score,
        ?int $userId = null,
        ?int $sessionId = null
    ): array {
        if (!$this->isNotificationTypeEnabled('notify_exam_result')) {
            return ['success' => false, 'status' => 'DISABLED_BY_ADMIN', 'message' => 'Notifikasi hasil ujian dinonaktifkan oleh Administrator.'];
        }

        $formattedScore = number_format($score, 1, ',', '.');
        $message = "🏆 *PENGUMUMAN NILAI UJIAN (U-LINE)*\n\n" .
                   "Halo *{$studentName}*,\n" .
                   "Hasil evaluasi ujian Anda telah selesai diproses oleh sistem:\n\n" .
                   "📝 *Nama Ujian*: {$examTitle}\n" .
                   "📊 *Nilai Akhir*: *{$formattedScore}* / 100\n\n" .
                   "Pembahasan butir soal interaktif dan penjelasan AI dapat Anda akses melalui menu *Riwayat Hasil Ujian*.";

        $idempotencyKey = $sessionId ? "fonnte_exam_result_{$sessionId}_{$userId}" : null;

        return $this->sendMessage($phoneNumber, $message, 'EXAM_RESULT', $userId, $idempotencyKey);
    }

    /**
     * 5. TEMPLATE: NOTIFIKASI GURU (RINGKASAN UJIAN SELESAI)
     */
    public function sendTeacherExamSummaryNotification(
        string $phoneNumber,
        string $teacherName,
        string $examTitle,
        int $totalParticipants,
        int $totalFinished,
        ?int $userId = null,
        ?int $examId = null
    ): array {
        if (!$this->isNotificationTypeEnabled('notify_teacher_finish')) {
            return ['success' => false, 'status' => 'DISABLED_BY_ADMIN', 'message' => 'Notifikasi ringkasan guru dinonaktifkan oleh Administrator.'];
        }

        $message = "📋 *LAPORAN RINGKASAN UJIAN GURU*\n\n" .
                   "Halo Bapak/Ibu *{$teacherName}*,\n" .
                   "Ujian *{$examTitle}* telah selesai dilaksanakan oleh seluruh peserta:\n\n" .
                   "👥 *Total Peserta*: {$totalParticipants} Siswa\n" .
                   "✅ *Telah Selesai*: {$totalFinished} Siswa\n\n" .
                   "Silakan buka dashboard Guru (/guru/results) untuk melihat rekapitulasi nilai dan mengunduh berkas CSV.";

        $idempotencyKey = $examId ? "fonnte_teacher_summary_{$examId}_{$userId}" : null;

        return $this->sendMessage($phoneNumber, $message, 'TEACHER_SUMMARY', $userId, $idempotencyKey);
    }

    /**
     * 6. TEMPLATE: KODE OTP LOGIN WHATSAPP
     */
    public function sendLoginOtp(
        string $phoneNumber,
        string $userName,
        string $otpCode,
        ?int $userId = null
    ): array {
        if (!$this->isNotificationTypeEnabled('notify_login_otp')) {
            return ['success' => false, 'status' => 'DISABLED_BY_ADMIN', 'message' => 'Layanan login dengan OTP WhatsApp dinonaktifkan oleh Administrator.'];
        }

        $message = "🔐 *KODE VERIFIKASI (OTP) U-LINE*\n\n" .
                   "Halo *{$userName}*,\n" .
                   "Berikut adalah Kode Verifikasi (OTP) untuk masuk ke akun *Sistem Ujian Online U-Line* Anda:\n\n" .
                   "👉 *{$otpCode}*\n\n" .
                   "⏱️ Kode ini hanya berlaku selama *5 menit*.\n" .
                   "⚠️ *PENTING*: JANGAN pernah membagikan kode OTP ini kepada siapa pun termasuk pihak sekolah atau pengawas ujian.\n\n" .
                   "_Jika Anda tidak merasa meminta kode ini, silakan abaikan pesan ini._";

        return $this->sendMessage($phoneNumber, $message, 'LOGIN_OTP', $userId, null);
    }

    /**
     * Test Koneksi & Pengiriman Pesan (Digunakan oleh Admin Dashboard)
     */
    public function sendTestMessage(string $targetNumber, string $testMessage = 'Pesan uji coba integrasi Fonnte WhatsApp Gateway U-Line.'): array
    {
        $adminUser = auth();
        $adminId = $adminUser ? (int)$adminUser['id'] : null;

        $fullMsg = "Halo! Ini adalah pesan uji coba (Test Message) dari Sistem Ujian Online U-Line.\n\n" .
                   "Pesan: {$testMessage}\n" .
                   "Waktu Server: " . date('d M Y, H:i:s') . " WIB\n" .
                   "Status Gateway: Terhubung ke Fonnte API.";

        return $this->sendMessage($targetNumber, $fullMsg, 'ADMIN_TEST', $adminId, null);
    }

    /**
     * Simpan baris log ke database
     */
    public function logMessage(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO `whatsapp_logs` (
                `user_id`, `phone_number`, `notification_type`, `message`,
                `provider`, `provider_response`, `status`, `error_message`,
                `retry_count`, `idempotency_key`, `sent_at`, `created_at`
            ) VALUES (
                :user_id, :phone_number, :notification_type, :message,
                :provider, :provider_response, :status, :error_message,
                :retry_count, :idempotency_key, :sent_at, NOW()
            )
        ");

        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':phone_number' => $data['phone_number'],
            ':notification_type' => $data['notification_type'] ?? 'GENERAL',
            ':message' => $data['message'] ?? '',
            ':provider' => $data['provider'] ?? 'fonnte',
            ':provider_response' => $data['provider_response'] ?? null,
            ':status' => $data['status'] ?? 'PENDING',
            ':error_message' => $data['error_message'] ?? null,
            ':retry_count' => (int)($data['retry_count'] ?? 0),
            ':idempotency_key' => $data['idempotency_key'] ?? null,
            ':sent_at' => $data['sent_at'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Perbarui baris log yang sudah ada
     */
    private function updateMessageLog(int $logId, array $data): void
    {
        $fields = [];
        $params = [':id' => $logId];

        foreach ($data as $key => $val) {
            $fields[] = "`{$key}` = :{$key}";
            $params[":{$key}"] = $val;
        }

        if (empty($fields)) {
            return;
        }

        $sql = "UPDATE `whatsapp_logs` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}
