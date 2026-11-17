<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Subject;
use App\Models\Teacher;
use Exception;

class SubjectController extends Controller
{
    private Subject $subjectModel;
    private Teacher $teacherModel;

    public function __construct()
    {
        parent::__construct();
        $this->subjectModel = new Subject();
        $this->teacherModel = new Teacher();
    }

    /**
     * Display list of subjects with assigned teachers
     */
    public function index(): void
    {
        $search = $this->request->string('q');

        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(s.`code` LIKE :search OR s.`name` LIKE :search OR u.`name` LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        $sql = "SELECT s.*, t.nip, u.name as teacher_name
                FROM `subjects` s
                LEFT JOIN `teachers` t ON s.teacher_id = t.id
                LEFT JOIN `users` u ON t.user_id = u.id
                {$whereSql}
                ORDER BY s.`code` ASC";

        $subjects = $this->subjectModel->query($sql, $params);
        $teachers = $this->teacherModel->getAllWithUsers();

        $this->view('admin/subjects/index', [
            'title' => 'Manajemen Mata Pelajaran - Super Admin',
            'subjects' => $subjects,
            'teachers' => $teachers,
            'search' => $search
        ]);
    }

    /**
     * Store new subject
     */
    public function store(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/subjects');
            return;
        }

        $code = strtoupper($this->request->string('code'));
        $name = $this->request->string('name');
        $description = $this->request->string('description');
        $teacherId = (int)$this->request->input('teacher_id');

        if (empty($code) || empty($name)) {
            Session::setFlash('error', 'Kode Mapel dan Nama Mata Pelajaran wajib diisi.');
            $this->redirect('admin/subjects');
            return;
        }

        if ($this->subjectModel->findWhere('code', $code)) {
            Session::setFlash('error', "Kode mata pelajaran '{$code}' sudah terdaftar.");
            $this->redirect('admin/subjects');
            return;
        }

        try {
            $this->subjectModel->create([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'teacher_id' => $teacherId > 0 ? $teacherId : null
            ]);

            Session::setFlash('success', "Mata Pelajaran <strong>" . e($name) . "</strong> ({$code}) berhasil ditambahkan!");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menambahkan mata pelajaran: ' . $e->getMessage());
        }

        $this->redirect('admin/subjects');
    }

    /**
     * Update subject details
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/subjects');
            return;
        }

        $id = (int)$this->request->input('id');
        $subject = $this->subjectModel->findById($id);

        if (!$subject) {
            Session::setFlash('error', 'Data mata pelajaran tidak ditemukan.');
            $this->redirect('admin/subjects');
            return;
        }

        $code = strtoupper($this->request->string('code'));
        $name = $this->request->string('name');
        $description = $this->request->string('description');
        $teacherId = (int)$this->request->input('teacher_id');

        if (empty($code) || empty($name)) {
            Session::setFlash('error', 'Kode Mapel dan Nama Mata Pelajaran wajib diisi.');
            $this->redirect('admin/subjects');
            return;
        }

        $existing = $this->subjectModel->query("SELECT id FROM subjects WHERE code = :c AND id != :id", [
            ':c' => $code,
            ':id' => $id
        ]);
        if (!empty($existing)) {
            Session::setFlash('error', "Kode mata pelajaran '{$code}' sudah digunakan pada mapel lain.");
            $this->redirect('admin/subjects');
            return;
        }

        try {
            $this->subjectModel->update($id, [
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'teacher_id' => $teacherId > 0 ? $teacherId : null
            ]);

            Session::setFlash('success', "Data mata pelajaran <strong>" . e($name) . "</strong> berhasil diperbarui!");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui mata pelajaran: ' . $e->getMessage());
        }

        $this->redirect('admin/subjects');
    }

    /**
     * Delete subject
     */
    public function delete(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/subjects');
            return;
        }

        $id = (int)$this->request->input('id');
        $subject = $this->subjectModel->findById($id);

        if (!$subject) {
            Session::setFlash('error', 'Data mata pelajaran tidak ditemukan.');
            $this->redirect('admin/subjects');
            return;
        }

        // Prevent deletion if exams use this subject
        $hasExams = $this->subjectModel->query("SELECT COUNT(*) as total FROM `exams` WHERE `subject_id` = :sid", [':sid' => $id]);
        if (!empty($hasExams[0]['total']) && (int)$hasExams[0]['total'] > 0) {
            Session::setFlash('error', "Mata pelajaran <strong>" . e($subject['name']) . "</strong> tidak dapat dihapus karena masih digunakan pada data ujian.");
            $this->redirect('admin/subjects');
            return;
        }

        try {
            $this->subjectModel->delete($id);
            Session::setFlash('success', "Mata Pelajaran <strong>" . e($subject['name']) . "</strong> berhasil dihapus.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus mata pelajaran: ' . $e->getMessage());
        }

        $this->redirect('admin/subjects');
    }
}
