<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;

class ExamConfig extends Model
{
    protected string $table = 'exam_configs';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'title',
        'duration',
        'random_questions',
        'random_options',
        'start_time',
        'end_time',
        'token',
        'token_secret',
        'is_active'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Get the active or default exam configuration
     *
     * @return array
     */
    public function getActiveConfig(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE `is_active` = 1 ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            $config = $this->findById(1);
        }

        if (!$config) {
            // Default fallback if table is empty
            return [
                'id' => 1,
                'title' => 'Konfigurasi Utama Ujian Online',
                'duration' => 60,
                'random_questions' => 1,
                'random_options' => 1,
                'start_time' => date('Y-m-d H:i:s'),
                'end_time' => date('Y-m-d H:i:s', strtotime('+4 hours')),
                'token' => 'ULINE2026',
                'token_secret' => 'exam_sec_f98c21a084eb73',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        return $config;
    }

    /**
     * Save or update the primary exam configuration
     *
     * @param array $data
     * @return bool
     */
    public function saveConfig(array $data): bool
    {
        $current = $this->getActiveConfig();

        if (isset($current['id'])) {
            return $this->update($current['id'], $data);
        }

        return (bool)$this->create($data);
    }

    /**
     * Verify student token against current active exam configuration
     *
     * @param string $inputToken
     * @return bool
     */
    public function verifyToken(string $inputToken): bool
    {
        $config = $this->getActiveConfig();
        $expectedToken = trim($config['token'] ?? '');
        return !empty($expectedToken) && (strcasecmp(trim($inputToken), $expectedToken) === 0);
    }

    /**
     * Check if exam time window is currently open
     *
     * @return array [isOpen => bool, status => string, message => string]
     */
    public function checkExamWindow(): array
    {
        $config = $this->getActiveConfig();
        $now = time();
        $start = strtotime($config['start_time'] ?? 'now');
        $end = strtotime($config['end_time'] ?? 'now');

        if ($now < $start) {
            return [
                'isOpen' => false,
                'status' => 'UPCOMING',
                'message' => 'Ujian belum dimulai. Dibuka pada ' . date('d M Y H:i', $start)
            ];
        }

        if ($now > $end) {
            return [
                'isOpen' => false,
                'status' => 'EXPIRED',
                'message' => 'Waktu pengerjaan ujian telah berakhir pada ' . date('d M Y H:i', $end)
            ];
        }

        return [
            'isOpen' => true,
            'status' => 'ACTIVE',
            'message' => 'Ujian sedang berlangsung.'
        ];
    }

    /**
     * Generate a secure random token (e.g. ULN84B)
     *
     * @param int $length
     * @return string
     */
    public static function generateToken(int $length = 6): string
    {
        // Avoid confusing characters: 0, O, I, 1
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxIndex = strlen($chars) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= $chars[random_int(0, $maxIndex)];
        }

        return $token;
    }
}
