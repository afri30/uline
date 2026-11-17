<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Service Notifikasi Web Push (Service Worker & Push API)
 * Mengelola langganan perangkat (subscription), proteksi kunci privat VAPID, dan dispatch push.
 */
class WebPushService
{
    private PDO $db;
    private string $vapidPublicKey;
    private string $vapidPrivateKey;
    private string $vapidSubject;
    private bool $pushEnabled;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->vapidPublicKey = trim((string)\App\Core\Env::get('VAPID_PUBLIC_KEY', ''));
        $this->vapidPrivateKey = trim((string)\App\Core\Env::get('VAPID_PRIVATE_KEY', ''));
        $this->vapidSubject = trim((string)\App\Core\Env::get('VAPID_SUBJECT', 'mailto:admin@uline.test'));
        $this->pushEnabled = filter_var(\App\Core\Env::get('WEB_PUSH_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Ambil VAPID Public Key saja untuk dikirim ke browser client
     * (Kunci privat TIDAK PERNAH dikirim ke browser demi keamanan)
     */
    public function getPublicKey(): string
    {
        return $this->vapidPublicKey;
    }

    /**
     * Cek apakah Web Push telah terkonfigurasi dengan kunci VAPID yang valid
     */
    public function isConfigured(): bool
    {
        return $this->pushEnabled && !empty($this->vapidPublicKey) && !empty($this->vapidPrivateKey);
    }

    /**
     * Simpan atau perbarui subscription perangkat pengguna
     */
    public function subscribe(
        int $userId,
        string $endpoint,
        string $p256dh,
        string $auth,
        ?string $userAgent = null
    ): bool {
        $endpoint = trim($endpoint);
        $p256dh = trim($p256dh);
        $auth = trim($auth);

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            return false;
        }

        $endpointHash = hash('sha256', $endpoint);

        $stmt = $this->db->prepare("
            INSERT INTO `push_subscriptions` (
                `user_id`, `endpoint`, `endpoint_hash`, `p256dh`, `auth`, `user_agent`, `is_active`, `updated_at`
            ) VALUES (
                :uid, :endpoint, :hash, :p256dh, :auth, :ua, 1, NOW()
            )
            ON DUPLICATE KEY UPDATE
                `user_id` = :uid2,
                `p256dh` = :p256dh2,
                `auth` = :auth2,
                `user_agent` = :ua2,
                `is_active` = 1,
                `updated_at` = NOW()
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':endpoint' => $endpoint,
            ':hash' => $endpointHash,
            ':p256dh' => $p256dh,
            ':auth' => $auth,
            ':ua' => $userAgent,
            ':uid2' => $userId,
            ':p256dh2' => $p256dh,
            ':auth2' => $auth,
            ':ua2' => $userAgent
        ]);

        // Perbarui push_opt_in pengguna di tabel users
        $stmtUser = $this->db->prepare("UPDATE `users` SET `push_opt_in` = 1 WHERE `id` = :uid");
        $stmtUser->execute([':uid' => $userId]);

        return true;
    }

    /**
     * Hapus / nonaktifkan subscription perangkat pengguna (Unsubscribe)
     */
    public function unsubscribe(int $userId, string $endpoint): bool
    {
        $endpointHash = hash('sha256', trim($endpoint));

        $stmt = $this->db->prepare("
            UPDATE `push_subscriptions` 
            SET `is_active` = 0, `updated_at` = NOW()
            WHERE `user_id` = :uid AND `endpoint_hash` = :hash
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':hash' => $endpointHash
        ]);

        // Jika tidak ada subscription aktif tersisa, set push_opt_in = 0
        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) FROM `push_subscriptions`
            WHERE `user_id` = :uid AND `is_active` = 1
        ");
        $stmtCount->execute([':uid' => $userId]);
        $activeCount = (int)$stmtCount->fetchColumn();

        if ($activeCount === 0) {
            $stmtUser = $this->db->prepare("UPDATE `users` SET `push_opt_in` = 0 WHERE `id` = :uid");
            $stmtUser->execute([':uid' => $userId]);
        }

        return true;
    }

    /**
     * Ambil seluruh subscription aktif milik pengguna
     */
    public function getActiveSubscriptions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM `push_subscriptions`
            WHERE `user_id` = :uid AND `is_active` = 1
            ORDER BY `id` DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Nonaktifkan subscription yang sudah kedaluwarsa atau tidak valid (HTTP 404/410)
     */
    public function deactivateSubscription(string $endpointHash): void
    {
        $stmt = $this->db->prepare("
            UPDATE `push_subscriptions`
            SET `is_active` = 0, `updated_at` = NOW()
            WHERE `endpoint_hash` = :hash
        ");
        $stmt->execute([':hash' => $endpointHash]);
    }

    /**
     * Kirim notifikasi Web Push ke semua perangkat aktif milik seorang pengguna
     */
    public function sendPushToUser(
        int $userId,
        string $title,
        string $body,
        ?string $link = null,
        array $options = []
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'unconfigured',
                'message' => 'Web Push belum dikonfigurasi dengan VAPID key di .env.',
                'sent_count' => 0
            ];
        }

        $subscriptions = $this->getActiveSubscriptions($userId);
        if (empty($subscriptions)) {
            return [
                'success' => false,
                'status' => 'no_subscriptions',
                'message' => 'Pengguna tidak memiliki perangkat yang terdaftar untuk menerima Web Push.',
                'sent_count' => 0
            ];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $options['icon'] ?? base_url('assets/images/logo.png'),
            'badge' => $options['badge'] ?? base_url('assets/images/badge.png'),
            'data' => [
                'url' => $link ? base_url($link) : base_url('notifications'),
                'timestamp' => time()
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sentCount = 0;
        $failedCount = 0;
        $deactivatedCount = 0;

        foreach ($subscriptions as $sub) {
            $endpoint = $sub['endpoint'];
            $endpointHash = $sub['endpoint_hash'];

            $res = $this->dispatchPushNotification($endpoint, (string)$payload);

            if ($res['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                // Jika status 404 Not Found atau 410 Gone, otomatis nonaktifkan token langganan
                if (in_array($res['http_code'] ?? 0, [404, 410], true)) {
                    $this->deactivateSubscription($endpointHash);
                    $deactivatedCount++;
                }
            }
        }

        return [
            'success' => ($sentCount > 0),
            'status' => ($sentCount > 0) ? 'delivered' : 'failed',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'deactivated_count' => $deactivatedCount
        ];
    }

    /**
     * Dispatch HTTP request push ke endpoint browser push service
     */
    private function dispatchPushNotification(string $endpoint, string $payload): array
    {
        $parsedUrl = parse_url($endpoint);
        $audience = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');

        $vapidHeaders = $this->createVapidHeaders($audience);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                'TTL: 86400',
                'Authorization: ' . $vapidHeaders['Authorization'],
                'Crypto-Key: p256ecdsa=' . $this->vapidPublicKey
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'http_code' => 0,
                'error' => $curlError
            ];
        }

        // 200 OK or 201 Created or 202 Accepted means push service accepted notification
        $isSuccess = ($httpCode >= 200 && $httpCode < 300);

        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }

    /**
     * Buat header otorisasi VAPID JWT (RFC 8292)
     */
    private function createVapidHeaders(string $audience): array
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];

        $claims = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12 jam
            'sub' => $this->vapidSubject
        ];

        $encodedHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $encodedClaims = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');
        $dataToSign = "{$encodedHeader}.{$encodedClaims}";

        // Tanda tangani menggunakan kunci privat EC (secp256r1 / prime256v1)
        $signature = '';
        $rawPriv = base64_decode(strtr($this->vapidPrivateKey, '-_', '+/'));
        
        // Buat private key PEM jika belum berbentuk format PEM
        $pem = "-----BEGIN EC PRIVATE KEY-----\n" . base64_encode($rawPriv) . "\n-----END EC PRIVATE KEY-----";
        $pkey = openssl_pkey_get_private($pem);

        if ($pkey) {
            openssl_sign($dataToSign, $rawSignature, $pkey, OPENSSL_ALGO_SHA256);
            $signature = rtrim(strtr(base64_encode($rawSignature), '+/', '-_'), '=');
        } else {
            // Fallback SHA256 HMAC jika EC PEM belum dimuat
            $rawSignature = hash_hmac('sha256', $dataToSign, $this->vapidPrivateKey, true);
            $signature = rtrim(strtr(base64_encode($rawSignature), '+/', '-_'), '=');
        }

        $jwt = "{$dataToSign}.{$signature}";

        return [
            'Authorization' => "vapid t={$jwt}, k={$this->vapidPublicKey}"
        ];
    }
}
