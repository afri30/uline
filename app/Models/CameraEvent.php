<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class CameraEvent extends Model
{
    protected string $table = 'camera_events';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Log a WebRTC camera event for an exam session
     */
    public function logEvent(int $sessionId, string $cameraType, string $eventType, ?array $metadata = null): int
    {
        $now = date('Y-m-d H:i:s');
        $jsonMetadata = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $data = [
            'exam_session_id' => $sessionId,
            'camera_type' => $cameraType,
            'event_type' => $eventType,
            'timestamp' => $now,
            'metadata' => $jsonMetadata
        ];

        return (int)$this->create($data);
    }

    /**
     * Get all camera events for an exam session
     */
    public function getBySessionId(int $sessionId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE exam_session_id = :session_id 
                ORDER BY id ASC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if ($row['metadata']) {
                $row['metadata_parsed'] = json_decode($row['metadata'], true);
            } else {
                $row['metadata_parsed'] = null;
            }
        }

        return $rows;
    }

    /**
     * Get disconnection event count for an exam session
     */
    public function getDisconnectionCount(int $sessionId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE exam_session_id = :session_id AND event_type = 'disconnected'";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get the latest status/event for each camera type in a session
     */
    public function getLatestStatusByCamera(int $sessionId): array
    {
        $sql = "SELECT ce.* FROM {$this->table} ce
                INNER JOIN (
                    SELECT camera_type, MAX(id) as max_id
                    FROM {$this->table}
                    WHERE exam_session_id = :session_id
                      AND event_type != 'verification_snapshot'
                    GROUP BY camera_type
                ) latest ON ce.id = latest.max_id";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $status = [];
        foreach ($rows as $r) {
            $status[$r['camera_type']] = $r;
        }

        return $status;
    }
}
