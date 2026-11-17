<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Kelas;
use App\Models\Student;
use App\Models\Question;
use App\Models\Exam;

class GuruController extends Controller
{
    private Teacher $teacherModel;
    private Subject $subjectModel;
    private Kelas $kelasModel;
    private Student $studentModel;
    private Question $questionModel;
    private Exam $examModel;

    public function __construct()
    {
        parent::__construct();
        $this->teacherModel = new Teacher();
        $this->subjectModel = new Subject();
        $this->kelasModel = new Kelas();
        $this->studentModel = new Student();
        $this->questionModel = new Question();
        $this->examModel = new Exam();
    }

    /**
     * Guru Dashboard (/guru/dashboard)
     */
    public function dashboard(): void
    {
        $user = auth();
        $teacher = $this->teacherModel->findByUserId((int)($user['id'] ?? 0));

        $teacherId = $teacher ? (int)$teacher['id'] : 0;
        $mySubjects = $teacherId ? $this->subjectModel->getByTeacherId($teacherId) : [];
        $totalClasses = $this->kelasModel->count();
        $totalStudents = $this->studentModel->count();
        $totalQuestions = $teacherId ? $this->questionModel->countByTeacher($teacherId) : 0;
        $examStats = $teacherId ? $this->examModel->getTeacherStats($teacherId) : ['total' => 0, 'draft' => 0, 'published' => 0, 'active' => 0, 'finished' => 0];

        $this->view('guru/dashboard', [
            'title' => 'Dashboard Guru Penguji - AI Online Exam',
            'user' => $user,
            'teacher' => $teacher,
            'mySubjects' => $mySubjects,
            'totalClasses' => $totalClasses,
            'totalStudents' => $totalStudents,
            'totalQuestions' => $totalQuestions,
            'examStats' => $examStats
        ]);
    }

    /**
     * Jadwal Ujian Placeholder
     */
    public function exams(): void
    {
        $this->view('guru/exams/index', ['title' => 'Manajemen Jadwal Ujian - Panel Guru']);
    }

    /**
     * Peserta Ujian Placeholder
     */
    public function students(): void
    {
        $this->view('guru/students/index', ['title' => 'Daftar Peserta Ujian - Panel Guru']);
    }

    /**
     * Hasil Ujian Placeholder
     */
    public function results(): void
    {
        $this->view('guru/results/index', ['title' => 'Hasil & Analisis Nilai - Panel Guru']);
    }

    /**
     * Monitoring Placeholder
     */
    public function monitoring(): void
    {
        $this->view('guru/monitoring/index', ['title' => 'Live Monitoring Ujian - Panel Guru']);
    }
}

