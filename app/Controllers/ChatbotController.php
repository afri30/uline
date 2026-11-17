<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AI\NvidiaAIService;
use App\Services\AI\GuardrailService;
use App\Services\AI\ChatbotDatabaseService;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Subject;
use App\Models\Kelas;
use Exception;
use Throwable;
use PDO;

class ChatbotController extends Controller
{
    private NvidiaAIService $aiService;
    private GuardrailService $guardrail;
    private ChatbotDatabaseService $dbService;
    private User $userModel;
    private ExamSession $sessionModel;
    private Question $questionModel;
    private Exam $examModel;

    public function __construct()
    {
        parent::__construct();
        $this->aiService = new NvidiaAIService();
        $this->guardrail = new GuardrailService();
        $this->dbService = new ChatbotDatabaseService();
        $this->userModel = new User();
        $this->sessionModel = new ExamSession();
        $this->questionModel = new Question();
        $this->examModel = new Exam();
    }

    /**
     * Tipe 1: Floating Chatbot Global (Scoped by user role with Database Grounding)
     * POST /api/chat/global
     */
    public function chatGlobal(): void
    {
        $user = auth();
        if (!$user) {
            $this->json([
                'status' => 'error',
                'message' => 'Sesi login tidak valid. Silakan login kembali.'
            ], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $userMessage = trim((string)($input['message'] ?? ''));
        $history = is_array($input['history'] ?? null) ? $input['history'] : [];

        // Delegate chat execution to ChatbotDatabaseService (Guardrails + DB Scoping + AI + Grounded Fallback)
        $response = $this->dbService->processChat($userMessage, $user, $history);
        $httpCode = ($response['status'] === 'error') ? 503 : 200;

        $this->json($response, $httpCode);
    }


    /**
     * Tipe 2: Chatbot Penjelasan Tiap Soal (AI Tutor Explanation)
     * POST /api/chat/explain-question
     */
    public function explainQuestion(): void
    {
        $user = auth();
        if (!$user) {
            $this->json([
                'status' => 'error',
                'message' => 'Sesi login tidak valid. Silakan login kembali.'
            ], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $sessionId = (int)($input['session_id'] ?? 0);
        $questionId = (int)($input['question_id'] ?? 0);
        $userQuestion = trim((string)($input['question'] ?? ''));

        if ($sessionId <= 0 || $questionId <= 0) {
            $this->json([
                'status' => 'error',
                'message' => 'Parameter session_id dan question_id wajib diisi.'
            ], 422);
            return;
        }

        // 1. Fetch Session & Security Verification
        $session = $this->sessionModel->getSessionWithDetails($sessionId);
        if (!$session) {
            $this->json([
                'status' => 'error',
                'message' => 'Sesi ujian tidak ditemukan.'
            ], 404);
            return;
        }

        // Strict Authorization checks
        $role = $user['role'] ?? '';
        if ($role === 'PESERTA') {
            $studentModel = new Student();
            $student = $studentModel->findByUserId((int)$user['id']);
            if (!$student || (int)$session['student_id'] !== (int)$student['id']) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Sesi ujian ini bukan milik Anda.'
                ], 403);
                return;
            }

            // CRITICAL SECURITY GUARDRAIL: Cannot explain during active exam!
            if ($session['status'] === 'started') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Pembahasan soal AI hanya dapat diakses setelah Anda menyelesaikan dan mengirim lembar jawaban ujian.'
                ], 403);
                return;
            }
        } elseif ($role === 'GURU') {
            $teacherModel = new Teacher();
            $teacher = $teacherModel->findByUserId((int)$user['id']);
            $exam = $this->examModel->find((int)$session['exam_id']);
            if (!$teacher || !$exam || (int)$exam['teacher_id'] !== (int)$teacher['id']) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Ujian ini bukan milik Anda.'
                ], 403);
                return;
            }
        } elseif ($role !== 'SUPER_ADMIN') {
            $this->json([
                'status' => 'error',
                'message' => 'Role tidak diizinkan mengakses fitur ini.'
            ], 403);
            return;
        }

        // 2. Fetch Question Data
        $question = $this->questionModel->find($questionId);
        if (!$question) {
            $this->json([
                'status' => 'error',
                'message' => 'Data soal tidak ditemukan.'
            ], 404);
            return;
        }

        // 3. Fetch Student Answer
        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM `answers` WHERE `exam_session_id` = :sid AND `question_id` = :qid LIMIT 1");
        $stmt->execute([':sid' => $sessionId, ':qid' => $questionId]);
        $answer = $stmt->fetch(PDO::FETCH_ASSOC);

        $studentAnswer = $answer['answer'] ?? '(Tidak Dijawab)';
        $isCorrect = (strcasecmp((string)$studentAnswer, (string)$question['correct_answer']) === 0);

        // Package strictly scoped question context
        $options = [];
        if (($question['question_type'] ?? '') === 'multiple_choice') {
            if (!empty($question['option_a'])) $options['A'] = $question['option_a'];
            if (!empty($question['option_b'])) $options['B'] = $question['option_b'];
            if (!empty($question['option_c'])) $options['C'] = $question['option_c'];
            if (!empty($question['option_d'])) $options['D'] = $question['option_d'];
        }

        $questionData = [
            'subject_name' => $session['subject_name'] ?? 'Mata Pelajaran',
            'question_type' => $question['question_type'],
            'difficulty' => $question['difficulty'],
            'question_text' => $question['question_text'],
            'options' => $options,
            'student_answer' => $studentAnswer,
            'correct_answer' => $question['correct_answer'],
            'is_correct' => $isCorrect,
            'teacher_explanation' => $question['explanation'] ?? null
        ];

        // 4. Validate custom user prompt if present
        if (!empty($userQuestion)) {
            $validation = $this->guardrail->validateUserInput($userQuestion);
            if (!$validation['passed']) {
                $this->json([
                    'status' => 'success',
                    'explanation' => $validation['fallback'] ?? 'Pertanyaan tidak valid.',
                    'question_id' => $questionId
                ]);
                return;
            }
        }

        // 5. Build Tutor System Prompt
        $systemPrompt = $this->guardrail->buildQuestionTutorSystemPrompt($questionData);

        $userPrompt = !empty($userQuestion) 
            ? "Peserta bertanya: '{$userQuestion}'. Tolong jelaskan dan kaitkan langsung dengan konsep soal ini." 
            : "Tolong berikan penjelasan lengkap, langkah penyelesaian, dan analisis mengapa jawaban tersebut benar serta tips konsepnya.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        // 6. Query NVIDIA NIM API
        try {
            $rawExplanation = $this->aiService->chatCompletion($messages, [
                'temperature' => 0.2,
                'max_tokens' => 350
            ]);


            $sanitizedExplanation = $this->guardrail->sanitizeOutput($rawExplanation);

            $this->json([
                'status' => 'success',
                'explanation' => $sanitizedExplanation,
                'question_id' => $questionId,
                'is_correct' => $isCorrect,
                'correct_answer' => $question['correct_answer'],
                'student_answer' => $studentAnswer
            ]);
        } catch (Throwable $e) {
            // Factual grounded fallback based on question details and teacher explanation
            $statusStr = $isCorrect ? 'Jawaban Anda BENAR.' : 'Jawaban Anda SALAH.';
            $teacherNote = !empty($question['explanation']) ? "\n\n**Catatan Guru**: " . $question['explanation'] : '';
            $fallbackExplanation = "### Pembahasan Soal ({$questionData['subject_name']})\n\n" .
                "**Status**: {$statusStr}\n" .
                "- **Kunci Jawaban**: **{$question['correct_answer']}**\n" .
                "- **Jawaban Anda**: **{$studentAnswer}**\n\n" .
                "**Konsep Soal**: Soal ini bertipe *" . ucfirst(str_replace('_', ' ', (string)$question['question_type'])) . 
                "* dengan tingkat kesulitan *" . ucfirst((string)$question['difficulty']) . "*." .
                $teacherNote . "\n\n" .
                "*(Layanan AI sedang sibuk, pembahasan di atas dirangkum langsung dari kunci data resmi)*";

            $this->json([
                'status' => 'success',
                'explanation' => $fallbackExplanation,
                'question_id' => $questionId,
                'is_correct' => $isCorrect,
                'correct_answer' => $question['correct_answer'],
                'student_answer' => $studentAnswer
            ]);
        }
    }

    /**
     * Mengambil data database aktual secara live dan terstruktur untuk U-Line AI Assistant
     * Didelegasikan ke ChatbotDatabaseService
     */
    public function getLiveDatabaseSummary(): array
    {
        return $this->dbService->getLiveDatabaseSummary();
    }

    /**
     * Mengumpulkan data platform dan database aktual untuk U-Line AI Assistant
     * Didelegasikan ke ChatbotDatabaseService
     */
    public function getScopedDataForUser(array $user): array
    {
        return $this->dbService->getScopedDataForUser($user);
    }
}

