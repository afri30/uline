<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use Throwable;

class WhatsAppSetting extends Model
{
    protected string $table = 'whatsapp_settings';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'setting_key',
        'setting_value',
        'description'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all settings as key-value associative array
     */
    public function getAllSettings(): array
    {
        $db = $this->getDb();
        try {
            $stmt = $db->query("SELECT `setting_key`, `setting_value`, `description` FROM `{$this->table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $row['setting_value'],
                    'description' => $row['description']
                ];
            }
            return $settings;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get specific setting value
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $db = $this->getDb();
        try {
            $stmt = $db->prepare("SELECT `setting_value` FROM `{$this->table}` WHERE `setting_key` = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? (string)$val : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }

    /**
     * Save or update multiple settings at once
     */
    public function updateSettings(array $settings): bool
    {
        $db = $this->getDb();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO `{$this->table}` (`setting_key`, `setting_value`)
                VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = CURRENT_TIMESTAMP
            ");

            foreach ($settings as $k => $v) {
                $stmt->execute([
                    ':k' => (string)$k,
                    ':v' => is_bool($v) ? ($v ? '1' : '0') : (string)$v
                ]);
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }
}
