<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ProctorEvent extends Model
{
    protected string $table = 'proctor_events';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Log a proctoring event
     */
    public function logEvent(
        int $sessionId,
        string $eventType,
        string $severity = 'INFO',
        float $confidence = 1.0,
        string $cameraType = 'camera_1',
        ?array $metadata = null,
        ?string $evidencePath = null
    ): int {
        $now = date('Y-m-d H:i:s');
        $validSeverities = ['INFO', 'WARNING', 'HIGH'];
        $severity = strtoupper(trim($severity));
        if (!in_array($severity, $validSeverities, true)) {
            $severity = 'INFO';
        }

        // Clamp confidence between 0.0 and 1.0 (if passed as 0-100, convert to decimal)
        if ($confidence > 1.0 && $confidence <= 100.0) {
            $confidence = round($confidence / 100.0, 4);
        }
        $confidence = max(0.0, min(1.0, (float)$confidence));

        $jsonMetadata = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $data = [
            'exam_session_id' => $sessionId,
            'camera_type' => $cameraType,
            'event_type' => $eventType,
            'confidence' => $confidence,
            'severity' => $severity,
            'timestamp' => $now,
            'evidence_path' => $evidencePath,
            'metadata' => $jsonMetadata,
            'reviewed' => 0,
            'review_note' => null
        ];

        return (int)$this->create($data);
    }

    /**
     * Get all proctor events for a specific exam session
     */
    public function getBySessionId(int $sessionId, ?string $severityFilter = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE exam_session_id = :session_id";
        $params = [':session_id' => $sessionId];

        if (!empty($severityFilter)) {
            $sql .= " AND severity = :severity";
            $params[':severity'] = strtoupper($severityFilter);
        }

        $sql .= " ORDER BY timestamp ASC, id ASC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['confidence_pct'] = round((float)$row['confidence'] * 100, 1);
            $row['metadata_parsed'] = $row['metadata'] ? json_decode($row['metadata'], true) : null;
        }

        return $rows;
    }

    /**
     * Update review status and notes by teacher
     */
    public function updateReview(int $eventId, bool $reviewed, ?string $reviewNote = null, string $reviewStatus = 'reviewed'): bool
    {
        $validStatuses = ['pending', 'reviewed', 'confirmed', 'dismissed'];
        $status = strtolower(trim($reviewStatus));
        if (!in_array($status, $validStatuses, true)) {
            $status = $reviewed ? 'reviewed' : 'pending';
        }

        return (bool)$this->update($eventId, [
            'reviewed' => ($status !== 'pending') ? 1 : 0,
            'review_status' => $status,
            'review_note' => $reviewNote
        ]);
    }

    /**
     * Bulk review all events for a session
     */
    public function reviewAllForSession(int $sessionId, ?string $globalNote = null, string $reviewStatus = 'reviewed'): bool
    {
        $validStatuses = ['pending', 'reviewed', 'confirmed', 'dismissed'];
        $status = strtolower(trim($reviewStatus));
        if (!in_array($status, $validStatuses, true)) {
            $status = 'reviewed';
        }
        $isReviewed = ($status !== 'pending') ? 1 : 0;

        $params = [
            ':session_id' => $sessionId,
            ':reviewed' => $isReviewed,
            ':review_status' => $status
        ];

        if ($globalNote !== null && trim($globalNote) !== '') {
            $sql = "UPDATE {$this->table} 
                    SET reviewed = :reviewed,
                        review_status = :review_status,
                        review_note = :note
                    WHERE exam_session_id = :session_id";
            $params[':note'] = trim($globalNote);
        } else {
            $sql = "UPDATE {$this->table} 
                    SET reviewed = :reviewed,
                        review_status = :review_status
                    WHERE exam_session_id = :session_id";
        }

        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get event count statistics for a session
     */
    public function getEventStatsBySession(int $sessionId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN severity = 'INFO' THEN 1 ELSE 0 END) as info_count,
                    SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) as warning_count,
                    SUM(CASE WHEN severity = 'HIGH' THEN 1 ELSE 0 END) as high_count,
                    SUM(CASE WHEN reviewed = 1 THEN 1 ELSE 0 END) as reviewed_count
                FROM {$this->table} 
                WHERE exam_session_id = :session_id";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_events' => (int)($res['total_events'] ?? 0),
            'info_count' => (int)($res['info_count'] ?? 0),
            'warning_count' => (int)($res['warning_count'] ?? 0),
            'high_count' => (int)($res['high_count'] ?? 0),
            'reviewed_count' => (int)($res['reviewed_count'] ?? 0),
            'unreviewed_count' => (int)($res['total_events'] ?? 0) - (int)($res['reviewed_count'] ?? 0)
        ];
    }

    /**
     * Count unreviewed events for an exam across all its sessions
     */
    public function countUnreviewedByExamId(int $examId): int
    {
        $sql = "SELECT COUNT(pe.id) as unrev 
                FROM {$this->table} pe 
                JOIN exam_sessions es ON pe.exam_session_id = es.id 
                WHERE es.exam_id = :eid AND pe.reviewed = 0";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':eid' => $examId]);
        return (int)$stmt->fetchColumn();
    }
}
