<?php

declare(strict_types=1);

namespace App\Controllers\Guru;

use App\Core\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Kelas;
use App\Core\Session;
use Exception;

class ExamController extends Controller
{
    private Exam $examModel;
    private Question $questionModel;
    private Teacher $teacherModel;
    private Subject $subjectModel;
    private Kelas $kelasModel;

    public function __construct()
    {
        parent::__construct();
        $this->examModel = new Exam();
        $this->questionModel = new Question();
        $this->teacherModel = new Teacher();
        $this->subjectModel = new Subject();
        $this->kelasModel = new Kelas();
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
            die("Akun Anda belum terdaftar sebagai data profil Guru penguji.");
        }
        return (int)$teacher['id'];
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
     * Exam List (/guru/exams)
     */
    public function index(): void
    {
        $teacherId = $this->getTeacherId();

        $page = max(1, (int)$this->request->input('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => trim($this->request->string('search')),
            'subject_id' => $this->request->string('subject_id'),
            'class_id' => $this->request->string('class_id'),
            'status' => $this->request->string('status')
        ];

        $exams = $this->examModel->getByTeacherId($teacherId, $filters, $limit, $offset);
        $totalExams = $this->examModel->countByTeacherId($teacherId, $filters);
        $totalPages = ceil($totalExams / $limit);

        $stats = $this->examModel->getTeacherStats($teacherId);
        $mySubjects = $this->subjectModel->getByTeacherId($teacherId);
        if (empty($mySubjects)) {
            $mySubjects = $this->subjectModel->findAll('name', 'ASC');
        }
        $allClasses = $this->kelasModel->all();

        $this->view('guru/exams/index', [
            'title' => 'Manajemen Ujian - Panel Guru',
            'exams' => $exams,
            'totalExams' => $totalExams,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'stats' => $stats,
            'mySubjects' => $mySubjects,
            'allClasses' => $allClasses
        ]);
    }

    /**
     * Create Exam Form (/guru/exams/create)
     */
    public function create(): void
    {
        $teacherId = $this->getTeacherId();

        $mySubjects = $this->subjectModel->getByTeacherId($teacherId);
        if (empty($mySubjects)) {
            $mySubjects = $this->subjectModel->findAll('name', 'ASC');
        }
        $allClasses = $this->kelasModel->all();
        $questions = $this->questionModel->getAllByTeacherAndSubject($teacherId);

        $this->view('guru/exams/create', [
            'title' => 'Buat Jadwal Ujian Baru - Panel Guru',
            'mySubjects' => $mySubjects,
            'allClasses' => $allClasses,
            'questions' => $questions
        ]);
    }

    /**
     * Store new Exam (/guru/exams/store)
     */
    public function store(): void
    {
        if (!$this->checkCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid atau sesi telah kedaluwarsa.');
            $this->redirect('guru/exams/create');
            return;
        }

        $teacherId = $this->getTeacherId();

        $title = trim($this->request->string('title'));
        $subjectId = (int)$this->request->input('subject_id');
        $classId = (int)$this->request->input('class_id');
        $description = trim($this->request->string('description'));
        $duration = (int)$this->request->input('duration', 0);
        $startAt = trim($this->request->string('start_at'));
        $endAt = trim($this->request->string('end_at'));

        $randomQuestions = $this->request->input('random_questions') ? 1 : 0;
        $randomOptions = $this->request->input('random_options') ? 1 : 0;
        $aiProctoring = $this->request->input('ai_proctoring') ? 1 : 0;
        $dualCamera = $this->request->input('dual_camera') ? 1 : 0;
        $status = in_array($this->request->string('status'), ['draft', 'published', 'active', 'finished'], true) 
                    ? $this->request->string('status') 
                    : 'draft';

        $questionIds = $_POST['questions'] ?? [];

        // Validations
        if (empty($title)) {
            Session::setFlash('error', 'Judul ujian wajib diisi.');
            $this->redirect('guru/exams/create');
            return;
        }

        if ($subjectId <= 0) {
            Session::setFlash('error', 'Mata pelajaran wajib dipilih.');
            $this->redirect('guru/exams/create');
            return;
        }

        if ($classId <= 0) {
            Session::setFlash('error', 'Target kelas peserta ujian wajib dipilih.');
            $this->redirect('guru/exams/create');
            return;
        }

        if ($duration <= 0) {
            Session::setFlash('error', 'Durasi ujian harus lebih dari 0 menit.');
            $this->redirect('guru/exams/create');
            return;
        }

        if (empty($startAt) || empty($endAt)) {
            Session::setFlash('error', 'Waktu mulai dan waktu selesai ujian wajib diisi.');
            $this->redirect('guru/exams/create');
            return;
        }

        $startTime = strtotime($startAt);
        $endTime = strtotime($endAt);

        if (!$startTime || !$endTime || $endTime <= $startTime) {
            Session::setFlash('error', 'Waktu selesai ujian harus lebih lambat dari waktu mulai.');
            $this->redirect('guru/exams/create');
            return;
        }

        if (!is_array($questionIds) || count($questionIds) === 0) {
            Session::setFlash('error', 'Minimal harus memilih satu butir soal dari Bank Soal untuk ujian ini.');
            $this->redirect('guru/exams/create');
            return;
        }

        // Enforce Question Ownership Isolation
        foreach ($questionIds as $qId) {
            if (!$this->questionModel->isOwnedBy((int)$qId, $teacherId)) {
                http_response_code(403);
                die("Akses ditolak: Anda tidak memiliki izin atas butir soal ID {$qId}.");
            }
        }

        try {
            $examData = [
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'title' => $title,
                'description' => $description ?: null,
                'duration' => $duration,
                'start_at' => date('Y-m-d H:i:s', $startTime),
                'end_at' => date('Y-m-d H:i:s', $endTime),
                'random_questions' => $randomQuestions,
                'random_options' => $randomOptions,
                'ai_proctoring' => $aiProctoring,
                'dual_camera' => $dualCamera,
                'status' => $status
            ];

            $examId = $this->examModel->createWithQuestions($examData, $questionIds);

            audit_log('CREATE_EXAM', "Guru membuat ujian baru '{$title}' (ID: {$examId}) dengan " . count($questionIds) . " butir soal.", [
                'exam_id' => $examId,
                'title' => $title,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'questions_count' => count($questionIds)
            ]);

            if (in_array($status, ['published', 'active'], true)) {
                try {
                    (new \App\Services\Notification\NotificationService())->notifyStudentsForNewExam((int)$examId);
                } catch (\Throwable $e) {}
            }

            Session::setFlash('success', "Ujian '{$title}' berhasil dibuat dengan " . count($questionIds) . " butir soal.");
            $this->redirect('guru/exams/show?id=' . $examId);
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Gagal membuat ujian: ' . $e->getMessage());
            $this->redirect('guru/exams/create');
        }
    }

    /**
     * Show Exam Details (/guru/exams/show?id=X)
     */
    public function show(): void
    {
        $teacherId = $this->getTeacherId();
        $id = (int)$this->request->input('id', 0);

        if ($id <= 0 || !$this->examModel->isOwnedBy($id, $teacherId)) {
            http_response_code(403);
            die("Ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
        }

        $exam = $this->examModel->findWithDetails($id);
        if (!$exam) {
            Session::setFlash('error', 'Ujian tidak ditemukan.');
            $this->redirect('guru/exams');
            return;
        }

        $questions = $this->examModel->getExamQuestions($id);

        $this->view('guru/exams/show', [
            'title' => 'Detail Ujian: ' . $exam['title'],
            'exam' => $exam,
            'questions' => $questions
        ]);
    }

    /**
     * Edit Exam Form (/guru/exams/edit?id=X)
     */
    public function edit(): void
    {
        $teacherId = $this->getTeacherId();
        $id = (int)$this->request->input('id', 0);

        if ($id <= 0 || !$this->examModel->isOwnedBy($id, $teacherId)) {
            http_response_code(403);
            die("Ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
        }

        $exam = $this->examModel->findWithDetails($id);
        if (!$exam) {
            Session::setFlash('error', 'Ujian tidak ditemukan.');
            $this->redirect('guru/exams');
            return;
        }

        $mySubjects = $this->subjectModel->getByTeacherId($teacherId);
        if (empty($mySubjects)) {
            $mySubjects = $this->subjectModel->findAll('name', 'ASC');
        }
        $allClasses = $this->kelasModel->all();
        $questions = $this->questionModel->getAllByTeacherAndSubject($teacherId);
        $selectedQuestionIds = $this->examModel->getQuestionIds($id);

        $this->view('guru/exams/edit', [
            'title' => 'Edit Ujian: ' . $exam['title'],
            'exam' => $exam,
            'mySubjects' => $mySubjects,
            'allClasses' => $allClasses,
            'questions' => $questions,
            'selectedQuestionIds' => $selectedQuestionIds
        ]);
    }

    /**
     * Update Exam (/guru/exams/update)
     */
    public function update(): void
    {
        if (!$this->checkCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/exams');
            return;
        }

        $teacherId = $this->getTeacherId();
        $id = (int)$this->request->input('id', 0);

        if ($id <= 0 || !$this->examModel->isOwnedBy($id, $teacherId)) {
            http_response_code(403);
            die("Ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
        }

        $title = trim($this->request->string('title'));
        $subjectId = (int)$this->request->input('subject_id');
        $classId = (int)$this->request->input('class_id');
        $description = trim($this->request->string('description'));
        $duration = (int)$this->request->input('duration', 0);
        $startAt = trim($this->request->string('start_at'));
        $endAt = trim($this->request->string('end_at'));

        $randomQuestions = $this->request->input('random_questions') ? 1 : 0;
        $randomOptions = $this->request->input('random_options') ? 1 : 0;
        $aiProctoring = $this->request->input('ai_proctoring') ? 1 : 0;
        $dualCamera = $this->request->input('dual_camera') ? 1 : 0;
        $status = in_array($this->request->string('status'), ['draft', 'published', 'active', 'finished'], true) 
                    ? $this->request->string('status') 
                    : 'draft';

        $questionIds = $_POST['questions'] ?? [];

        if (empty($title)) {
            Session::setFlash('error', 'Judul ujian wajib diisi.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        if ($subjectId <= 0) {
            Session::setFlash('error', 'Mata pelajaran wajib dipilih.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        if ($classId <= 0) {
            Session::setFlash('error', 'Target kelas peserta ujian wajib dipilih.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        if ($duration <= 0) {
            Session::setFlash('error', 'Durasi ujian harus lebih dari 0 menit.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        if (empty($startAt) || empty($endAt)) {
            Session::setFlash('error', 'Waktu mulai dan selesai ujian wajib diisi.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        $startTime = strtotime($startAt);
        $endTime = strtotime($endAt);

        if (!$startTime || !$endTime || $endTime <= $startTime) {
            Session::setFlash('error', 'Waktu selesai ujian harus lebih lambat dari waktu mulai.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        if (!is_array($questionIds) || count($questionIds) === 0) {
            Session::setFlash('error', 'Minimal harus memilih satu butir soal dari Bank Soal.');
            $this->redirect('guru/exams/edit?id=' . $id);
            return;
        }

        // Validate question ownership
        foreach ($questionIds as $qId) {
            if (!$this->questionModel->isOwnedBy((int)$qId, $teacherId)) {
                http_response_code(403);
                die("Akses ditolak: Anda tidak memiliki izin atas butir soal ID {$qId}.");
            }
        }

        try {
            $examData = [
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'title' => $title,
                'description' => $description ?: null,
                'duration' => $duration,
                'start_at' => date('Y-m-d H:i:s', $startTime),
                'end_at' => date('Y-m-d H:i:s', $endTime),
                'random_questions' => $randomQuestions,
                'random_options' => $randomOptions,
                'ai_proctoring' => $aiProctoring,
                'dual_camera' => $dualCamera,
                'status' => $status
            ];

            $oldExam = $this->examModel->find($id);
            $this->examModel->updateWithQuestions($id, $examData, $questionIds);

            audit_log('UPDATE_EXAM', "Guru memperbarui data ujian '{$title}' (ID: {$id}).", [
                'exam_id' => $id,
                'title' => $title,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'status' => $status
            ]);

            if (in_array($status, ['published', 'active'], true)) {
                try {
                    $notifService = new \App\Services\Notification\NotificationService();
                    $isScheduleChanged = ($oldExam && (
                        $oldExam['start_at'] !== $examData['start_at'] || 
                        $oldExam['end_at'] !== $examData['end_at']
                    ));

                    if ($isScheduleChanged) {
                        $notifService->notifyExamScheduleChanged(
                            (int)$id,
                            (string)($oldExam['start_at'] ?? ''),
                            (string)$examData['start_at'],
                            (string)$examData['end_at']
                        );
                    } else {
                        $notifService->notifyStudentsForNewExam((int)$id);
                    }
                } catch (\Throwable $e) {}
            }

            Session::setFlash('success', "Ujian '{$title}' berhasil diperbarui.");
            $this->redirect('guru/exams/show?id=' . $id);
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Gagal memperbarui ujian: ' . $e->getMessage());
            $this->redirect('guru/exams/edit?id=' . $id);
        }
    }

    /**
     * Delete Exam (/guru/exams/delete)
     */
    public function delete(): void
    {
        if (!$this->checkCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/exams');
            return;
        }

        $teacherId = $this->getTeacherId();
        $id = (int)$this->request->input('id', 0);

        if ($id <= 0 || !$this->examModel->isOwnedBy($id, $teacherId)) {
            http_response_code(403);
            die("Ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
        }

        $exam = $this->examModel->findWithDetails($id);
        if ($exam) {
            $this->examModel->deleteExam($id);

            audit_log('DELETE_EXAM', "Guru menghapus ujian '{$exam['title']}' (ID: {$id}).", [
                'exam_id' => $id,
                'title' => $exam['title']
            ]);

            Session::setFlash('success', "Ujian '{$exam['title']}' berhasil dihapus.");
        }

        $this->redirect('guru/exams');
    }

    /**
     * Toggle or Update Status (/guru/exams/toggle-status)
     */
    public function toggleStatus(): void
    {
        if (!$this->checkCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/exams');
            return;
        }

        $teacherId = $this->getTeacherId();
        $id = (int)$this->request->input('id', 0);
        $status = trim($this->request->string('status'));

        if ($id <= 0 || !$this->examModel->isOwnedBy($id, $teacherId)) {
            http_response_code(403);
            die("Ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
        }

        $validStatuses = ['draft', 'published', 'active', 'finished'];
        if (!in_array($status, $validStatuses, true)) {
            Session::setFlash('error', 'Status ujian tidak valid.');
            $this->redirect('guru/exams');
            return;
        }

        $this->examModel->updateStatus($id, $status);

        if (in_array($status, ['published', 'active'], true)) {
            try {
                (new \App\Services\Notification\NotificationService())->notifyStudentsForNewExam((int)$id);
            } catch (\Throwable $e) {}
        }

        $statusLabels = [
            'draft' => 'Draft',
            'published' => 'Dipublikasikan (Published)',
            'active' => 'Sedang Berlangsung (Active)',
            'finished' => 'Selesai (Finished)'
        ];

        Session::setFlash('success', "Status ujian berhasil diubah menjadi {$statusLabels[$status]}.");
        $this->redirect($_SERVER['HTTP_REFERER'] ?? 'guru/exams');
    }

    /**
     * AJAX endpoint: Get questions for teacher filtered by subject
     */
    public function getQuestions(): void
    {
        $teacherId = $this->getTeacherId();
        $subjectId = $this->request->input('subject_id') ? (int)$this->request->input('subject_id') : null;

        $questions = $this->questionModel->getAllByTeacherAndSubject($teacherId, $subjectId);

        $this->json([
            'status' => 'success',
            'count' => count($questions),
            'data' => $questions
        ]);
    }
}
