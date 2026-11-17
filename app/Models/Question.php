<?php

namespace App\Models;

use App\Core\Model;

class Question extends Model
{
    protected string $table = 'questions';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id',
        'subject_id',
        'question_text',
        'question_type',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'score',
        'difficulty'
    ];

    /**
     * Get paginated questions owned by specific teacher with filters
     *
     * @param int $teacherId
     * @param array $filters ['search', 'subject_id', 'difficulty', 'type']
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTeacher(int $teacherId, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $where = ["q.`teacher_id` = :teacher_id"];
        $params = [':teacher_id' => $teacherId];

        if (!empty($filters['search'])) {
            $where[] = "q.`question_text` LIKE :search";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['subject_id']) && is_numeric($filters['subject_id'])) {
            $where[] = "q.`subject_id` = :subject_id";
            $params[':subject_id'] = (int)$filters['subject_id'];
        }

        if (!empty($filters['difficulty']) && in_array($filters['difficulty'], ['easy', 'medium', 'hard'], true)) {
            $where[] = "q.`difficulty` = :difficulty";
            $params[':difficulty'] = $filters['difficulty'];
        }

        if (!empty($filters['type']) && in_array($filters['type'], ['multiple_choice', 'true_false', 'essay'], true)) {
            $where[] = "q.`question_type` = :type";
            $params[':type'] = $filters['type'];
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $sql = "SELECT q.*, s.code as subject_code, s.name as subject_name
                FROM `{$this->table}` q
                JOIN `subjects` s ON q.subject_id = s.id
                {$whereSql}
                ORDER BY q.`id` DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->query($sql, $params);
    }

    /**
     * Count questions for teacher with filters
     *
     * @param int $teacherId
     * @param array $filters
     * @return int
     */
    public function countByTeacher(int $teacherId, array $filters = []): int
    {
        $where = ["q.`teacher_id` = :teacher_id"];
        $params = [':teacher_id' => $teacherId];

        if (!empty($filters['search'])) {
            $where[] = "q.`question_text` LIKE :search";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['subject_id']) && is_numeric($filters['subject_id'])) {
            $where[] = "q.`subject_id` = :subject_id";
            $params[':subject_id'] = (int)$filters['subject_id'];
        }

        if (!empty($filters['difficulty']) && in_array($filters['difficulty'], ['easy', 'medium', 'hard'], true)) {
            $where[] = "q.`difficulty` = :difficulty";
            $params[':difficulty'] = $filters['difficulty'];
        }

        if (!empty($filters['type']) && in_array($filters['type'], ['multiple_choice', 'true_false', 'essay'], true)) {
            $where[] = "q.`question_type` = :type";
            $params[':type'] = $filters['type'];
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $sql = "SELECT COUNT(q.id) as total
                FROM `{$this->table}` q
                JOIN `subjects` s ON q.subject_id = s.id
                {$whereSql}";

        $res = $this->query($sql, $params);
        return (int)($res[0]['total'] ?? 0);
    }

    /**
     * Find single question with subject details and enforce teacher ownership
     *
     * @param int $id
     * @param int $teacherId
     * @return array|false
     */
    public function findWithSubjectAndTeacher(int $id, int $teacherId)
    {
        $sql = "SELECT q.*, s.code as subject_code, s.name as subject_name
                FROM `{$this->table}` q
                JOIN `subjects` s ON q.subject_id = s.id
                WHERE q.`id` = :id AND q.`teacher_id` = :teacher_id
                LIMIT 1";

        $res = $this->query($sql, [':id' => $id, ':teacher_id' => $teacherId]);
        return $res[0] ?? false;
    }

    /**
     * Check if a question belongs to a specific teacher
     *
     * @param int $questionId
     * @param int $teacherId
     * @return bool
     */
    public function isOwnedBy(int $questionId, int $teacherId): bool
    {
        $sql = "SELECT id FROM `{$this->table}` WHERE `id` = :id AND `teacher_id` = :teacher_id LIMIT 1";
        $res = $this->query($sql, [':id' => $questionId, ':teacher_id' => $teacherId]);
        return !empty($res);
    }

    /**
     * Get all questions for a teacher, optionally filtered by subject
     */
    public function getAllByTeacherAndSubject(int $teacherId, ?int $subjectId = null): array
    {
        $sql = "SELECT q.*, s.code as subject_code, s.name as subject_name
                FROM `{$this->table}` q
                JOIN `subjects` s ON q.subject_id = s.id
                WHERE q.`teacher_id` = :teacher_id";
        $params = [':teacher_id' => $teacherId];

        if ($subjectId !== null && $subjectId > 0) {
            $sql .= " AND q.`subject_id` = :subject_id";
            $params[':subject_id'] = $subjectId;
        }

        $sql .= " ORDER BY q.`id` DESC";

        return $this->query($sql, $params);
    }
}

