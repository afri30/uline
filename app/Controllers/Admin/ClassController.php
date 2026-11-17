<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Kelas;
use App\Models\Student;
use Exception;

class ClassController extends Controller
{
    private Kelas $kelasModel;
    private Student $studentModel;

    public function __construct()
    {
        parent::__construct();
        $this->kelasModel = new Kelas();
        $this->studentModel = new Student();
    }

    /**
     * Display list of classes with student count
     */
    public function index(): void
    {
        $search = $this->request->string('q');
        $grade = $this->request->string('grade');

        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(c.`name` LIKE :search OR c.`academic_year` LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($grade) && is_numeric($grade)) {
            $whereClauses[] = "c.`grade` = :grade";
            $params[':grade'] = $grade;
        }

        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        $sql = "SELECT c.*, COUNT(s.id) as total_students
                FROM `classes` c
                LEFT JOIN `students` s ON c.id = s.class_id
                {$whereSql}
                GROUP BY c.id
                ORDER BY c.grade ASC, c.name ASC";

        $classes = $this->kelasModel->query($sql, $params);

        $this->view('admin/classes/index', [
            'title' => 'Manajemen Data Kelas - Super Admin',
            'classes' => $classes,
            'search' => $search,
            'grade' => $grade
        ]);
    }

    /**
     * Store new class
     */
    public function store(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/classes');
            return;
        }

        $name = $this->request->string('name');
        $grade = $this->request->string('grade');
        $academicYear = $this->request->string('academic_year', '2026/2027');

        if (empty($name) || empty($grade) || empty($academicYear)) {
            Session::setFlash('error', 'Nama Kelas, Tingkat, dan Tahun Ajaran wajib diisi.');
            $this->redirect('admin/classes');
            return;
        }

        try {
            $this->kelasModel->create([
                'name' => $name,
                'grade' => $grade,
                'academic_year' => $academicYear
            ]);

            Session::setFlash('success', "Kelas <strong>" . e($name) . "</strong> berhasil ditambahkan!");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menambahkan kelas: ' . $e->getMessage());
        }

        $this->redirect('admin/classes');
    }

    /**
     * Update class details
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/classes');
            return;
        }

        $id = (int)$this->request->input('id');
        $class = $this->kelasModel->findById($id);

        if (!$class) {
            Session::setFlash('error', 'Data kelas tidak ditemukan.');
            $this->redirect('admin/classes');
            return;
        }

        $name = $this->request->string('name');
        $grade = $this->request->string('grade');
        $academicYear = $this->request->string('academic_year');

        if (empty($name) || empty($grade) || empty($academicYear)) {
            Session::setFlash('error', 'Semua bidang wajib diisi.');
            $this->redirect('admin/classes');
            return;
        }

        try {
            $this->kelasModel->update($id, [
                'name' => $name,
                'grade' => $grade,
                'academic_year' => $academicYear
            ]);

            Session::setFlash('success', "Data kelas <strong>" . e($name) . "</strong> berhasil diperbarui!");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui kelas: ' . $e->getMessage());
        }

        $this->redirect('admin/classes');
    }

    /**
     * Delete class
     */
    public function delete(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/classes');
            return;
        }

        $id = (int)$this->request->input('id');
        $class = $this->kelasModel->findById($id);

        if (!$class) {
            Session::setFlash('error', 'Data kelas tidak ditemukan.');
            $this->redirect('admin/classes');
            return;
        }

        // Check if exams reference this class
        $hasExams = $this->kelasModel->query("SELECT COUNT(*) as total FROM `exams` WHERE `class_id` = :cid", [':cid' => $id]);
        if (!empty($hasExams[0]['total']) && (int)$hasExams[0]['total'] > 0) {
            Session::setFlash('error', "Kelas <strong>" . e($class['name']) . "</strong> tidak dapat dihapus karena masih digunakan pada data ujian.");
            $this->redirect('admin/classes');
            return;
        }

        try {
            $this->kelasModel->delete($id);
            Session::setFlash('success', "Kelas <strong>" . e($class['name']) . "</strong> berhasil dihapus.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus kelas: ' . $e->getMessage());
        }

        $this->redirect('admin/classes');
    }

    /**
     * View list of students enrolled in this class
     */
    public function students(int $id): void
    {
        $class = $this->kelasModel->findById($id);
        if (!$class) {
            Session::setFlash('error', 'Kelas tidak ditemukan.');
            $this->redirect('admin/classes');
            return;
        }

        $sql = "SELECT s.*, u.name, u.email, u.username, u.status
                FROM `students` s
                JOIN `users` u ON s.user_id = u.id
                WHERE s.class_id = :cid
                ORDER BY u.name ASC";
        $students = $this->studentModel->query($sql, [':cid' => $id]);

        $this->view('admin/classes/students', [
            'title' => "Daftar Siswa Kelas " . e($class['name']) . " - Super Admin",
            'class' => $class,
            'students' => $students
        ]);
    }
}
