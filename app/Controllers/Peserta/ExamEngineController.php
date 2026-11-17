<?php

declare(strict_types=1);

namespace App\Controllers\Peserta;

use App\Core\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Student;
use App\Models\CameraEvent;
use App\Services\Proctoring\ProctoringService;
use App\Services\Proctoring\CameraStreamService;
use App\Core\Session;
use Exception;

class ExamEngineController extends Controller
{
    private Exam $examModel;
    private ExamSession $sessionModel;
    private Answer $answerModel;
    private Question $questionModel;
    private Student $studentModel;
    private CameraEvent $cameraEventModel;
    private ProctoringService $proctoringService;
    private CameraStreamService $cameraStreamService;

    public function __construct()
    {
        parent::__construct();
        $this->examModel = new Exam();
        $this->sessionModel = new ExamSession();
        $this->answerModel = new Answer();
        $this->questionModel = new Question();
        $this->studentModel = new Student();
        $this->cameraEventModel = new CameraEvent();
        $this->proctoringService = new ProctoringService();
        $this->cameraStreamService = new CameraStreamService();
    }

    /**
     * Get active student data from auth session
     */
    private function getStudent(): array
    {
        $user = auth();
        if (!$user) {
            redirect('login');
        }

        $student = $this->studentModel->findByUserIdWithClass((int)$user['id']);
        if (!$student) {
            http_response_code(403);
            die("Akun Anda belum terhubung dengan data Peserta Didik.");
        }

        return $student;
    }

    /**
     * Verify CSRF token helper
     */
    private function checkCsrf(): bool
    {
        $token = $this->request->string('_csrf_token');
        return csrf_verify($token);
    }

    /**
     * List available exams & history (/peserta/exams)
     */
    public function index(): void
    {
        $student = $this->getStudent();
        $classId = (int)$student['class_id'];
        $studentId = (int)$student['id'];

        $availableExams = $this->examModel->getAvailableForStudentClass($classId, $studentId);
        $history = $this->sessionModel->getStudentHistory($studentId);

        $this->view('peserta/exams/index', [
            'title' => 'Daftar Ujian Online - Panel Peserta',
            'student' => $student,
            'availableExams' => $availableExams,
            'history' => $history
        ]);
    }

    /**
     * Exam confirmation / instruction page (/peserta/exams/detail?id=X)
     */
    public function detail(): void
    {
        $student = $this->getStudent();
        $examId = (int)$this->request->input('id', 0);

        if ($examId <= 0) {
            Session::setFlash('error', 'ID Ujian tidak valid.');
            $this->redirect('peserta/exams');
            return;
        }

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || !in_array($exam['status'], ['published', 'active'], true)) {
            Session::setFlash('error', 'Ujian tidak ditemukan atau belum dipublikasikan.');
            $this->redirect('peserta/exams');
            return;
        }

        // Enforce Class Isolation: Student must be in the target class
        if ((int)$exam['class_id'] !== (int)$student['class_id']) {
            http_response_code(403);
            die("Akses ditolak: Ujian ini tidak ditugaskan untuk kelas Anda.");
        }

        $studentId = (int)$student['id'];
        $session = $this->sessionModel->findByExamAndStudent($examId, $studentId);

        // If session is already finished, redirect to result
        if ($session && in_array($session['status'], ['finished', 'expired'], true)) {
            $this->redirect('peserta/exams/result?session_id=' . $session['id']);
            return;
        }

        $questionsCount = count($this->examModel->getExamQuestions($examId));

        $this->view('peserta/exams/detail', [
            'title' => 'Konfirmasi Ujian: ' . $exam['title'],
            'student' => $student,
            'exam' => $exam,
            'session' => $session,
            'questionsCount' => $questionsCount
        ]);
    }

    /**
     * Start exam session (/peserta/exams/start)
     */
    public function start(): void
    {
        if (!$this->checkCsrf()) {
            Session::setFlash('error', 'Sesi Anda telah kedaluwarsa. Silakan coba kembali.');
            $this->redirect('peserta/exams');
            return;
        }

        $student = $this->getStudent();
        $examId = (int)$this->request->input('exam_id', 0);

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || !in_array($exam['status'], ['published', 'active'], true)) {
            Session::setFlash('error', 'Ujian tidak aktif atau tidak ditemukan.');
            $this->redirect('peserta/exams');
            return;
        }

        // Validate class assignment
        if ((int)$exam['class_id'] !== (int)$student['class_id']) {
            http_response_code(403);
            die("Akses ditolak: Ujian ini bukan untuk kelas Anda.");
        }

        $studentId = (int)$student['id'];
        $durationMinutes = (int)$exam['duration'];

        // Get or initialize session
        $session = $this->sessionModel->getOrCreateSession($examId, $studentId, $durationMinutes);
        $this->sessionModel->touchActivity((int)$session['id']);

        audit_log('START_EXAM', "Peserta {$student['name']} memulai ujian '{$exam['title']}' (Session ID: {$session['id']}).", [
            'session_id' => $session['id'],
            'exam_id' => $examId,
            'student_id' => $studentId,
            'status' => $session['status']
        ]);

        // Notify Teacher that student has started the exam
        try {
            (new \App\Services\Notification\NotificationService())->notifyTeacherExamStarted((int)$session['id'], $examId, $studentId);
        } catch (\Throwable $e) {}

        if (in_array($session['status'], ['finished', 'expired'], true)) {
            $this->redirect('peserta/exams/result?session_id=' . $session['id']);
            return;
        }

        $this->redirect('peserta/exams/room?session_id=' . $session['id']);
    }

    /**
     * Interactive Exam Room (/peserta/exams/room?session_id=X)
     */
    public function room(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);

        $session = $this->sessionModel->getSessionWithDetails($sessionId);
        if (!$session) {
            Session::setFlash('error', 'Sesi ujian tidak ditemukan.');
            $this->redirect('peserta/exams');
            return;
        }

        // Validate ownership: Session must belong to logged-in student
        if ((int)$session['student_id'] !== (int)$student['id']) {
            http_response_code(403);
            die("Akses ditolak: Sesi ujian ini milik peserta lain.");
        }

        // Server-Side Time Verification
        $duration = (int)$session['exam_duration'];
        $remainingSeconds = $this->sessionModel->calculateRemainingSeconds($session, $duration);

        if ($remainingSeconds <= 0 && $session['status'] === 'started') {
            // Auto-finish on expiry
            $this->sessionModel->finishSession($sessionId, 'expired');
            Session::setFlash('warning', 'Waktu pengerjaan ujian telah habis.');
            $this->redirect('peserta/exams/result?session_id=' . $sessionId);
            return;
        }

        if (in_array($session['status'], ['finished', 'expired'], true)) {
            $this->redirect('peserta/exams/result?session_id=' . $sessionId);
            return;
        }

        // Fetch exam questions
        $examId = (int)$session['exam_id'];
        $questions = $this->examModel->getExamQuestions($examId);

        if (empty($questions)) {
            Session::setFlash('error', 'Ujian ini belum memiliki butir soal.');
            $this->redirect('peserta/exams');
            return;
        }

        // Handle Randomization of Questions based on Student ID Seed
        if ($session['random_questions']) {
            mt_srand($sessionId + 12345);
            shuffle($questions);
            mt_srand(); // Reset RNG
        }

        // Fetch student's existing answers map
        $answersMap = $this->answerModel->getAnswersMapBySessionId($sessionId);

        $this->view('peserta/exams/room', [
            'title' => 'Ujian Berlangsung: ' . $session['exam_title'],
            'student' => $student,
            'session' => $session,
            'questions' => $questions,
            'answersMap' => $answersMap,
            'remainingSeconds' => $remainingSeconds
        ]);
    }

    /**
     * AJAX auto-save answer endpoint (/peserta/exams/save-answer)
     */
    public function saveAnswer(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);
        $questionId = (int)$this->request->input('question_id', 0);
        $answerValue = (string)$this->request->input('answer', '');

        $session = $this->sessionModel->getSessionWithDetails($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            $this->json(['status' => 'error', 'message' => 'Sesi ujian tidak valid.'], 403);
            return;
        }

        // Validate time left on server
        $remainingSeconds = $this->sessionModel->calculateRemainingSeconds($session, (int)$session['exam_duration']);
        if ($remainingSeconds <= 0 || in_array($session['status'], ['finished', 'expired'], true)) {
            if ($session['status'] === 'started') {
                $this->sessionModel->finishSession($sessionId, 'expired');
            }
            $this->json([
                'status' => 'expired',
                'message' => 'Waktu pengerjaan telah habis. Ujian akan dikumpulkan otomatis.'
            ], 200);
            return;
        }

        // Find question metadata for auto-scoring
        $question = $this->questionModel->find($questionId);
        if (!$question) {
            $this->json(['status' => 'error', 'message' => 'Butir soal tidak ditemukan.'], 404);
            return;
        }

        // Save or update answer
        $saved = $this->answerModel->saveOrUpdateAnswer($sessionId, $questionId, $answerValue, $question);
        $this->sessionModel->touchActivity($sessionId);

        // Get total answered questions count
        $answersMap = $this->answerModel->getAnswersMapBySessionId($sessionId);

        $this->json([
            'status' => 'success',
            'message' => 'Jawaban berhasil disimpan.',
            'data' => $saved,
            'answered_count' => count($answersMap),
            'remaining_seconds' => $remainingSeconds
        ]);
    }

    /**
     * Final submission (/peserta/exams/finish)
     */
    public function finish(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);
        $isExpired = (bool)$this->request->input('is_expired', false);

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            http_response_code(403);
            die("Akses ditolak.");
        }

        $status = $isExpired ? 'expired' : 'finished';
        $this->sessionModel->finishSession($sessionId, $status);

        audit_log('SUBMIT_EXAM', "Peserta {$student['name']} mengumpulkan ujian (Session ID: {$sessionId}, Status: {$status}).", [
            'session_id' => $sessionId,
            'exam_id' => $session['exam_id'],
            'student_id' => $student['id'],
            'status' => $status
        ]);

        // Notify Student and Teacher that exam is finished
        try {
            (new \App\Services\Notification\NotificationService())->notifyExamFinished((int)$sessionId, (int)$session['exam_id'], (int)$student['id']);
        } catch (\Throwable $e) {}

        Session::setFlash('success', 'Ujian telah berhasil dikumpulkan dan dinilai.');
        $this->redirect('peserta/exams/result?session_id=' . $sessionId);
    }

    /**
     * Exam result & score breakdown (/peserta/exams/result?session_id=X)
     */
    public function result(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);

        $session = $this->sessionModel->getSessionWithDetails($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            http_response_code(403);
            die("Hasil ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
        }

        $scoreStats = $this->sessionModel->calculateSessionScore($sessionId);

        $this->view('peserta/exams/result', [
            'title' => 'Hasil Ujian: ' . $session['exam_title'],
            'student' => $student,
            'session' => $session,
            'stats' => $scoreStats
        ]);
    }

    /**
     * Periodic student heartbeat (/peserta/exams/heartbeat)
     */
    public function heartbeat(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            $this->json(['status' => 'error', 'message' => 'Sesi ujian tidak valid.'], 403);
            return;
        }

        $this->sessionModel->touchActivity($sessionId);
        $remainingSeconds = $this->sessionModel->calculateRemainingSeconds($session, (int)($session['duration'] ?? 60));

        $this->json([
            'status' => 'success',
            'online' => true,
            'remaining_seconds' => $remainingSeconds,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log WebRTC camera event asynchronously (/peserta/exams/camera-event)
     */
    public function logCameraEvent(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);
        $cameraType = (string)$this->request->input('camera_type', 'camera_1');
        $eventType = (string)$this->request->input('event_type', 'connected');
        $metadata = $this->request->input('metadata');

        // Allow metadata as array or JSON string
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            } else {
                $metadata = ['info' => $metadata];
            }
        } elseif (!is_array($metadata)) {
            $metadata = null;
        }

        // Validate session ownership
        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            $this->json(['status' => 'error', 'message' => 'Sesi ujian tidak valid atau akses ditolak.'], 403);
            return;
        }

        $this->sessionModel->touchActivity($sessionId);
        $eventId = $this->cameraEventModel->logEvent($sessionId, $cameraType, $eventType, $metadata);

        $this->json([
            'status' => 'success',
            'message' => 'Event kamera berhasil dicatat.',
            'event_id' => $eventId
        ]);
    }

    /**
     * Log suspicious proctoring event (/peserta/exams/proctor-event)
     */
    public function logProctorEvent(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);
        $cameraType = (string)$this->request->input('camera_type', 'primary');
        $eventType = (string)$this->request->input('event_type', '');
        $confidence = (float)$this->request->input('confidence', 0.85);
        $evidencePath = (string)$this->request->input('evidence_path', '');
        $metadata = $this->request->input('metadata');

        // Allow metadata as array or JSON string
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            } else {
                $metadata = ['info' => $metadata];
            }
        } elseif (!is_array($metadata)) {
            $metadata = [];
        }

        // Validate session ownership
        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            $this->json(['status' => 'error', 'message' => 'Sesi ujian tidak valid atau akses ditolak.'], 403);
            return;
        }

        $this->sessionModel->touchActivity($sessionId);

        // Attach latest live camera frame automatically as visual evidence if none supplied
        if ($evidencePath === '') {
            $camKey = ($cameraType === 'secondary' || $cameraType === 'camera_2') ? 'camera_2' : 'camera_1';
            $evFile = $this->cameraStreamService->copyLiveFrameToEvidence($sessionId, $camKey, 'proctor');
            if ($evFile !== null) {
                $evidencePath = 'guru/monitoring/evidence?file=' . urlencode($evFile);
            }
        }

        // Record suspicious event via Proctoring Service
        $result = $this->proctoringService->recordSuspiciousEvent(
            $sessionId,
            $cameraType,
            $eventType,
            $confidence,
            $metadata,
            $evidencePath
        );

        $authUser = auth();
        $studentName = $authUser['name'] ?? ($student['name'] ?? 'Peserta');
        audit_log('PROCTOR_EVENT', "AI Proctoring mendeteksi event '{$result['event_type']}' (Severity: {$result['severity']}, Confidence: " . round($result['confidence'] * 100) . "%) pada siswa {$studentName}.", [
            'session_id' => $sessionId,
            'event_id' => $result['event_id'],
            'event_type' => $result['event_type'],
            'severity' => $result['severity'],
            'confidence' => $result['confidence']
        ]);

        // Notify Teacher if suspicious event is WARNING or HIGH
        if (in_array(strtoupper((string)($result['severity'] ?? '')), ['WARNING', 'HIGH'], true)) {
            try {
                (new \App\Services\Notification\NotificationService())->notifyTeacherProctorAlert(
                    (int)$sessionId,
                    (int)$session['exam_id'],
                    (int)$student['id'],
                    (string)$result['event_type'],
                    (string)$result['severity']
                );
            } catch (\Throwable $e) {}
        }

        $this->json([
            'status' => 'success',
            'message' => 'Suspicious proctoring event berhasil dicatat.',
            'data' => $result
        ]);
    }

    /**
     * Save live student WebRTC camera frame snapshot during exam (/peserta/exams/camera-snapshot)
     */
    public function saveCameraSnapshot(): void
    {
        if (!$this->checkCsrf()) {
            $this->json(['status' => 'error', 'message' => 'Token CSRF tidak valid.'], 403);
            return;
        }

        $student = $this->getStudent();
        $sessionId = (int)$this->request->input('session_id', 0);
        $cameraType = (string)$this->request->input('camera_type', 'camera_1');
        $imageBase64 = (string)$this->request->input('image_base64', '');

        $normalizedCam = ($cameraType === 'camera_2' || $cameraType === 'secondary') ? 'camera_2' : 'camera_1';

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id']) {
            $this->json(['status' => 'error', 'message' => 'Sesi ujian tidak valid atau akses ditolak.'], 403);
            return;
        }

        if (($session['status'] ?? '') !== 'started') {
            $this->json(['status' => 'error', 'message' => 'Sesi ujian sudah berakhir.'], 400);
            return;
        }

        try {
            $saved = $this->cameraStreamService->saveBase64Snapshot($imageBase64, 'session', $sessionId, $normalizedCam);
            $this->sessionModel->touchActivity($sessionId);

            $examId = (int)$session['exam_id'];
            $proctorLive = $this->cameraStreamService->isProctorCameraLive($examId);

            $this->json([
                'status' => 'success',
                'camera_type' => $normalizedCam,
                'updated_at' => $saved['updated_at'],
                'proctor_camera_live' => $proctorLive
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Stream Guru Proctor live camera snapshot to actively enrolled student (/peserta/exams/proctor-camera)
     */
    public function getProctorCameraFrame(): void
    {
        $student = $this->getStudent();
        $sessionId = (int)$this->request->get('session_id', 0);

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['student_id'] !== (int)$student['id'] || ($session['status'] ?? '') !== 'started') {
            http_response_code(403);
            exit;
        }

        $examId = (int)$session['exam_id'];
        if (!$this->cameraStreamService->isProctorCameraLive($examId, 30)) {
            http_response_code(204);
            exit;
        }

        $path = $this->cameraStreamService->getFramePath('proctor_exam', $examId, 'camera_1');
        $img = $path ? $this->cameraStreamService->readImageForHttp($path) : null;
        if (!$img) {
            http_response_code(204);
            exit;
        }

        header('Content-Type: ' . $img['mime']);
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $img['data'];
        exit;
    }
}
