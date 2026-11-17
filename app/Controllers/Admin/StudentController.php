<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Student;
use App\Models\Kelas;
use Exception;

class StudentController extends Controller
{
    private User $userModel;
    private Student $studentModel;
    private Kelas $kelasModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->studentModel = new Student();
        $this->kelasModel = new Kelas();
    }

    /**
     * Display list of students with search, class filter & pagination
     */
    public function index(): void
    {
        $search = $this->request->string('q');
        $classId = $this->request->string('class_id');
        $status = $this->request->string('status');
        $page = max(1, (int)$this->request->input('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $whereClauses = ["u.`role` = 'PESERTA'"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(u.`name` LIKE :search OR u.`username` LIKE :search OR u.`email` LIKE :search OR s.`nis` LIKE :search OR s.`nisn` LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($classId) && is_numeric($classId)) {
            $whereClauses[] = "s.`class_id` = :class_id";
            $params[':class_id'] = (int)$classId;
        }

        if (!empty($status) && in_array($status, ['active', 'inactive'], true)) {
            $whereClauses[] = "u.`status` = :status";
            $params[':status'] = $status;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "SELECT COUNT(s.id) as total 
                     FROM `students` s 
                     JOIN `users` u ON s.user_id = u.id 
                     LEFT JOIN `classes` c ON s.class_id = c.id
                     {$whereSql}";
        $totalRes = $this->studentModel->query($countSql, $params);
        $totalItems = (int)($totalRes[0]['total'] ?? 0);

        // Fetch paginated students
        $dataSql = "SELECT s.*, u.name, u.username, u.email, u.status, u.created_at as user_created_at,
                           c.name as class_name, c.grade
                    FROM `students` s
                    JOIN `users` u ON s.user_id = u.id
                    LEFT JOIN `classes` c ON s.class_id = c.id
                    {$whereSql}
                    ORDER BY u.`name` ASC
                    LIMIT {$perPage} OFFSET {$offset}";
        $students = $this->studentModel->query($dataSql, $params);

        $classes = $this->kelasModel->findAll('name', 'ASC');

        // Statistics for summary metric cards
        $statsRaw = $this->studentModel->query("
            SELECT 
                COUNT(s.id) as total,
                SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN s.class_id IS NOT NULL THEN 1 ELSE 0 END) as with_class,
                SUM(CASE WHEN s.class_id IS NULL THEN 1 ELSE 0 END) as without_class
            FROM `students` s
            JOIN `users` u ON s.user_id = u.id
        ");
        $studentStats = $statsRaw[0] ?? [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'with_class' => 0,
            'without_class' => 0
        ];

        $this->view('admin/students/index', [
            'title' => 'Manajemen Peserta Didik - Super Admin',
            'students' => $students,
            'classes' => $classes,
            'stats' => $studentStats,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'classId' => $classId,
            'status' => $status
        ]);
    }

    /**
     * Store new student & user account
     */
    public function store(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/students');
            return;
        }

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $password = $this->request->string('password', 'password123');
        $classId = (int)$this->request->input('class_id');
        $nis = $this->request->string('nis');
        $nisn = $this->request->string('nisn');
        $phone = $this->request->string('phone');
        $gender = $this->request->string('gender', 'L');
        $status = $this->request->string('status', 'active');

        // Validation
        if (empty($name) || empty($username) || empty($email) || empty($nis)) {
            Session::setFlash('error', 'Nama, Username, Email, dan NIS wajib diisi.');
            $this->redirect('admin/students');
            return;
        }

        if (strlen($password) < 6) {
            Session::setFlash('error', 'Password minimal 6 karakter.');
            $this->redirect('admin/students');
            return;
        }

        if ($this->userModel->findWhere('username', $username)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan.");
            $this->redirect('admin/students');
            return;
        }

        if ($this->userModel->findWhere('email', $email)) {
            Session::setFlash('error', "Email '{$email}' sudah terdaftar.");
            $this->redirect('admin/students');
            return;
        }

        if ($this->studentModel->findWhere('nis', $nis)) {
            Session::setFlash('error', "NIS '{$nis}' sudah terdaftar untuk siswa lain.");
            $this->redirect('admin/students');
            return;
        }

        if (!empty($nisn) && $this->studentModel->findWhere('nisn', $nisn)) {
            Session::setFlash('error', "NISN '{$nisn}' sudah terdaftar untuk siswa lain.");
            $this->redirect('admin/students');
            return;
        }

        try {
            $this->userModel->beginTransaction();

            $userId = $this->userModel->createWithPassword([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'PESERTA',
                'status' => $status === 'inactive' ? 'inactive' : 'active'
            ]);

            $this->studentModel->create([
                'user_id' => $userId,
                'class_id' => $classId > 0 ? $classId : null,
                'nis' => $nis,
                'nisn' => !empty($nisn) ? $nisn : null,
                'phone' => $phone,
                'gender' => in_array($gender, ['L', 'P'], true) ? $gender : 'L'
            ]);

            $this->userModel->commit();
            Session::setFlash('success', "Peserta Didik <strong>" . e($name) . "</strong> (NIS: " . e($nis) . ") berhasil ditambahkan!");
        } catch (Exception $e) {
            $this->userModel->rollBack();
            Session::setFlash('error', 'Gagal menambahkan peserta didik: ' . $e->getMessage());
        }

        $this->redirect('admin/students');
    }

    /**
     * Update student & user data
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/students');
            return;
        }

        $studentId = (int)$this->request->input('id');
        $student = $this->studentModel->findById($studentId);

        if (!$student) {
            Session::setFlash('error', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
            return;
        }

        $userId = (int)$student['user_id'];
        $user = $this->userModel->findById($userId);

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $password = $this->request->string('password');
        $classId = (int)$this->request->input('class_id');
        $nis = $this->request->string('nis');
        $nisn = $this->request->string('nisn');
        $phone = $this->request->string('phone');
        $gender = $this->request->string('gender', 'L');
        $status = $this->request->string('status', 'active');

        if (empty($name) || empty($username) || empty($email) || empty($nis)) {
            Session::setFlash('error', 'Nama, Username, Email, dan NIS wajib diisi.');
            $this->redirect('admin/students');
            return;
        }

        // Unique checks
        $checkUser = $this->userModel->query("SELECT id FROM users WHERE username = :u AND id != :id", [':u' => $username, ':id' => $userId]);
        if (!empty($checkUser)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan akun lain.");
            $this->redirect('admin/students');
            return;
        }

        $checkEmail = $this->userModel->query("SELECT id FROM users WHERE email = :e AND id != :id", [':e' => $email, ':id' => $userId]);
        if (!empty($checkEmail)) {
            Session::setFlash('error', "Email '{$email}' sudah digunakan akun lain.");
            $this->redirect('admin/students');
            return;
        }

        $checkNis = $this->studentModel->query("SELECT id FROM students WHERE nis = :n AND id != :id", [':n' => $nis, ':id' => $studentId]);
        if (!empty($checkNis)) {
            Session::setFlash('error', "NIS '{$nis}' sudah digunakan siswa lain.");
            $this->redirect('admin/students');
            return;
        }

        if (!empty($nisn)) {
            $checkNisn = $this->studentModel->query("SELECT id FROM students WHERE nisn = :n AND id != :id", [':n' => $nisn, ':id' => $studentId]);
            if (!empty($checkNisn)) {
                Session::setFlash('error', "NISN '{$nisn}' sudah digunakan siswa lain.");
                $this->redirect('admin/students');
                return;
            }
        }

        if (!empty($password) && strlen($password) < 6) {
            Session::setFlash('error', 'Password baru minimal 6 karakter.');
            $this->redirect('admin/students');
            return;
        }

        try {
            $this->userModel->beginTransaction();

            $userData = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'status' => $status === 'inactive' ? 'inactive' : 'active'
            ];

            if (!empty($password)) {
                $userData['password'] = password_hash($password, PASSWORD_BCRYPT);
            }

            $this->userModel->update($userId, $userData);

            $this->studentModel->update($studentId, [
                'class_id' => $classId > 0 ? $classId : null,
                'nis' => $nis,
                'nisn' => !empty($nisn) ? $nisn : null,
                'phone' => $phone,
                'gender' => in_array($gender, ['L', 'P'], true) ? $gender : 'L'
            ]);

            $this->userModel->commit();
            Session::setFlash('success', "Data peserta didik <strong>" . e($name) . "</strong> berhasil diperbarui!");
        } catch (Exception $e) {
            $this->userModel->rollBack();
            Session::setFlash('error', 'Gagal memperbarui data peserta didik: ' . $e->getMessage());
        }

        $this->redirect('admin/students');
    }

    /**
     * Delete student and user
     */
    public function delete(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/students');
            return;
        }

        $studentId = (int)$this->request->input('id');
        $student = $this->studentModel->findById($studentId);

        if (!$student) {
            Session::setFlash('error', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
            return;
        }

        $userId = (int)$student['user_id'];
        $user = $this->userModel->findById($userId);

        try {
            $this->userModel->delete($userId);
            Session::setFlash('success', "Data peserta didik <strong>" . e($user['name'] ?? 'Siswa') . "</strong> berhasil dihapus.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus data peserta didik: ' . $e->getMessage());
        }

        $this->redirect('admin/students');
    }

    /**
     * Toggle status active <-> inactive
     */
    public function toggleStatus(): void
    {
        $studentId = (int)$this->request->input('id');
        $student = $this->studentModel->findById($studentId);

        if (!$student) {
            Session::setFlash('error', 'Peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
            return;
        }

        $user = $this->userModel->findById((int)$student['user_id']);
        if (!$user) {
            Session::setFlash('error', 'Akun pengguna siswa tidak ditemukan.');
            $this->redirect('admin/students');
            return;
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        $this->userModel->update((int)$user['id'], ['status' => $newStatus]);

        $statusLabel = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        Session::setFlash('success', "Status akun peserta didik <strong>" . e($user['name']) . "</strong> berhasil {$statusLabel}.");
        $this->redirect('admin/students');
    }
}
