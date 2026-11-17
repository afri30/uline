<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Answer extends Model
{
    protected string $table = 'answers';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Save or update answer for a specific question in a session
     */
    public function saveOrUpdateAnswer(int $sessionId, int $questionId, string $answerValue, array $questionData): array
    {
        $answerValue = trim($answerValue);
        $score = 0;

        // Auto-scoring logic for Objective questions
        $type = $questionData['question_type'] ?? 'multiple_choice';
        $correctAnswer = trim($questionData['correct_answer'] ?? '');
        $maxScore = (int)($questionData['score'] ?? 10);

        if ($type === 'multiple_choice' || $type === 'true_false') {
            if (strcasecmp($answerValue, $correctAnswer) === 0) {
                $score = $maxScore;
            }
        } elseif ($type === 'essay') {
            // Essay initial score is 0, evaluated by teacher
            $score = 0;
        }

        $now = date('Y-m-d H:i:s');

        // Check if answer already exists
        $checkSql = "SELECT id FROM {$this->table} WHERE exam_session_id = :session_id AND question_id = :question_id LIMIT 1";
        $checkStmt = $this->getDb()->prepare($checkSql);
        $checkStmt->execute([':session_id' => $sessionId, ':question_id' => $questionId]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $updateSql = "UPDATE {$this->table} 
                          SET answer = :answer, score = :score, answered_at = :now 
                          WHERE id = :id";
            $updateStmt = $this->getDb()->prepare($updateSql);
            $updateStmt->execute([
                ':answer' => $answerValue,
                ':score' => $score,
                ':now' => $now,
                ':id' => $existingId
            ]);
            $answerId = (int)$existingId;
        } else {
            $insertSql = "INSERT INTO {$this->table} (exam_session_id, question_id, answer, score, answered_at) 
                          VALUES (:session_id, :question_id, :answer, :score, :now)";
            $insertStmt = $this->getDb()->prepare($insertSql);
            $insertStmt->execute([
                ':session_id' => $sessionId,
                ':question_id' => $questionId,
                ':answer' => $answerValue,
                ':score' => $score,
                ':now' => $now
            ]);
            $answerId = (int)$this->getDb()->lastInsertId();
        }

        return [
            'id' => $answerId,
            'exam_session_id' => $sessionId,
            'question_id' => $questionId,
            'answer' => $answerValue,
            'score' => $score
        ];
    }

    /**
     * Get answers map for a session indexed by question_id
     */
    public function getAnswersMapBySessionId(int $sessionId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE exam_session_id = :session_id";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['question_id']] = $r;
        }

        return $map;
    }
}
