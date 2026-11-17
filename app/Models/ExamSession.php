<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ExamSession extends Model
{
    protected string $table = 'exam_sessions';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->getDb();
    }

    /**
     * Find existing session by exam and student
     */
    public function findByExamAndStudent(int $examId, int $studentId): ?array
    {
        $sql = "SELECT es.*, e.duration, e.title as exam_title, e.status as exam_status
                FROM {$this->table} es
                JOIN exams e ON es.exam_id = e.id
                WHERE es.exam_id = :exam_id AND es.student_id = :student_id
                LIMIT 1";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':exam_id' => $examId, ':student_id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Get or create a session for an exam and student
     */
    public function getOrCreateSession(int $examId, int $studentId, int $durationMinutes): array
    {
        $existing = $this->findByExamAndStudent($examId, $studentId);

        if ($existing) {
            // Recalculate remaining seconds
            $remaining = $this->calculateRemainingSeconds($existing, (int)$durationMinutes);
            if ($remaining <= 0 && $existing['status'] === 'started') {
                $this->finishSession((int)$existing['id'], 'expired');
                $existing['status'] = 'expired';
                $existing['remaining_seconds'] = 0;
            } else {
                $existing['remaining_seconds'] = $remaining;
            }
            return $existing;
        }

        // Create new session
        $now = date('Y-m-d H:i:s');
        $initialRemaining = $durationMinutes * 60;

        $sessionData = [
            'exam_id' => $examId,
            'student_id' => $studentId,
            'started_at' => $now,
            'finished_at' => null,
            'status' => 'started',
            'remaining_seconds' => $initialRemaining
        ];

        $sessionId = (int)$this->create($sessionData);
        $sessionData['id'] = $sessionId;

        return $sessionData;
    }

    /**
     * Server-side calculation of remaining time in seconds based on started_at and duration
     */
    public function calculateRemainingSeconds(array $session, int $durationMinutes): int
    {
        if ($session['status'] === 'finished' || $session['status'] === 'expired') {
            return 0;
        }

        $startedTimestamp = strtotime($session['started_at']);
        $endTimestamp = $startedTimestamp + ($durationMinutes * 60);
        $currentTimestamp = time();

        $remaining = $endTimestamp - $currentTimestamp;
        return max(0, $remaining);
    }

    /**
     * Finish session (by student submission or auto-expiry)
     */
    public function finishSession(int $sessionId, string $status = 'finished'): bool
    {
        $validStatuses = ['finished', 'expired'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'finished';
        }

        $now = date('Y-m-d H:i:s');
        return (bool)$this->update($sessionId, [
            'status' => $status,
            'finished_at' => $now,
            'remaining_seconds' => 0,
            'last_activity_at' => $now
        ]);
    }

    /**
     * Update heartbeat / last active timestamp of a session
     */
    public function touchActivity(int $sessionId): bool
    {
        $stmt = $this->getDb()->prepare("UPDATE `{$this->table}` SET `last_activity_at` = NOW() WHERE `id` = :id");
        return $stmt->execute([':id' => $sessionId]);
    }

    /**
     * Get session with full details (exam, student, subject, teacher, class)
     */
    public function getSessionWithDetails(int $sessionId): ?array
    {
        $sql = "SELECT es.*, 
                       e.title as exam_title,
                       e.description as exam_description,
                       e.duration as exam_duration,
                       e.start_at as exam_start_at,
                       e.end_at as exam_end_at,
                       e.random_questions,
                       e.random_options,
                       e.ai_proctoring,
                       e.dual_camera,
                       s.id as subject_id,
                       s.name as subject_name,
                       s.code as subject_code,
                       u_t.name as teacher_name,
                       st.nis as student_nis,
                       st.class_id,
                       c.name as class_name,
                       u_s.name as student_name,
                       u_s.email as student_email
                FROM {$this->table} es
                JOIN exams e ON es.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u_t ON t.user_id = u_t.id
                JOIN students st ON es.student_id = st.id
                JOIN users u_s ON st.user_id = u_s.id
                JOIN classes c ON st.class_id = c.id
                WHERE es.id = :id
                LIMIT 1";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Calculate score and summary statistics for a session
     */
    public function calculateSessionScore(int $sessionId): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT eq.question_id) as total_questions,
                    COALESCE(SUM(q.score), 0) as max_score,
                    COUNT(DISTINCT a.question_id) as answered_questions,
                    COALESCE(SUM(a.score), 0) as earned_score,
                    SUM(CASE WHEN q.question_type = 'multiple_choice' AND UPPER(a.answer) = UPPER(q.correct_answer) THEN 1 
                             WHEN q.question_type = 'true_false' AND UPPER(a.answer) = UPPER(q.correct_answer) THEN 1 
                             ELSE 0 END) as correct_answers,
                    SUM(CASE WHEN (q.question_type IN ('multiple_choice', 'true_false')) AND (a.answer IS NOT NULL AND UPPER(a.answer) != UPPER(q.correct_answer)) THEN 1 
                             ELSE 0 END) as incorrect_answers,
                    SUM(CASE WHEN q.question_type = 'essay' THEN 1 ELSE 0 END) as essay_count
                FROM exam_sessions es
                JOIN exams e ON es.exam_id = e.id
                JOIN exam_questions eq ON e.id = eq.exam_id
                JOIN questions q ON eq.question_id = q.id
                LEFT JOIN answers a ON es.id = a.exam_session_id AND q.id = a.question_id
                WHERE es.id = :id";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $sessionId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalQuestions = (int)($res['total_questions'] ?? 0);
        $maxScore = (int)($res['max_score'] ?? 0);
        $answered = (int)($res['answered_questions'] ?? 0);
        $earned = (int)($res['earned_score'] ?? 0);
        $correct = (int)($res['correct_answers'] ?? 0);
        $incorrect = (int)($res['incorrect_answers'] ?? 0);
        $essayCount = (int)($res['essay_count'] ?? 0);

        $percentage = $maxScore > 0 ? round(($earned / $maxScore) * 100, 1) : 0;

        return [
            'total_questions' => $totalQuestions,
            'max_score' => $maxScore,
            'answered_questions' => $answered,
            'unanswered_questions' => max(0, $totalQuestions - $answered),
            'earned_score' => $earned,
            'correct_answers' => $correct,
            'incorrect_answers' => $incorrect,
            'essay_count' => $essayCount,
            'percentage' => $percentage
        ];
    }

    /**
     * Get list of teacher exams with aggregate exam result statistics
     */
    public function getTeacherExamsWithResultSummary(int $teacherId, array $filters = []): array
    {
        $sql = "SELECT e.id as exam_id,
                       e.title,
                       e.duration,
                       e.start_at,
                       e.end_at,
                       e.status as exam_status,
                       e.ai_proctoring,
                       s.id as subject_id,
                       s.name as subject_name,
                       s.code as subject_code,
                       c.id as class_id,
                       c.name as class_name,
                       (SELECT COUNT(eq_sub.question_id) FROM exam_questions eq_sub WHERE eq_sub.exam_id = e.id) as total_questions,
                       (SELECT COALESCE(SUM(q_sub.score), 0) FROM exam_questions eq_sub JOIN questions q_sub ON eq_sub.question_id = q_sub.id WHERE eq_sub.exam_id = e.id) as max_exam_score,
                       (SELECT COUNT(DISTINCT st_sub.id) FROM students st_sub WHERE st_sub.class_id = e.class_id) as total_students_in_class,
                       (SELECT COUNT(es_sub.id) FROM exam_sessions es_sub WHERE es_sub.exam_id = e.id) as total_participants,
                       (SELECT COUNT(es_sub.id) FROM exam_sessions es_sub WHERE es_sub.exam_id = e.id AND es_sub.status IN ('finished', 'expired')) as finished_participants
                FROM exams e
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                WHERE e.teacher_id = :teacher_id";

        $params = [':teacher_id' => $teacherId];

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

        if (!empty($filters['search'])) {
            $sql .= " AND (e.title LIKE :search OR s.name LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " GROUP BY e.id ORDER BY e.id DESC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate average, min, max scores for each exam
        foreach ($exams as &$exam) {
            $stats = $this->getExamStatsSummary((int)$exam['exam_id']);
            $exam['avg_score'] = $stats['avg_score'];
            $exam['avg_grade'] = $stats['avg_grade'];
            $exam['max_score_earned'] = $stats['max_score_earned'];
            $exam['min_score_earned'] = $stats['min_score_earned'];
            $exam['highest_grade'] = $stats['highest_grade'];
            $exam['lowest_grade'] = $stats['lowest_grade'];
        }

        return $exams;
    }

    /**
     * Get aggregate statistics for an exam
     */
    public function getExamStatsSummary(int $examId): array
    {
        $participants = $this->getExamParticipants($examId);
        $totalParticipants = count($participants);

        if ($totalParticipants === 0) {
            return [
                'total_participants' => 0,
                'finished_count' => 0,
                'started_count' => 0,
                'avg_score' => 0,
                'avg_grade' => 0,
                'max_score_earned' => 0,
                'min_score_earned' => 0,
                'highest_grade' => 0,
                'lowest_grade' => 0,
                'pass_count' => 0,
                'pass_percentage' => 0
            ];
        }

        $grades = [];
        $scores = [];
        $finishedCount = 0;
        $startedCount = 0;
        $passCount = 0; // Grade >= 70

        foreach ($participants as $p) {
            if ($p['session_status'] === 'finished' || $p['session_status'] === 'expired') {
                $finishedCount++;
            } else {
                $startedCount++;
            }
            $grades[] = $p['grade'];
            $scores[] = $p['earned_score'];
            if ($p['grade'] >= 70) {
                $passCount++;
            }
        }

        $avgGrade = count($grades) > 0 ? round(array_sum($grades) / count($grades), 1) : 0;
        $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
        $maxGrade = count($grades) > 0 ? max($grades) : 0;
        $minGrade = count($grades) > 0 ? min($grades) : 0;
        $maxScore = count($scores) > 0 ? max($scores) : 0;
        $minScore = count($scores) > 0 ? min($scores) : 0;
        $passPercentage = $totalParticipants > 0 ? round(($passCount / $totalParticipants) * 100, 1) : 0;

        return [
            'total_participants' => $totalParticipants,
            'finished_count' => $finishedCount,
            'started_count' => $startedCount,
            'avg_score' => $avgScore,
            'avg_grade' => $avgGrade,
            'max_score_earned' => $maxScore,
            'min_score_earned' => $minScore,
            'highest_grade' => $maxGrade,
            'lowest_grade' => $minGrade,
            'pass_count' => $passCount,
            'pass_percentage' => $passPercentage
        ];
    }

    /**
     * Get list of participants and their scores/rankings for a specific exam
     */
    public function getExamParticipants(int $examId, array $filters = [], string $sort = 'rank_asc'): array
    {
        // First get total max score and question count of the exam
        $qSql = "SELECT COUNT(eq.question_id) as total_questions, COALESCE(SUM(q.score), 0) as max_score
                 FROM exam_questions eq
                 JOIN questions q ON eq.question_id = q.id
                 WHERE eq.exam_id = :exam_id";
        $qStmt = $this->getDb()->prepare($qSql);
        $qStmt->execute([':exam_id' => $examId]);
        $examMeta = $qStmt->fetch(PDO::FETCH_ASSOC);

        $totalQuestions = (int)($examMeta['total_questions'] ?? 0);
        $maxExamScore = (int)($examMeta['max_score'] ?? 0);

        // Fetch participants with session and answers aggregate
        $sql = "SELECT es.id as session_id,
                       es.exam_id,
                       es.student_id,
                       es.started_at,
                       es.finished_at,
                       es.status as session_status,
                       es.remaining_seconds,
                       es.last_activity_at,
                       st.nis as student_nis,
                       u.name as student_name,
                       u.email as student_email,
                       c.name as class_name,
                       COUNT(DISTINCT a.question_id) as answered_count,
                       COALESCE(SUM(a.score), 0) as earned_score,
                       SUM(CASE WHEN q.question_type IN ('multiple_choice', 'true_false') AND UPPER(a.answer) = UPPER(q.correct_answer) THEN 1 ELSE 0 END) as correct_count,
                       SUM(CASE WHEN q.question_type IN ('multiple_choice', 'true_false') AND a.answer IS NOT NULL AND UPPER(a.answer) != UPPER(q.correct_answer) THEN 1 ELSE 0 END) as incorrect_count
                FROM exam_sessions es
                JOIN students st ON es.student_id = st.id
                JOIN users u ON st.user_id = u.id
                JOIN classes c ON st.class_id = c.id
                LEFT JOIN answers a ON es.id = a.exam_session_id
                LEFT JOIN questions q ON a.question_id = q.id
                WHERE es.exam_id = :exam_id";

        $params = [':exam_id' => $examId];

        if (!empty($filters['status'])) {
            $sql .= " AND es.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE :search OR st.nis LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " GROUP BY es.id";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $participants = [];
        foreach ($rows as $row) {
            $earned = (int)$row['earned_score'];
            $answered = (int)$row['answered_count'];
            $correct = (int)$row['correct_count'];
            $incorrect = (int)$row['incorrect_count'];
            $unanswered = max(0, $totalQuestions - $answered);

            // Grade on 0-100 scale
            $grade = $maxExamScore > 0 ? round(($earned / $maxExamScore) * 100, 1) : 0;
            $percentage = $grade;

            // Duration spent in seconds
            $timeSpentSeconds = 0;
            if ($row['started_at']) {
                $end = $row['finished_at'] ? strtotime($row['finished_at']) : time();
                $timeSpentSeconds = max(0, $end - strtotime($row['started_at']));
            }

            $participants[] = [
                'session_id' => (int)$row['session_id'],
                'exam_id' => (int)$row['exam_id'],
                'student_id' => (int)$row['student_id'],
                'student_nis' => $row['student_nis'],
                'student_name' => $row['student_name'],
                'student_email' => $row['student_email'],
                'class_name' => $row['class_name'],
                'started_at' => $row['started_at'],
                'finished_at' => $row['finished_at'],
                'session_status' => $row['session_status'],
                'remaining_seconds' => (int)($row['remaining_seconds'] ?? 0),
                'last_activity_at' => $row['last_activity_at'],
                'total_questions' => $totalQuestions,
                'max_score' => $maxExamScore,
                'answered_count' => $answered,
                'correct_count' => $correct,
                'incorrect_count' => $incorrect,
                'unanswered_count' => $unanswered,
                'earned_score' => $earned,
                'grade' => $grade,
                'percentage' => $percentage,
                'time_spent_seconds' => $timeSpentSeconds
            ];
        }

        // Sort primarily by score DESC to assign absolute rankings
        usort($participants, function ($a, $b) {
            if ($b['grade'] == $a['grade']) {
                // Secondary sort: faster time spent ranks higher
                return $a['time_spent_seconds'] <=> $b['time_spent_seconds'];
            }
            return ($b['grade'] < $a['grade']) ? -1 : 1;
        });

        // Assign rankings
        $rank = 1;
        foreach ($participants as &$p) {
            $p['rank'] = $rank++;
        }
        unset($p);

        // Apply custom user sort if requested
        if ($sort === 'score_asc') {
            usort($participants, fn($a, $b) => $a['grade'] <=> $b['grade']);
        } elseif ($sort === 'name_asc') {
            usort($participants, fn($a, $b) => strcasecmp($a['student_name'], $b['student_name']));
        } elseif ($sort === 'name_desc') {
            usort($participants, fn($a, $b) => strcasecmp($b['student_name'], $a['student_name']));
        } elseif ($sort === 'time_desc') {
            usort($participants, fn($a, $b) => strcmp($b['finished_at'] ?? '', $a['finished_at'] ?? ''));
        }
        // Default is rank_asc / score_desc, which is already in place.

        return $participants;
    }

    /**
     * Get detailed student answer breakdown for a given exam and student
     */
    public function getStudentExamAnswerBreakdown(int $examId, int $studentId): ?array
    {
        $session = $this->findByExamAndStudent($examId, $studentId);
        if (!$session) {
            return null;
        }

        $sessionDetails = $this->getSessionWithDetails((int)$session['id']);
        if (!$sessionDetails) {
            return null;
        }

        // Fetch all questions for this exam
        $sql = "SELECT q.id,
                       q.question_text,
                       q.question_type,
                       q.option_a,
                       q.option_b,
                       q.option_c,
                       q.option_d,
                       q.correct_answer,
                       q.score as max_score,
                       q.difficulty,
                       eq.question_order,
                       a.id as answer_id,
                       a.answer as student_answer,
                       a.score as earned_score,
                       a.answered_at
                FROM exam_questions eq
                JOIN questions q ON eq.question_id = q.id
                LEFT JOIN answers a ON a.exam_session_id = :session_id AND a.question_id = q.id
                WHERE eq.exam_id = :exam_id
                ORDER BY eq.question_order ASC, q.id ASC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([
            ':exam_id' => $examId,
            ':session_id' => $session['id']
        ]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalQuestions = count($questions);
        $maxTotalScore = 0;
        $earnedTotalScore = 0;
        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;

        foreach ($questions as &$q) {
            $q['max_score'] = (int)$q['max_score'];
            $maxTotalScore += $q['max_score'];

            $studentAnswer = $q['student_answer'];
            $hasAnswered = ($studentAnswer !== null && trim($studentAnswer) !== '');

            $isCorrect = false;
            if ($hasAnswered) {
                if ($q['question_type'] === 'essay') {
                    // For essay, if earned_score is set
                    $isCorrect = ((int)($q['earned_score'] ?? 0) > 0);
                } else {
                    $isCorrect = (strtoupper(trim($studentAnswer)) === strtoupper(trim($q['correct_answer'])));
                }

                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
                $earnedTotalScore += (int)($q['earned_score'] ?? 0);
            } else {
                $unansweredCount++;
                $q['earned_score'] = 0;
            }

            $q['is_answered'] = $hasAnswered;
            $q['is_correct'] = $isCorrect;
        }
        unset($q);

        $grade = $maxTotalScore > 0 ? round(($earnedTotalScore / $maxTotalScore) * 100, 1) : 0;

        return [
            'session' => $sessionDetails,
            'summary' => [
                'total_questions' => $totalQuestions,
                'max_score' => $maxTotalScore,
                'earned_score' => $earnedTotalScore,
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrectCount,
                'unanswered_count' => $unansweredCount,
                'grade' => $grade,
                'percentage' => $grade,
            ],
            'questions' => $questions
        ];
    }

    /**
     * Get student's completed and active exam history with calculated grades
     */
    public function getStudentHistory(int $studentId): array
    {
        $sql = "SELECT es.id as session_id,
                       es.exam_id,
                       es.student_id,
                       es.started_at,
                       es.finished_at,
                       es.status as session_status,
                       e.title as exam_title,
                       e.duration as exam_duration,
                       e.start_at as exam_start_at,
                       e.end_at as exam_end_at,
                       s.name as subject_name,
                       s.code as subject_code,
                       u_t.name as teacher_name,
                       (SELECT COUNT(eq_sub.question_id) FROM exam_questions eq_sub WHERE eq_sub.exam_id = es.exam_id) as total_questions,
                       (SELECT COALESCE(SUM(q_sub.score), 0) FROM exam_questions eq_sub JOIN questions q_sub ON eq_sub.question_id = q_sub.id WHERE eq_sub.exam_id = es.exam_id) as max_score,
                       (SELECT COUNT(a_sub.id) FROM answers a_sub WHERE a_sub.exam_session_id = es.id) as answered_count,
                       (SELECT COALESCE(SUM(a_sub.score), 0) FROM answers a_sub WHERE a_sub.exam_session_id = es.id) as earned_score,
                       (SELECT COUNT(a_sub.id) FROM answers a_sub JOIN questions q_sub ON a_sub.question_id = q_sub.id WHERE a_sub.exam_session_id = es.id AND ((q_sub.question_type IN ('multiple_choice', 'true_false') AND UPPER(a_sub.answer) = UPPER(q_sub.correct_answer)) OR (q_sub.question_type = 'essay' AND a_sub.score > 0))) as correct_count,
                       (SELECT COUNT(a_sub.id) FROM answers a_sub JOIN questions q_sub ON a_sub.question_id = q_sub.id WHERE a_sub.exam_session_id = es.id AND q_sub.question_type IN ('multiple_choice', 'true_false') AND a_sub.answer IS NOT NULL AND UPPER(a_sub.answer) != UPPER(q_sub.correct_answer)) as incorrect_count
                FROM {$this->table} es
                JOIN exams e ON es.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u_t ON t.user_id = u_t.id
                WHERE es.student_id = :student_id
                ORDER BY es.id DESC";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $totalQ = (int)$row['total_questions'];
            $maxScore = (int)$row['max_score'];
            $earned = (int)$row['earned_score'];
            $answered = (int)$row['answered_count'];
            $correct = (int)$row['correct_count'];
            $incorrect = (int)$row['incorrect_count'];
            $unanswered = max(0, $totalQ - $answered);
            $grade = $maxScore > 0 ? round(($earned / $maxScore) * 100, 1) : 0;

            $row['grade'] = $grade;
            $row['percentage'] = $grade;
            $row['unanswered_count'] = $unanswered;
            $results[] = $row;
        }

        return $results;
    }
}
