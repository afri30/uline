<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'reference_type',
        'reference_id',
        'is_read',
        'read_at'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Get count of unread notifications for a user
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->getDb()->prepare("
            SELECT COUNT(*) FROM `{$this->table}` 
            WHERE `user_id` = :uid AND `is_read` = 0
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if a notification already exists for deduplication
     */
    public function existsForUserAndRef(int $userId, string $refType, int $refId): bool
    {
        $stmt = $this->getDb()->prepare("
            SELECT `id` FROM `{$this->table}`
            WHERE `user_id` = :uid AND `reference_type` = :reftype AND `reference_id` = :refid
            LIMIT 1
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':reftype' => $refType,
            ':refid' => $refId
        ]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create notification if not already exists (deduplicated)
     */
    public function createIfNotExists(array $data): ?int
    {
        $userId = (int)($data['user_id'] ?? 0);
        $refType = $data['reference_type'] ?? null;
        $refId = isset($data['reference_id']) ? (int)$data['reference_id'] : null;

        if ($userId <= 0) {
            return null;
        }

        if (!empty($refType) && $refId !== null) {
            if ($this->existsForUserAndRef($userId, $refType, $refId)) {
                return null; // Prevent duplicate
            }
        }

        return $this->create([
            'user_id' => $userId,
            'title' => trim((string)($data['title'] ?? 'Pemberitahuan')),
            'message' => trim((string)($data['message'] ?? '')),
            'type' => (string)($data['type'] ?? 'info'),
            'link' => !empty($data['link']) ? trim((string)$data['link']) : null,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'is_read' => 0,
            'read_at' => null
        ]);
    }

    /**
     * Get recent notifications for navbar dropdown
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM `{$this->table}`
            WHERE `user_id` = :uid
            ORDER BY `id` DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated notifications for listing page
     */
    public function getPaginated(int $userId, int $page = 1, int $perPage = 10, ?string $filter = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $whereClause = "WHERE `user_id` = :uid";
        $params = [':uid' => $userId];

        if ($filter === 'unread') {
            $whereClause .= " AND `is_read` = 0";
        } elseif ($filter === 'read') {
            $whereClause .= " AND `is_read` = 1";
        }

        // Count total
        $countStmt = $this->getDb()->prepare("SELECT COUNT(*) FROM `{$this->table}` {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch records
        $sql = "SELECT * FROM `{$this->table}` {$whereClause} ORDER BY `id` DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->getDb()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = (int)ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    /**
     * Mark single notification as read for a specific user
     */
    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->getDb()->prepare("
            UPDATE `{$this->table}`
            SET `is_read` = 1, `read_at` = NOW()
            WHERE `id` = :id AND `user_id` = :uid
        ");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Mark all notifications as read for a specific user
     */
    public function markAllAsRead(int $userId): int
    {
        $stmt = $this->getDb()->prepare("
            UPDATE `{$this->table}`
            SET `is_read` = 1, `read_at` = NOW()
            WHERE `user_id` = :uid AND `is_read` = 0
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->rowCount();
    }
}
