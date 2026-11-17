<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Exam extends Model
{
    protected string $table = 'exams';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Get exams belonging to a specific teacher with filters, joins, and pagination
     */
    public function getByTeacherId(int $teacherId, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT e.*, 
                       s.name AS subject_name, 
                       s.code AS subject_code,
                       c.name AS class_name, 
                       c.grade AS class_grade,
                       COUNT(eq.id) AS total_questions,
                       COALESCE(SUM(q.score), 0) AS total_score
                FROM {$this->table} e
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                LEFT JOIN questions q ON eq.question_id = q.id
                WHERE e.teacher_id = :teacher_id";

        $params = [':teacher_id' => $teacherId];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.title LIKE :search OR e.description LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['subject_id'])) {
            $sql .= " AND e.subject_id = :subject_id";
            $params[':subject_id'] = (int)$filters['subject_id'];
        }

        if (!empty($filters['class_id'])) {
            $sql .= " AND e.class_id = :class_id";
            $params[':class_id'] = (int)$filters['class_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " GROUP BY e.id ORDER BY e.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->getDb()->prepare($sql);
        foreach ($params as $key => $val) {
            if (is_int($val)) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count exams by teacher with filters
     */
    public function countByTeacherId(int $teacherId, array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT e.id) AS total
                FROM {$this->table} e
                WHERE e.teacher_id = :teacher_id";

        $params = [':teacher_id' => $teacherId];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.title LIKE :search OR e.description LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['subject_id'])) {
            $sql .= " AND e.subject_id = :subject_id";
            $params[':subject_id'] = (int)$filters['subject_id'];
        }

        if (!empty($filters['class_id'])) {
            $sql .= " AND e.class_id = :class_id";
            $params[':class_id'] = (int)$filters['class_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($res['total'] ?? 0);
    }

    /**
     * Get single exam with full joined details
     */
    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT e.*, 
                       s.name AS subject_name, 
                       s.code AS subject_code,
                       c.name AS class_name, 
                       c.grade AS class_grade,
                       c.academic_year AS class_academic_year,
                       t.nip AS teacher_nip,
                       u.name AS teacher_name,
                       COUNT(eq.id) AS total_questions,
                       COALESCE(SUM(q.score), 0) AS total_score
                FROM {$this->table} e
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                LEFT JOIN questions q ON eq.question_id = q.id
                WHERE e.id = :id
                GROUP BY e.id
                LIMIT 1";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res ?: null;
    }

    /**
     * Check if exam belongs to specific teacher
     */
    public function isOwnedBy(int $examId, int $teacherId): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE id = :id AND teacher_id = :teacher_id LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $examId, ':teacher_id' => $teacherId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create an exam with its assigned questions in a single transaction
     */
    public function createWithQuestions(array $examData, array $questionIds): int
    {
        $db = $this->getDb();
        $db->beginTransaction();
        try {
            $examId = (int)$this->create($examData);
            if (!$examId) {
                throw new \Exception("Gagal menyimpan data ujian utama.");
            }

            $order = 1;
            $eqSql = "INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (:exam_id, :question_id, :question_order)";
            $eqStmt = $db->prepare($eqSql);

            foreach ($questionIds as $qId) {
                $eqStmt->execute([
                    ':exam_id' => $examId,
                    ':question_id' => (int)$qId,
                    ':question_order' => $order++
                ]);
            }

            $db->commit();
            return $examId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update an exam and sync its assigned questions in a transaction
     */
    public function updateWithQuestions(int $examId, array $examData, array $questionIds): bool
    {
        $db = $this->getDb();
        $db->beginTransaction();
        try {
            $this->update($examId, $examData);

            // Delete existing relations
            $delSql = "DELETE FROM exam_questions WHERE exam_id = :exam_id";
            $delStmt = $db->prepare($delSql);
            $delStmt->execute([':exam_id' => $examId]);

            // Insert new questions
            $order = 1;
            $eqSql = "INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (:exam_id, :question_id, :question_order)";
            $eqStmt = $db->prepare($eqSql);

            foreach ($questionIds as $qId) {
                $eqStmt->execute([
                    ':exam_id' => $examId,
                    ':question_id' => (int)$qId,
                    ':question_order' => $order++
                ]);
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Delete an exam
     */
    public function deleteExam(int $examId): bool
    {
        return (bool)$this->delete($examId);
    }

    /**
     * Toggle or update exam status (draft, published, active, finished)
     */
    public function updateStatus(int $examId, string $status): bool
    {
        $validStatuses = ['draft', 'published', 'active', 'finished'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        return (bool)$this->update($examId, ['status' => $status]);
    }

    /**
     * Get all questions for an exam in assigned order
     */
    public function getExamQuestions(int $examId): array
    {
        $sql = "SELECT eq.id AS exam_question_id,
                       eq.question_order,
                       q.*,
                       s.name AS subject_name,
                       s.code AS subject_code
                FROM exam_questions eq
                JOIN questions q ON eq.question_id = q.id
                JOIN subjects s ON q.subject_id = s.id
                WHERE eq.exam_id = :exam_id
                ORDER BY eq.question_order ASC, eq.id ASC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':exam_id' => $examId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get simple array of question IDs assigned to an exam
     */
    public function getQuestionIds(int $examId): array
    {
        $sql = "SELECT question_id FROM exam_questions WHERE exam_id = :exam_id ORDER BY question_order ASC";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':exam_id' => $examId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Summary stats for a teacher's exams
     */
    public function getTeacherStats(int $teacherId): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS total_draft,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS total_published,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS total_active,
                    SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) AS total_finished
                FROM {$this->table}
                WHERE teacher_id = :teacher_id";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':teacher_id' => $teacherId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int)($row['total'] ?? 0),
            'draft' => (int)($row['total_draft'] ?? 0),
            'published' => (int)($row['total_published'] ?? 0),
            'active' => (int)($row['total_active'] ?? 0),
            'finished' => (int)($row['total_finished'] ?? 0),
        ];
    }

    /**
     * Get list of exams available for a student based on class_id
     */
    public function getAvailableForStudentClass(int $classId, int $studentId): array
    {
        $sql = "SELECT e.*, 
                       s.name AS subject_name, 
                       s.code AS subject_code,
                       c.name AS class_name,
                       u_t.name AS teacher_name,
                       COUNT(eq.id) AS total_questions,
                       COALESCE(SUM(q.score), 0) AS total_score,
                       es.id AS session_id,
                       es.status AS session_status,
                       es.started_at AS session_started_at,
                       es.finished_at AS session_finished_at,
                       COALESCE(SUM(a.score), 0) AS earned_score
                FROM {$this->table} e
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u_t ON t.user_id = u_t.id
                LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                LEFT JOIN questions q ON eq.question_id = q.id
                LEFT JOIN exam_sessions es ON e.id = es.exam_id AND es.student_id = :student_id
                LEFT JOIN answers a ON es.id = a.exam_session_id AND q.id = a.question_id
                WHERE e.class_id = :class_id AND e.status IN ('published', 'active')
                GROUP BY e.id
                ORDER BY e.start_at ASC, e.id DESC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':class_id' => $classId, ':student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

