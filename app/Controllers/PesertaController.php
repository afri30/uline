<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ExamSession;

class PesertaController extends Controller
{
    private Student $studentModel;
    private Subject $subjectModel;
    private Exam $examModel;
    private ExamSession $sessionModel;

    public function __construct()
    {
        parent::__construct();
        $this->studentModel = new Student();
        $this->subjectModel = new Subject();
        $this->examModel = new Exam();
        $this->sessionModel = new ExamSession();
    }

    /**
     * Peserta Dashboard (/peserta/dashboard)
     */
    public function dashboard(): void
    {
        $user = auth();
        $student = $this->studentModel->findByUserIdWithClass((int)($user['id'] ?? 0));
        $allSubjects = $this->subjectModel->getAllWithTeachers();

        $classId = $student ? (int)$student['class_id'] : 0;
        $studentId = $student ? (int)$student['id'] : 0;

        $availableExams = ($classId && $studentId) ? $this->examModel->getAvailableForStudentClass($classId, $studentId) : [];
        $history = $studentId ? $this->sessionModel->getStudentHistory($studentId) : [];

        $this->view('peserta/dashboard', [
            'title' => 'Dashboard Peserta Ujian - AI Online Exam',
            'user' => $user,
            'student' => $student,
            'subjects' => $allSubjects,
            'availableExams' => $availableExams,
            'history' => $history
        ]);
    }
}
