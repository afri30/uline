<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use Throwable;

class WhatsAppLog extends Model
{
    protected string $table = 'whatsapp_logs';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'phone_number',
        'notification_type',
        'message',
        'provider',
        'provider_response',
        'status',
        'error_message',
        'retry_count',
        'idempotency_key',
        'sent_at'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get paginated logs with optional filters and user information
     */
    public function getLogs(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $db = $this->getDb();
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array(strtoupper($filters['status']), ['PENDING', 'SUCCESS', 'FAILED'])) {
            $where[] = "l.status = :status";
            $params[':status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['notification_type'])) {
            $where[] = "l.notification_type = :ntype";
            $params[':ntype'] = $filters['notification_type'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(l.phone_number LIKE :q OR l.message LIKE :q OR u.name LIKE :q)";
            $params[':q'] = '%' . trim($filters['search']) . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT l.*, u.name AS user_name, u.role AS user_role
            FROM `{$this->table}` l
            LEFT JOIN `users` u ON l.user_id = u.id
            {$whereClause}
            ORDER BY l.id DESC
            LIMIT :offset, :perPage
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total count for pagination
     */
    public function getTotalCount(array $filters = []): int
    {
        $db = $this->getDb();
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array(strtoupper($filters['status']), ['PENDING', 'SUCCESS', 'FAILED'])) {
            $where[] = "l.status = :status";
            $params[':status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['notification_type'])) {
            $where[] = "l.notification_type = :ntype";
            $params[':ntype'] = $filters['notification_type'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(l.phone_number LIKE :q OR l.message LIKE :q OR u.name LIKE :q)";
            $params[':q'] = '%' . trim($filters['search']) . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT COUNT(*)
            FROM `{$this->table}` l
            LEFT JOIN `users` u ON l.user_id = u.id
            {$whereClause}
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get statistics summary for dashboard cards
     */
    public function getStats(): array
    {
        $db = $this->getDb();
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'pending' => 0,
            'today_count' => 0
        ];

        try {
            $stmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN `status` = 'SUCCESS' THEN 1 ELSE 0 END) AS `success`,
                    SUM(CASE WHEN `status` = 'FAILED' THEN 1 ELSE 0 END) AS `failed`,
                    SUM(CASE WHEN `status` = 'PENDING' THEN 1 ELSE 0 END) AS `pending`,
                    SUM(CASE WHEN DATE(`created_at`) = CURDATE() THEN 1 ELSE 0 END) AS `today_count`
                FROM `{$this->table}`
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $stats['total'] = (int)($row['total'] ?? 0);
                $stats['success'] = (int)($row['success'] ?? 0);
                $stats['failed'] = (int)($row['failed'] ?? 0);
                $stats['pending'] = (int)($row['pending'] ?? 0);
                $stats['today_count'] = (int)($row['today_count'] ?? 0);
            }
        } catch (Throwable $e) {
            // Keep default zeros
        }

        return $stats;
    }

    /**
     * Find single log with user details
     */
    public function findWithUser(int $id): ?array
    {
        $db = $this->getDb();
        $stmt = $db->prepare("
            SELECT l.*, u.name AS user_name, u.email AS user_email, u.role AS user_role
            FROM `{$this->table}` l
            LEFT JOIN `users` u ON l.user_id = u.id
            WHERE l.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
