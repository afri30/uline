<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class AuditLog extends Model
{
    protected string $table = 'audit_logs';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Record a new audit log entry
     */
    public function log(
        string $action,
        ?string $description = null,
        ?int $userId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): int {
        $ip = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ua = $userAgent ?? substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System', 0, 255);
        $uid = $userId ?? (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);

        $sql = "INSERT INTO `{$this->table}` (`user_id`, `action`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) 
                VALUES (:user_id, :action, :description, :ip_address, :user_agent, :metadata, NOW())";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([
            ':user_id' => $uid,
            ':action' => strtoupper(trim($action)),
            ':description' => $description,
            ':ip_address' => $ip,
            ':user_agent' => $ua,
            ':metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null
        ]);

        return (int)$this->getDb()->lastInsertId();
    }

    /**
     * Get recent audit logs with user information
     */
    public function getRecent(int $limit = 50, ?string $action = null): array
    {
        $sql = "SELECT al.*, u.name as user_name, u.username, u.role as user_role
                FROM `{$this->table}` al
                LEFT JOIN `users` u ON al.user_id = u.id";

        $params = [];
        if ($action) {
            $sql .= " WHERE al.action = :action";
            $params[':action'] = strtoupper(trim($action));
        }

        $sql .= " ORDER BY al.id DESC LIMIT " . (int)$limit;

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if ($row['metadata']) {
                $row['meta'] = json_decode($row['metadata'], true);
            } else {
                $row['meta'] = [];
            }
        }

        return $rows;
    }

    /**
     * Get audit logs for a specific user
     */
    public function getByUserId(int $userId, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name, u.username, u.role as user_role
                FROM `{$this->table}` al
                LEFT JOIN `users` u ON al.user_id = u.id
                WHERE al.user_id = :user_id
                ORDER BY al.id DESC LIMIT " . (int)$limit;

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if ($row['metadata']) {
                $row['meta'] = json_decode($row['metadata'], true);
            } else {
                $row['meta'] = [];
            }
        }

        return $rows;
    }
}
