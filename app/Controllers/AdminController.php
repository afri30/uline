<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Kelas;
use App\Models\Subject;

class AdminController extends Controller
{
    private User $userModel;
    private Teacher $teacherModel;
    private Student $studentModel;
    private Kelas $kelasModel;
    private Subject $subjectModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->teacherModel = new Teacher();
        $this->studentModel = new Student();
        $this->kelasModel = new Kelas();
        $this->subjectModel = new Subject();
    }

    /**
     * Super Admin Dashboard (/admin/dashboard)
     */
    public function dashboard(): void
    {
        $user = auth();

        $stats = [
            'total_users' => $this->userModel->count(),
            'total_teachers' => $this->teacherModel->count(),
            'total_students' => $this->studentModel->count(),
            'total_classes' => $this->kelasModel->count(),
            'total_subjects' => $this->subjectModel->count(),
        ];

        $recentUsers = $this->userModel->query("
            SELECT id, name, username, email, role, status, created_at 
            FROM users 
            ORDER BY id DESC 
            LIMIT 10
        ");

        $this->view('admin/dashboard', [
            'title' => 'Dashboard Super Administrator - AI Online Exam',
            'user' => $user,
            'stats' => $stats,
            'recentUsers' => $recentUsers
        ]);
    }
}
