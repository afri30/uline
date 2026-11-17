<?php

declare(strict_types=1);

namespace App\Controllers\Guru;

use App\Core\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Kelas;
use App\Core\Session;

class ResultController extends Controller
{
    private Exam $examModel;
    private ExamSession $sessionModel;
    private Teacher $teacherModel;
    private Subject $subjectModel;
    private Kelas $kelasModel;

    public function __construct()
    {
        parent::__construct();
        $this->examModel = new Exam();
        $this->sessionModel = new ExamSession();
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
     * Display list of exams created by the teacher with results summary
     */
    public function index(): void
    {
        $teacherId = $this->getTeacherId();

        $filters = [
            'subject_id' => $this->request->get('subject_id') ? (int)$this->request->get('subject_id') : null,
            'class_id' => $this->request->get('class_id') ? (int)$this->request->get('class_id') : null,
            'status' => $this->request->get('status') ? trim((string)$this->request->get('status')) : null,
            'search' => $this->request->get('search') ? trim((string)$this->request->get('search')) : null
        ];

        $exams = $this->sessionModel->getTeacherExamsWithResultSummary($teacherId, $filters);
        $subjects = $this->subjectModel->all();
        $classes = $this->kelasModel->all();

        // Calculate overall analytics
        $totalExams = count($exams);
        $totalParticipants = 0;
        $totalCompleted = 0;
        foreach ($exams as $e) {
            $totalParticipants += (int)$e['total_participants'];
            $totalCompleted += (int)$e['finished_participants'];
        }

        $this->view('guru/results/index', [
            'title' => 'Hasil & Penilaian Ujian',
            'exams' => $exams,
            'subjects' => $subjects,
            'classes' => $classes,
            'filters' => $filters,
            'stats' => [
                'total_exams' => $totalExams,
                'total_participants' => $totalParticipants,
                'total_completed' => $totalCompleted
            ]
        ]);
    }

    /**
     * Display list of participants, scores, and rankings for a specific exam
     */
    public function show($examId): void
    {
        $examId = (int)$examId;
        $teacherId = $this->getTeacherId();

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            Session::flash('error', 'Data ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            redirect('guru/results');
        }

        $filters = [
            'status' => $this->request->get('status') ? trim((string)$this->request->get('status')) : null,
            'search' => $this->request->get('search') ? trim((string)$this->request->get('search')) : null
        ];
        $sort = $this->request->get('sort') ? trim((string)$this->request->get('sort')) : 'rank_asc';

        $participants = $this->sessionModel->getExamParticipants($examId, $filters, $sort);
        $stats = $this->sessionModel->getExamStatsSummary($examId);

        $this->view('guru/results/show', [
            'title' => 'Hasil Peserta: ' . $exam['title'],
            'exam' => $exam,
            'participants' => $participants,
            'stats' => $stats,
            'filters' => $filters,
            'sort' => $sort
        ]);
    }

    /**
     * Display question-by-question answer sheet of a student for an exam
     */
    public function studentDetail($examId, $studentId): void
    {
        $examId = (int)$examId;
        $studentId = (int)$studentId;
        $teacherId = $this->getTeacherId();

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            Session::flash('error', 'Data ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            redirect('guru/results');
        }

        $breakdown = $this->sessionModel->getStudentExamAnswerBreakdown($examId, $studentId);
        if (!$breakdown) {
            Session::flash('error', 'Peserta belum memulai atau tidak memiliki data ujian ini.');
            redirect('guru/results/' . $examId);
        }

        $this->view('guru/results/student_detail', [
            'title' => 'Lembar Hasil Peserta: ' . ($breakdown['session']['student_name'] ?? 'Peserta'),
            'exam' => $exam,
            'session' => $breakdown['session'],
            'summary' => $breakdown['summary'],
            'questions' => $breakdown['questions']
        ]);
    }

    /**
     * Export exam results to CSV
     */
    public function exportCsv(?int $examId = null): void
    {
        $teacherId = $this->getTeacherId();
        
        $examId = $examId ?: (int)$this->request->get('exam_id');
        if (!$examId) {
            Session::flash('error', 'ID Ujian tidak valid untuk diexport.');
            redirect('guru/results');
        }

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam || (int)$exam['teacher_id'] !== $teacherId) {
            Session::flash('error', 'Data ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            redirect('guru/results');
        }

        $participants = $this->sessionModel->getExamParticipants($examId, [], 'rank_asc');

        $safeExamTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$exam['title']);
        $filename = 'hasil_ujian_' . $examId . '_' . $safeExamTitle . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Output UTF-8 BOM for Microsoft Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Header metadata
        fputcsv($output, ['LAPORAN HASIL PENILAIAN UJIAN']);
        fputcsv($output, ['Judul Ujian', $exam['title']]);
        fputcsv($output, ['Mata Pelajaran', $exam['subject_name'] . ' (' . $exam['subject_code'] . ')']);
        fputcsv($output, ['Kelas', $exam['class_name']]);
        fputcsv($output, ['Durasi', $exam['duration'] . ' Menit']);
        fputcsv($output, ['Waktu Export', date('d-m-Y H:i:s')]);
        fputcsv($output, []); // Empty line

        // Table headers
        fputcsv($output, [
            'Peringkat',
            'NIS',
            'Nama Peserta',
            'Kelas',
            'Email',
            'Total Soal',
            'Dijawab',
            'Benar',
            'Salah',
            'Tidak Dijawab',
            'Total Skor',
            'Nilai (Skala 0-100)',
            'Status Sesi',
            'Waktu Mulai',
            'Waktu Selesai',
            'Durasi Pengerjaan (Menit:Detik)'
        ]);

        foreach ($participants as $p) {
            $minutes = floor($p['time_spent_seconds'] / 60);
            $seconds = $p['time_spent_seconds'] % 60;
            $durationFormatted = sprintf('%02d:%02d', $minutes, $seconds);

            fputcsv($output, [
                $p['rank'],
                $p['student_nis'],
                $p['student_name'],
                $p['class_name'],
                $p['student_email'],
                $p['total_questions'],
                $p['answered_count'],
                $p['correct_count'],
                $p['incorrect_count'],
                $p['unanswered_count'],
                $p['earned_score'],
                number_format($p['grade'], 1, ',', '.'),
                strtoupper($p['session_status']),
                $p['started_at'] ?? '-',
                $p['finished_at'] ?? '-',
                $durationFormatted
            ]);
        }

        fclose($output);
        exit;
    }
}
