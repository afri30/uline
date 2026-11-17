<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Database;
use App\Services\Notification\Contracts\WhatsAppNotificationInterface;
use PDO;
use Exception;

/**
 * Service Notifikasi WhatsApp Business Platform (Meta Cloud API Gateway)
 * Menyediakan pengiriman pesan terstandarisasi, aman, dan patuh kebijakan Meta.
 */
class WhatsAppService implements WhatsAppNotificationInterface
{
    private PDO $db;
    private bool $enabled;
    private string $provider;
    private string $apiToken;
    private string $phoneNumberId;
    private string $apiVersion;
    private int $rateLimitPerMinute;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->enabled = filter_var(\App\Core\Env::get('WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $this->provider = (string)\App\Core\Env::get('WHATSAPP_PROVIDER', 'meta_cloud_api');
        $this->apiToken = trim((string)\App\Core\Env::get('WHATSAPP_API_TOKEN', ''));
        $this->phoneNumberId = trim((string)\App\Core\Env::get('WHATSAPP_PHONE_NUMBER_ID', ''));
        $this->apiVersion = trim((string)\App\Core\Env::get('WHATSAPP_API_VERSION', 'v19.0'));
        $this->rateLimitPerMinute = max(1, (int)\App\Core\Env::get('WHATSAPP_RATE_LIMIT_PER_MINUTE', 30));
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->apiToken) && !empty($this->phoneNumberId);
    }

    /**
     * @inheritDoc
     */
    public function formatPhoneNumber(string $phone): ?string
    {
        // 1. Bersihkan spasi, tanda strip, kurung, dan karakter non-numerik (selain tanda plus di awal)
        $cleaned = trim($phone);
        $hasPlus = str_starts_with($cleaned, '+');
        $digits = preg_replace('/[^\d]/', '', $cleaned);

        if (empty($digits)) {
            return null;
        }

        // 2. Normalisasi format nomor Indonesia / Internasional
        if ($hasPlus) {
            $e164 = '+' . $digits;
        } elseif (str_starts_with($digits, '62')) {
            $e164 = '+' . $digits;
        } elseif (str_starts_with($digits, '0')) {
            // Ganti 0 depan dengan kode negara +62
            $e164 = '+62' . substr($digits, 1);
        } else {
            // Default tambah +62 jika nomor langsung diawali angka operator (misal 812xxxx)
            $e164 = '+62' . $digits;
        }

        // 3. Validasi panjang E.164 (standar ITU-T E.164: + diikuti 7 sampai 15 digit angka)
        if (preg_match('/^\+[1-9]\d{7,14}$/', $e164)) {
            return $e164;
        }

        return null;
    }

    /**
     * Cek persetujuan pengguna untuk menerima notifikasi WhatsApp
     */
    public function isUserConsented(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT `whatsapp_opt_in` FROM `users` WHERE `id` = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $val = $stmt->fetchColumn();
        return ($val !== false && (int)$val === 1);
    }

    /**
     * @inheritDoc
     */
    public function sendTemplate(
        string $toPhoneNumber,
        string $templateName,
        array $parameters = [],
        ?int $userId = null,
        ?string $idempotencyKey = null
    ): array {
        // 1. Validasi Format Nomor Telepon
        $formattedPhone = $this->formatPhoneNumber($toPhoneNumber);
        if ($formattedPhone === null) {
            $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $toPhoneNumber,
                'template_name' => $templateName,
                'message_payload' => json_encode(['parameters' => $parameters], JSON_UNESCAPED_UNICODE),
                'status' => 'failed',
                'error_message' => 'Format nomor telepon tidak valid menurut standar internasional E.164.',
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'invalid_phone',
                'message' => 'Nomor telepon tidak valid menurut format internasional E.164 (+62xxxxxxxx).'
            ];
        }

        // 2. Cek Persetujuan Pengguna (Opt-In Consent) jika userId tersedia
        if ($userId !== null && !$this->isUserConsented($userId)) {
            $logId = $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $formattedPhone,
                'template_name' => $templateName,
                'message_payload' => json_encode(['parameters' => $parameters], JSON_UNESCAPED_UNICODE),
                'status' => 'skipped_not_consented',
                'error_message' => 'Pengguna belum memberikan persetujuan penerimaan notifikasi WhatsApp (opt-in = 0).',
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'skipped_not_consented',
                'message' => 'Pengiriman dilewati karena pengguna belum mengaktifkan izin notifikasi WhatsApp.',
                'log_id' => $logId
            ];
        }

        // 3. Cek Idempotency (Pencegahan Pengiriman Ganda)
        if (!empty($idempotencyKey)) {
            $stmt = $this->db->prepare("
                SELECT `id`, `status` FROM `whatsapp_logs` 
                WHERE `idempotency_key` = :ikey LIMIT 1
            ");
            $stmt->execute([':ikey' => $idempotencyKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return [
                    'success' => ($existing['status'] === 'sent'),
                    'status' => 'skipped_duplicate',
                    'message' => 'Pesan dengan idempotency key ini sudah pernah diproses sebelumnya.',
                    'log_id' => (int)$existing['id']
                ];
            }
        }

        // 4. Rate Limiting Pengiriman WhatsApp per Menit
        $stmtCount = $this->db->query("
            SELECT COUNT(*) FROM `whatsapp_logs` 
            WHERE `status` = 'sent' AND `sent_at` >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $sentLastMinute = (int)$stmtCount->fetchColumn();

        if ($sentLastMinute >= $this->rateLimitPerMinute) {
            $logId = $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $formattedPhone,
                'template_name' => $templateName,
                'message_payload' => json_encode(['parameters' => $parameters], JSON_UNESCAPED_UNICODE),
                'status' => 'failed',
                'error_message' => "Batas pengiriman pesan ({$this->rateLimitPerMinute} pesan/menit) tercapai.",
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'rate_limited',
                'message' => 'Batas pengiriman pesan WhatsApp per menit tercapai. Coba beberapa saat lagi.',
                'log_id' => $logId
            ];
        }

        // 5. Cek Ketersediaan Kredensial Resmi
        if (!$this->isConfigured()) {
            // CATAT DENGAN TRANSPARAN: Tidak mengarang kredensial atau memalsukan status pengiriman
            $logId = $this->logMessage([
                'user_id' => $userId,
                'phone_number' => $formattedPhone,
                'template_name' => $templateName,
                'message_payload' => json_encode([
                    'provider' => $this->provider,
                    'template' => $templateName,
                    'parameters' => $parameters
                ], JSON_UNESCAPED_UNICODE),
                'status' => 'skipped_no_credential',
                'error_message' => 'Kredensial WhatsApp API belum dikonfigurasi di .env (WHATSAPP_API_TOKEN / WHATSAPP_PHONE_NUMBER_ID). Pengiriman dilewati secara aman tanpa kepalsuan data.',
                'idempotency_key' => $idempotencyKey
            ]);

            return [
                'success' => false,
                'status' => 'skipped_no_credential',
                'message' => 'Kredensial WhatsApp API belum dikonfigurasi di file environment (.env). Pengiriman dilewati.',
                'log_id' => $logId
            ];
        }

        // 6. Siapkan Payload Resmi Meta WhatsApp Cloud API
        $formattedParams = [];
        foreach ($parameters as $param) {
            $formattedParams[] = [
                'type' => 'text',
                'text' => (string)$param
            ];
        }

        $waPayload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => ltrim($formattedPhone, '+'),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'id'
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $formattedParams
                    ]
                ]
            ]
        ];

        // Buat log awal berstatus 'pending'
        $logId = $this->logMessage([
            'user_id' => $userId,
            'phone_number' => $formattedPhone,
            'template_name' => $templateName,
            'message_payload' => json_encode($waPayload, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey
        ]);

        // 7. Eksekusi HTTP Request ke Meta Cloud API dengan Retry Terkendali
        return $this->executeHttpRequest($logId, $waPayload);
    }

    /**
     * Eksekusi request cURL ke endpoint Meta Cloud API dengan mekanisme retry
     */
    private function executeHttpRequest(int $logId, array $payload): array
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
        $maxRetries = 3;
        $attempt = 0;
        $lastError = '';
        $responseBody = '';
        $httpCode = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);

            $responseBody = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlError) {
                $lastError = "cURL Error (Attempt {$attempt}): {$curlError}";
                usleep(300000); // 300ms backoff
                continue;
            }

            // Sukses pengiriman (Meta API returns 200 OK or 201 Created)
            if ($httpCode >= 200 && $httpCode < 300) {
                $this->updateMessageLog($logId, [
                    'status' => 'sent',
                    'response_body' => (string)$responseBody,
                    'error_message' => null,
                    'retry_count' => $attempt - 1,
                    'sent_at' => date('Y-m-d H:i:s')
                ]);

                return [
                    'success' => true,
                    'status' => 'sent',
                    'message' => 'Pesan WhatsApp berhasil dikirim ke penerima.',
                    'http_code' => $httpCode,
                    'response' => json_decode((string)$responseBody, true),
                    'log_id' => $logId
                ];
            }

            // Gagal dari sisi client/Meta (4xx) - tidak perlu di-retry karena kredensial/payload keliru
            if ($httpCode >= 400 && $httpCode < 500) {
                $lastError = "Meta API Error (HTTP {$httpCode}): " . $responseBody;
                break;
            }

            // 5xx Server Error dari Meta - lakukan retry dengan backoff
            $lastError = "Meta Server Error (HTTP {$httpCode}): " . $responseBody;
            usleep(500000); // 500ms backoff
        }

        // Catat kegagalan setelah retry habis
        $this->updateMessageLog($logId, [
            'status' => 'failed',
            'response_body' => $responseBody ?: null,
            'error_message' => $lastError,
            'retry_count' => $attempt - 1
        ]);

        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'Gagal mengirim pesan WhatsApp ke Meta Cloud API.',
            'error' => $lastError,
            'http_code' => $httpCode,
            'log_id' => $logId
        ];
    }

    /**
     * @inheritDoc
     */
    public function sendExamScheduleNotice(
        int $userId,
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        string $scheduleTime
    ): array {
        $idempotencyKey = "wa_sched_{$userId}_" . md5("{$examTitle}_{$scheduleTime}");

        return $this->sendTemplate(
            $phoneNumber,
            'notifikasi_jadwal_ujian',
            [
                $studentName,
                $examTitle,
                $scheduleTime
            ],
            $userId,
            $idempotencyKey
        );
    }

    /**
     * @inheritDoc
     */
    public function sendExamResultNotice(
        int $userId,
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        float $score
    ): array {
        $idempotencyKey = "wa_result_{$userId}_" . md5("{$examTitle}_{$score}");

        return $this->sendTemplate(
            $phoneNumber,
            'notifikasi_hasil_ujian',
            [
                $studentName,
                $examTitle,
                number_format($score, 2)
            ],
            $userId,
            $idempotencyKey
        );
    }

    /**
     * Simpan baris log pesan WhatsApp ke database
     */
    private function logMessage(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO `whatsapp_logs` (
                `user_id`, `phone_number`, `template_name`, `message_payload`,
                `status`, `response_body`, `error_message`, `retry_count`,
                `idempotency_key`, `sent_at`, `created_at`
            ) VALUES (
                :user_id, :phone_number, :template_name, :message_payload,
                :status, :response_body, :error_message, :retry_count,
                :idempotency_key, :sent_at, NOW()
            )
        ");

        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':phone_number' => $data['phone_number'],
            ':template_name' => $data['template_name'],
            ':message_payload' => $data['message_payload'],
            ':status' => $data['status'] ?? 'pending',
            ':response_body' => $data['response_body'] ?? null,
            ':error_message' => $data['error_message'] ?? null,
            ':retry_count' => (int)($data['retry_count'] ?? 0),
            ':idempotency_key' => $data['idempotency_key'] ?? null,
            ':sent_at' => $data['sent_at'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Perbarui log pesan yang sudah ada
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

    /**
     * Ambil riwayat log notifikasi WhatsApp berdasarkan user
     */
    public function getLogsForUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM `whatsapp_logs`
            WHERE `user_id` = :uid
            ORDER BY `id` DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
