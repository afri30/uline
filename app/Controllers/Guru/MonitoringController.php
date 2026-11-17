<?php

declare(strict_types=1);

namespace App\Controllers\Guru;

use App\Core\Controller;
use App\Models\Exam;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Kelas;
use App\Models\ProctorEvent;
use App\Models\ExamSession;
use App\Models\CameraEvent;
use App\Services\Proctoring\ProctoringService;
use App\Services\Proctoring\CameraStreamService;
use App\Core\Session;

class MonitoringController extends Controller
{
    private Exam $examModel;
    private Teacher $teacherModel;
    private Subject $subjectModel;
    private Kelas $kelasModel;
    private ProctorEvent $eventModel;
    private ExamSession $sessionModel;
    private CameraEvent $cameraEventModel;
    private ProctoringService $proctoringService;
    private CameraStreamService $cameraStreamService;

    public function __construct()
    {
        parent::__construct();
        $this->examModel = new Exam();
        $this->teacherModel = new Teacher();
        $this->subjectModel = new Subject();
        $this->kelasModel = new Kelas();
        $this->eventModel = new ProctorEvent();
        $this->sessionModel = new ExamSession();
        $this->cameraEventModel = new CameraEvent();
        $this->proctoringService = new ProctoringService(
            $this->eventModel,
            null,
            null,
            $this->sessionModel,
            $this->cameraEventModel
        );
        $this->cameraStreamService = new CameraStreamService();
    }

    /**
     * Get active teacher ID from auth session
     */
    private function getTeacherId(): int
    {
        $user = auth();
        if (!$user) {
            redirect('login');
        }
        $teacher = $this->teacherModel->findByUserId((int)$user['id']);
        if (!$teacher) {
            http_response_code(403);
            die("Akun Anda belum terdaftar sebagai Guru.");
        }
        return (int)$teacher['id'];
    }

    /**
     * List all exams for monitoring (/guru/monitoring)
     */
    public function index(): void
    {
        $teacherId = $this->getTeacherId();

        $filters = [
            'search' => $this->request->get('search') ? trim((string)$this->request->get('search')) : '',
            'subject_id' => $this->request->get('subject_id') ? (int)$this->request->get('subject_id') : 0,
            'class_id' => $this->request->get('class_id') ? (int)$this->request->get('class_id') : 0,
            'status' => $this->request->get('status') ? trim((string)$this->request->get('status')) : ''
        ];

        $subjects = $this->subjectModel->all('name', 'ASC');
        $classes = $this->kelasModel->all('grade', 'ASC');

        $exams = $this->examModel->getByTeacherId($teacherId, $filters, 50, 0);

        $totalProctored = 0;
        $totalEvents = 0;
        $totalHighRisk = 0;
        $totalUnreviewed = 0;

        foreach ($exams as &$exam) {
            $overview = $this->proctoringService->getExamMonitoringOverview((int)$exam['id']);
            $exam['total_participants'] = $overview['summary']['total_participants'];
            $exam['total_events'] = $overview['summary']['total_events'];
            $exam['high_risk_participants'] = $overview['summary']['high_risk_count'];
            $exam['monitoring_summary'] = $overview['summary'];

            if (!empty($exam['ai_proctoring'])) {
                $totalProctored++;
            }
            $totalEvents += $overview['summary']['total_events'];
            $totalHighRisk += $overview['summary']['high_risk_count'];

            $totalUnreviewed += $this->eventModel->countUnreviewedByExamId((int)$exam['id']);
        }
        unset($exam);

        $this->view('guru/monitoring/index', [
            'title' => 'Monitoring & AI Proctoring',
            'exams' => $exams,
            'stats' => [
                'total_proctored_exams' => $totalProctored,
                'total_events' => $totalEvents,
                'total_high_risk' => $totalHighRisk,
                'total_unreviewed' => $totalUnreviewed
            ],
            'subjects' => $subjects,
            'classes' => $classes,
            'filters' => $filters
        ]);
    }

    /**
     * List participants and their Risk Scores for an exam (/guru/monitoring/{exam_id})
     */
    public function examSessions($examId): void
    {
        $examId = (int)$examId;
        $teacherId = $this->getTeacherId();

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            Session::flash('error', 'Data ujian tidak ditemukan atau akses ditolak.');
            redirect('guru/monitoring');
        }

        $riskFilter = $this->request->get('risk_level') ?: $this->request->get('risk');
        $search = $this->request->get('search') ? trim((string)$this->request->get('search')) : null;

        $overview = $this->proctoringService->getExamMonitoringOverview($examId, $riskFilter, $search);

        $sessions = [];
        foreach ($overview['participants'] as $p) {
            $stats = $p['proctor_stats'];
            $risk = $p['risk'];
            $sessions[] = [
                'session_id' => $p['session_id'],
                'student_id' => $p['student_id'],
                'student_name' => $p['student_name'],
                'nis' => $p['student_nis'],
                'status' => $p['session_status'],
                'is_online' => $p['is_online'] ?? false,
                'last_activity_at' => $p['last_activity_at'] ?? null,
                'last_active_formatted' => $p['last_active_formatted'] ?? '-',
                'camera_1_status' => $p['camera_1_status'] ?? 'none',
                'camera_2_status' => $p['camera_2_status'] ?? 'none',
                'cam1_age' => $this->cameraStreamService->getFrameAgeSeconds('session', (int)$p['session_id'], 'camera_1'),
                'cam2_age' => $this->cameraStreamService->getFrameAgeSeconds('session', (int)$p['session_id'], 'camera_2'),
                'started_at' => $p['started_at'],
                'finished_at' => $p['finished_at'],
                'risk_score' => $risk['score'],
                'risk_level' => $risk['level'],
                'risk_label' => $risk['label'],
                'risk_color' => $risk['color'] ?? ($risk['level'] === 'HIGH' ? 'danger' : ($risk['level'] === 'MEDIUM' ? 'warning' : 'success')),
                'warnings_count' => $p['warnings_count'] ?? ($stats['warning_count'] + $stats['high_count']),
                'total_events' => $stats['total_events'],
                'high_events' => $stats['high_count'],
                'warning_events' => $stats['warning_count'],
                'info_events' => $stats['info_count'],
                'unreviewed_count' => $stats['unreviewed_count']
            ];
        }

        $this->view('guru/monitoring/exam', [
            'title' => 'Pemantauan Ujian: ' . $exam['title'],
            'exam' => $exam,
            'sessions' => $sessions,
            'proctorCamLive' => $this->cameraStreamService->isProctorCameraLive($examId),
            'stats' => [
                'total_sessions' => $overview['summary']['total_participants'],
                'online_count' => $overview['summary']['online_count'] ?? 0,
                'offline_count' => $overview['summary']['offline_count'] ?? 0,
                'normal_count' => $overview['summary']['normal_count'] ?? $overview['summary']['low_risk_count'],
                'warning_count' => $overview['summary']['warning_count'] ?? $overview['summary']['medium_risk_count'],
                'high_risk_count' => $overview['summary']['high_risk_count'] ?? 0,
                'medium_risk' => $overview['summary']['medium_risk_count'] ?? 0,
                'low_risk' => $overview['summary']['low_risk_count'] ?? 0,
                'total_events' => $overview['summary']['total_events']
            ],
            'filters' => [
                'search' => $search ?? '',
                'risk_level' => $riskFilter ?? ''
            ]
        ]);
    }

    /**
     * Timeline event review for a single student session (/guru/monitoring/{exam_id}/{session_id})
     */
    public function sessionDetail($examId, $sessionId): void
    {
        $examId = (int)$examId;
        $sessionId = (int)$sessionId;
        $teacherId = $this->getTeacherId();

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            Session::flash('error', 'Data ujian tidak ditemukan atau akses ditolak.');
            redirect('guru/monitoring');
        }

        $session = $this->sessionModel->getSessionWithDetails($sessionId);
        if (!$session || (int)$session['exam_id'] !== $examId) {
            Session::flash('error', 'Sesi ujian peserta tidak ditemukan.');
            redirect('guru/monitoring/' . $examId);
        }

        $riskProfile = $this->proctoringService->getSessionRiskProfile($sessionId);
        $events = $riskProfile['events'];

        foreach ($events as &$ev) {
            $ev['meta'] = is_string($ev['metadata']) ? (json_decode($ev['metadata'], true) ?: []) : ($ev['metadata'] ?? []);
            $ev['confidence_pct'] = (int)round(((float)($ev['confidence'] ?? 0.8)) * 100);
            $ev['time_formatted'] = $ev['timestamp'] ? date('H:i:s - d/m/Y', strtotime($ev['timestamp'])) : '-';

            $sev = strtoupper($ev['severity'] ?? 'INFO');
            $ev['severity_badge'] = match($sev) {
                'HIGH' => ['bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'label' => 'HIGH SEVERITY'],
                'WARNING' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning-emphasis', 'label' => 'WARNING'],
                default => ['bg' => 'bg-info-subtle', 'text' => 'text-info', 'label' => 'INFO']
            };
            $ev['event_label'] = $ev['catalog_info']['name'] ?? str_replace('_', ' ', $ev['event_type']);
            $ev['event_icon'] = $ev['icon'] ?? 'bi-exclamation-circle';
            $ev['review_status'] = $ev['review_status'] ?? ($ev['reviewed'] ? 'reviewed' : 'pending');
        }
        unset($ev);

        // Extract camera verification check snapshots stored in camera_events
        $allCamEvents = $this->cameraEventModel->getBySessionId($sessionId);
        $verificationChecks = [];
        foreach (array_reverse($allCamEvents) as $ce) {
            if ($ce['event_type'] === 'verification_snapshot') {
                $verificationChecks[] = $ce;
            }
        }

        // Calculate remaining seconds if session is active
        $remainingSeconds = $this->sessionModel->calculateRemainingSeconds($session, (int)($session['exam_duration'] ?? $exam['duration']));

        $this->view('guru/monitoring/session_detail', [
            'title' => 'Linimasa Proctoring: ' . ($session['student_name'] ?? 'Peserta'),
            'exam' => $exam,
            'session' => $session,
            'events' => $events,
            'eventStats' => $riskProfile['stats'],
            'risk' => $riskProfile['risk'],
            'isOnline' => $riskProfile['is_online'],
            'cameras' => $riskProfile['cameras'],
            'cam1Age' => $this->cameraStreamService->getFrameAgeSeconds('session', $sessionId, 'camera_1'),
            'cam2Age' => $this->cameraStreamService->getFrameAgeSeconds('session', $sessionId, 'camera_2'),
            'proctorCamLive' => $this->cameraStreamService->isProctorCameraLive($examId),
            'verificationChecks' => array_slice($verificationChecks, 0, 6),
            'remainingSeconds' => $remainingSeconds
        ]);
    }

    /**
     * Submit teacher review for proctoring events (POST /guru/monitoring/review)
     */
    public function updateReview(): void
    {
        $teacherId = $this->getTeacherId();

        $token = (string)$this->request->input('_csrf_token', '');
        if (!csrf_verify($token)) {
            Session::flash('error', 'Sesi formulir kadaluarsa.');
            redirect('guru/monitoring');
        }

        $examId = (int)$this->request->input('exam_id', 0);
        $sessionId = (int)$this->request->input('session_id', 0);
        $eventId = (int)$this->request->input('event_id', 0);
        $reviewNote = trim((string)$this->request->input('review_note', ''));
        $reviewStatus = strtolower(trim((string)$this->request->input('review_status', 'reviewed')));
        $reviewed = ($reviewStatus !== 'pending');

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            Session::flash('error', 'Akses ditolak.');
            redirect('guru/monitoring');
        }

        if ($eventId > 0) {
            $this->eventModel->updateReview($eventId, $reviewed, $reviewNote, $reviewStatus);
            
            audit_log('REVIEW_EVENT', "Guru mereviu event (ID: {$eventId}) pada sesi ID {$sessionId} dengan status '{$reviewStatus}'.", [
                'event_id' => $eventId,
                'session_id' => $sessionId,
                'exam_id' => $examId,
                'review_status' => $reviewStatus,
                'review_note' => $reviewNote
            ]);

            Session::flash('success', 'Catatan reviu event berhasil disimpan.');
        } else {
            $this->eventModel->reviewAllForSession($sessionId, $reviewNote, $reviewStatus);

            audit_log('REVIEW_EVENT', "Guru mereviu seluruh event pada sesi ID {$sessionId} dengan status '{$reviewStatus}'.", [
                'session_id' => $sessionId,
                'exam_id' => $examId,
                'review_status' => $reviewStatus,
                'review_note' => $reviewNote
            ]);

            Session::flash('success', 'Seluruh event pada sesi ini telah diperbarui status reviu-nya.');
        }

        redirect('guru/monitoring/' . $examId . '/' . $sessionId);
    }

    /**
     * Hapus sesi peserta dan rekaman kamera (/guru/monitoring/{exam_id}/session/{session_id}/delete)
     */
    public function deleteSession($examId, $sessionId): void
    {
        $examId = (int)$examId;
        $sessionId = (int)$sessionId;
        $teacherId = $this->getTeacherId();

        $token = (string)$this->request->input('_csrf_token', '');
        if (!csrf_verify($token)) {
            Session::flash('error', 'Token keamanan CSRF tidak valid.');
            redirect('guru/monitoring/' . $examId);
        }

        $exam = $this->authorizeExam($examId, $teacherId);
        if (!$exam) {
            Session::flash('error', 'Akses ditolak atau data ujian tidak ditemukan.');
            redirect('guru/monitoring');
        }

        try {
            $db = \App\Core\Database::getConnection();
            $db->prepare("DELETE FROM answers WHERE exam_session_id = :sid")->execute([':sid' => $sessionId]);
            $db->prepare("DELETE FROM proctor_events WHERE exam_session_id = :sid")->execute([':sid' => $sessionId]);
            $db->prepare("DELETE FROM camera_events WHERE exam_session_id = :sid")->execute([':sid' => $sessionId]);
            $db->prepare("DELETE FROM exam_sessions WHERE id = :sid AND exam_id = :eid")->execute([':sid' => $sessionId, ':eid' => $examId]);

            // Bersihkan file frame kamera fisik di storage
            $storageDir = dirname(__DIR__, 3) . '/storage/cameras';
            @unlink($storageDir . "/session_{$sessionId}_camera_1.jpg");
            @unlink($storageDir . "/session_{$sessionId}_camera_2.jpg");

            audit_log('DELETE_SESSION', "Guru menghapus sesi ujian ID {$sessionId} pada ujian ID {$examId}.", [
                'session_id' => $sessionId,
                'exam_id' => $examId
            ]);

            Session::flash('success', 'Sesi peserta dan rekaman kamera berhasil dihapus.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menghapus sesi peserta: ' . $e->getMessage());
        }

        redirect('guru/monitoring/' . $examId);
    }

    /**
     * Authorize that exam belongs to current Guru
     */
    private function authorizeExam(int $examId, int $teacherId): ?array
    {
        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            return null;
        }
        return $exam;
    }

    /**
     * Stream student's latest live WebRTC camera frame securely to exam owner (/guru/monitoring/camera-frame/{exam_id}/{session_id}/{cam})
     */
    public function cameraFrame($examId, $sessionId, $cam): void
    {
        $examId = (int)$examId;
        $sessionId = (int)$sessionId;
        $teacherId = $this->getTeacherId();

        if (!$this->authorizeExam($examId, $teacherId)) {
            http_response_code(403);
            exit;
        }

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['exam_id'] !== $examId) {
            http_response_code(404);
            exit;
        }

        $camKey = ($cam === 'camera_2' || $cam === 'secondary') ? 'camera_2' : 'camera_1';
        $path = $this->cameraStreamService->getFramePath('session', $sessionId, $camKey);
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

    /**
     * AJAX JSON polling endpoint for live monitoring dashboard & camera grid (/guru/monitoring/live-data/{exam_id})
     */
    public function liveData($examId): void
    {
        $examId = (int)$examId;
        $teacherId = $this->getTeacherId();

        if (!$this->authorizeExam($examId, $teacherId)) {
            $this->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            return;
        }

        $overview = $this->proctoringService->getExamMonitoringOverview($examId);
        $participants = [];
        foreach ($overview['participants'] as $p) {
            $sid = (int)$p['session_id'];
            $cam1Age = $this->cameraStreamService->getFrameAgeSeconds('session', $sid, 'camera_1');
            $cam2Age = $this->cameraStreamService->getFrameAgeSeconds('session', $sid, 'camera_2');

            $participants[] = [
                'session_id' => $sid,
                'student_id' => (int)$p['student_id'],
                'student_name' => $p['student_name'],
                'nis' => $p['student_nis'] ?? '-',
                'status' => $p['session_status'],
                'is_online' => (bool)$p['is_online'],
                'last_active' => $p['last_active_formatted'] ?? '-',
                'camera_1_status' => $p['camera_1_status'] ?? 'none',
                'camera_2_status' => $p['camera_2_status'] ?? 'none',
                'cam1_has_frame' => $cam1Age !== null,
                'cam2_has_frame' => $cam2Age !== null,
                'cam1_age_seconds' => $cam1Age,
                'cam2_age_seconds' => $cam2Age,
                'risk_score' => $p['risk']['score'],
                'risk_color' => $p['risk']['color'] ?? 'success',
                'risk_label' => $p['risk']['label'],
                'warnings_count' => $p['warnings_count'] ?? 0,
            ];
        }

        $this->json([
            'status' => 'success',
            'summary' => $overview['summary'],
            'proctor_camera_live' => $this->cameraStreamService->isProctorCameraLive($examId),
            'participants' => $participants,
            'timestamp' => date('H:i:s')
        ]);
    }

    /**
     * Upload or stop Guru Proctor Camera live snapshot during exam supervision (/guru/monitoring/proctor-camera)
     */
    public function uploadProctorCamera(): void
    {
        $teacherId = $this->getTeacherId();
        $token = (string)$this->request->input('_csrf_token', '');
        if (!csrf_verify($token)) {
            $this->json(['status' => 'error', 'message' => 'Token CSRF tidak valid.'], 403);
            return;
        }

        $examId = (int)$this->request->input('exam_id', 0);
        if (!$this->authorizeExam($examId, $teacherId)) {
            $this->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            return;
        }

        $action = (string)$this->request->input('action', 'stream');
        if ($action === 'stop') {
            $this->cameraStreamService->removeFrame('proctor_exam', $examId, 'camera_1');
            $this->json(['status' => 'success', 'proctor_camera_live' => false]);
            return;
        }

        $imageBase64 = (string)$this->request->input('image_base64', '');
        try {
            $saved = $this->cameraStreamService->saveBase64Snapshot($imageBase64, 'proctor_exam', $examId, 'camera_1');
            $this->json([
                'status' => 'success',
                'proctor_camera_live' => true,
                'updated_at' => $saved['updated_at']
            ]);
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Capture manual visual verification snapshot of student's live camera (/guru/monitoring/capture-evidence)
     */
    public function captureEvidence(): void
    {
        $teacherId = $this->getTeacherId();
        $token = (string)$this->request->input('_csrf_token', '');
        if (!csrf_verify($token)) {
            $this->json(['status' => 'error', 'message' => 'Token CSRF tidak valid.'], 403);
            return;
        }

        $examId = (int)$this->request->input('exam_id', 0);
        $sessionId = (int)$this->request->input('session_id', 0);
        $cam = (string)$this->request->input('camera_type', 'camera_1');
        $camKey = ($cam === 'camera_2' || $cam === 'secondary') ? 'camera_2' : 'camera_1';

        if (!$this->authorizeExam($examId, $teacherId)) {
            $this->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            return;
        }

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['exam_id'] !== $examId) {
            $this->json(['status' => 'error', 'message' => 'Sesi tidak ditemukan.'], 404);
            return;
        }

        $filename = $this->cameraStreamService->copyLiveFrameToEvidence($sessionId, $camKey, 'check');
        if (!$filename) {
            $this->json(['status' => 'error', 'message' => 'Belum ada gambar live kamera peserta yang tersedia untuk diambil.'], 404);
            return;
        }

        $evidenceRoute = 'guru/monitoring/evidence?file=' . urlencode($filename);
        $user = auth();
        $this->cameraEventModel->logEvent($sessionId, $camKey, 'verification_snapshot', [
            'evidence_url' => $evidenceRoute,
            'checked_by' => $user['name'] ?? 'Guru Pengawas',
            'captured_at' => date('Y-m-d H:i:s')
        ]);

        audit_log('CAMERA_VERIFICATION', "Guru mengambil foto pengecekan kamera ({$camKey}) pada sesi ID {$sessionId}.", [
            'exam_id' => $examId,
            'session_id' => $sessionId,
            'evidence' => $evidenceRoute
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Foto pengecekan kamera berhasil disimpan sebagai bukti.',
            'evidence_url' => base_url($evidenceRoute),
            'captured_at' => date('H:i:s - d/m/Y')
        ]);
    }

    /**
     * Securely serve saved camera evidence image (/guru/monitoring/evidence?file=... or /{filename})
     */
    public function serveEvidence($filename = ''): void
    {
        $teacherId = $this->getTeacherId();
        $rawName = $filename !== '' ? (string)$filename : (string)$this->request->get('file', '');
        $safeName = basename($rawName);

        if (!preg_match('/_session_(\d+)_/', $safeName, $m)) {
            http_response_code(403);
            exit;
        }
        $sessionId = (int)$m[1];
        $session = $this->sessionModel->find($sessionId);
        if (!$session || !$this->authorizeExam((int)$session['exam_id'], $teacherId)) {
            http_response_code(403);
            exit;
        }

        $path = $this->cameraStreamService->getEvidenceFilePath($safeName);
        $img = $path ? $this->cameraStreamService->readImageForHttp($path) : null;
        if (!$img) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $img['mime']);
        header('Cache-Control: private, max-age=3600');
        echo $img['data'];
        exit;
    }
}
