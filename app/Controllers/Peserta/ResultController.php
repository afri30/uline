<?php

declare(strict_types=1);

namespace App\Controllers\Peserta;

use App\Core\Controller;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Core\Session;

class ResultController extends Controller
{
    private Student $studentModel;
    private Exam $examModel;
    private ExamSession $sessionModel;

    public function __construct()
    {
        parent::__construct();
        $this->studentModel = new Student();
        $this->examModel = new Exam();
        $this->sessionModel = new ExamSession();
    }

    /**
     * Get active student model record
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
            die("Akun Anda belum terhubung dengan data profil Peserta Didik.");
        }
        return $student;
    }

    /**
     * Display list of all exam results for the logged in student
     */
    public function index(): void
    {
        $student = $this->getStudent();
        $studentId = (int)$student['id'];

        $history = $this->sessionModel->getStudentHistory($studentId);

        // Filter search if given
        $search = $this->request->get('search') ? trim((string)$this->request->get('search')) : null;
        if ($search) {
            $history = array_filter($history, function ($item) use ($search) {
                return (stripos($item['exam_title'], $search) !== false) ||
                       (stripos($item['subject_name'], $search) !== false) ||
                       (stripos($item['subject_code'], $search) !== false);
            });
        }

        // Summary calculations
        $totalExams = count($history);
        $completedExams = 0;
        $totalGrades = 0;
        $highestGrade = 0;

        foreach ($history as $h) {
            if ($h['session_status'] === 'finished' || $h['session_status'] === 'expired') {
                $completedExams++;
                $totalGrades += (float)$h['grade'];
                if ($h['grade'] > $highestGrade) {
                    $highestGrade = (float)$h['grade'];
                }
            }
        }

        $avgGrade = $completedExams > 0 ? round($totalGrades / $completedExams, 1) : 0;

        $this->view('peserta/results/index', [
            'title' => 'Riwayat & Hasil Ujian Saya',
            'student' => $student,
            'history' => $history,
            'search' => $search,
            'stats' => [
                'total_exams' => $totalExams,
                'completed_exams' => $completedExams,
                'avg_grade' => $avgGrade,
                'highest_grade' => $highestGrade
            ]
        ]);
    }

    /**
     * Display question breakdown and AI Tutor for student's finished exam
     */
    public function show($examId): void
    {
        $student = $this->getStudent();
        $studentId = (int)$student['id'];
        $examId = (int)$examId;

        $exam = $this->examModel->findWithDetails($examId);
        if (!$exam) {
            Session::flash('error', 'Data ujian tidak ditemukan.');
            redirect('peserta/results');
        }

        $breakdown = $this->sessionModel->getStudentExamAnswerBreakdown($examId, $studentId);
        if (!$breakdown) {
            Session::flash('error', 'Anda belum menyelesaikan sesi ujian ini.');
            redirect('peserta/results');
        }

        $this->view('peserta/results/show', [
            'title' => 'Pembahasan Ujian: ' . $exam['title'],
            'student' => $student,
            'exam' => $exam,
            'session' => $breakdown['session'],
            'summary' => $breakdown['summary'],
            'questions' => $breakdown['questions']
        ]);
    }
}
