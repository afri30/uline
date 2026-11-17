<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;
use Throwable;

class WhatsAppOtp extends Model
{
    protected string $table = 'whatsapp_otps';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'phone_number',
        'otp_code',
        'action',
        'is_used',
        'attempts',
        'expires_at',
        'ip_address'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check rate limiting: max 1 OTP request every 60 seconds, max 10 per hour
     */
    public function checkRateLimit(string $phoneNumber, string $ipAddress = ''): array
    {
        $db = $this->getDb();
        $digits = preg_replace('/[^\d]/', '', $phoneNumber);

        // 1. Cooldown check (last 60 seconds using PHP timestamp comparison)
        $stmtCooldown = $db->prepare("
            SELECT `created_at`
            FROM `{$this->table}`
            WHERE (`phone_number` LIKE :phone OR `ip_address` = :ip)
              AND `action` = 'LOGIN'
            ORDER BY `id` DESC
            LIMIT 1
        ");
        $stmtCooldown->execute([
            ':phone' => '%' . substr($digits, -9),
            ':ip' => $ipAddress
        ]);
        $lastRequest = $stmtCooldown->fetch(PDO::FETCH_ASSOC);

        if ($lastRequest) {
            $createdTime = strtotime($lastRequest['created_at']);
            $elapsed = time() - $createdTime;
            if ($elapsed >= 0 && $elapsed < 60) {
                $remaining = 60 - $elapsed;
                return [
                    'allowed' => false,
                    'reason' => 'cooldown',
                    'retry_after' => $remaining,
                    'message' => "Mohon tunggu {$remaining} detik sebelum meminta kode OTP kembali."
                ];
            }
        }

        // 2. Hourly flood check (max 10 OTPs per hour)
        $oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
        $stmtHour = $db->prepare("
            SELECT COUNT(*) AS total
            FROM `{$this->table}`
            WHERE (`phone_number` LIKE :phone OR `ip_address` = :ip)
              AND `created_at` >= :one_hour_ago
        ");
        $stmtHour->execute([
            ':phone' => '%' . substr($digits, -9),
            ':ip' => $ipAddress,
            ':one_hour_ago' => $oneHourAgo
        ]);
        $hourlyCount = (int)$stmtHour->fetchColumn();

        if ($hourlyCount >= 10) {
            return [
                'allowed' => false,
                'reason' => 'hourly_limit',
                'retry_after' => 3600,
                'message' => 'Batas permintaan kode OTP per jam terlampaui. Silakan coba kembali 1 jam lagi.'
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Generate new OTP for user and store in database
     */
    public function generateOtp(int $userId, string $phoneNumber, string $action = 'LOGIN', int $validMinutes = 5, string $ip = ''): array
    {
        $db = $this->getDb();

        // 1. Invalidate previous active OTPs for this user and action
        $stmtInvalidate = $db->prepare("
            UPDATE `{$this->table}`
            SET `is_used` = 1
            WHERE `user_id` = :uid AND `action` = :act AND `is_used` = 0
        ");
        $stmtInvalidate->execute([':uid' => $userId, ':act' => $action]);

        // 2. Generate cryptographically secure 6-digit numeric OTP
        $otpCode = sprintf('%06d', random_int(100000, 999999));

        // 3. Insert new OTP
        // 3. Insert new OTP with consistent PHP timestamp calculation
        $now = time();
        $createdAt = date('Y-m-d H:i:s', $now);
        $expiresAt = date('Y-m-d H:i:s', $now + ($validMinutes * 60));

        $stmtInsert = $db->prepare("
            INSERT INTO `{$this->table}` (
                `user_id`, `phone_number`, `otp_code`, `action`,
                `is_used`, `attempts`, `expires_at`, `ip_address`, `created_at`
            ) VALUES (
                :user_id, :phone_number, :otp_code, :action,
                0, 0, :expires_at, :ip, :created_at
            )
        ");
        $stmtInsert->execute([
            ':user_id' => $userId,
            ':phone_number' => $phoneNumber,
            ':otp_code' => $otpCode,
            ':action' => $action,
            ':expires_at' => $expiresAt,
            ':ip' => $ip,
            ':created_at' => $createdAt
        ]);

        $otpId = (int)$db->lastInsertId();

        return [
            'id' => $otpId,
            'otp_code' => $otpCode,
            'valid_minutes' => $validMinutes,
            'phone_number' => $phoneNumber
        ];
    }

    /**
     * Verify submitted OTP code
     */
    public function verifyOtp(int $userId, string $submittedOtp, string $action = 'LOGIN'): array
    {
        $db = $this->getDb();
        $submittedOtp = trim($submittedOtp);

        // Fetch latest active OTP for user & action
        $stmt = $db->prepare("
            SELECT * FROM `{$this->table}`
            WHERE `user_id` = :uid AND `action` = :act AND `is_used` = 0
            ORDER BY `id` DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId, ':act' => $action]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'valid' => false,
                'message' => 'Tidak ada permintaan kode OTP yang aktif. Silakan minta kode OTP baru.'
            ];
        }

        // Check if expired
        $now = time();
        $expiresAt = strtotime($row['expires_at']);
        if ($now > $expiresAt) {
            // Mark expired
            $db->prepare("UPDATE `{$this->table}` SET `is_used` = 1 WHERE `id` = :id")->execute([':id' => $row['id']]);
            return [
                'valid' => false,
                'message' => 'Kode OTP telah kadaluarsa (melebihi batas waktu 5 menit). Silakan minta kode baru.'
            ];
        }

        // Check max attempts (max 5)
        if ((int)$row['attempts'] >= 5) {
            $db->prepare("UPDATE `{$this->table}` SET `is_used` = 1 WHERE `id` = :id")->execute([':id' => $row['id']]);
            return [
                'valid' => false,
                'message' => 'Kode OTP telah dibatalkan karena terlalu banyak percobaan salah. Silakan minta kode baru.'
            ];
        }

        // Verify OTP value
        if ($row['otp_code'] !== $submittedOtp) {
            $newAttempts = (int)$row['attempts'] + 1;
            $remaining = 5 - $newAttempts;
            $db->prepare("UPDATE `{$this->table}` SET `attempts` = :att WHERE `id` = :id")->execute([
                ':att' => $newAttempts,
                ':id' => $row['id']
            ]);

            return [
                'valid' => false,
                'message' => "Kode OTP yang Anda masukkan salah. Sisa percobaan: {$remaining} kali."
            ];
        }

        // Mark OTP as used
        $db->prepare("UPDATE `{$this->table}` SET `is_used` = 1 WHERE `id` = :id")->execute([':id' => $row['id']]);

        return [
            'valid' => true,
            'user_id' => (int)$row['user_id'],
            'phone_number' => $row['phone_number'],
            'message' => 'Kode OTP berhasil diverifikasi.'
        ];
    }
}
