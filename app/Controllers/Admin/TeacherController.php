<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Teacher;
use Exception;

class TeacherController extends Controller
{
    private User $userModel;
    private Teacher $teacherModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->teacherModel = new Teacher();
    }

    /**
     * Display list of teachers
     */
    public function index(): void
    {
        $search = $this->request->string('q');
        $status = $this->request->string('status');
        $page = max(1, (int)$this->request->input('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $whereClauses = ["u.`role` = 'GURU'"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(u.`name` LIKE :search OR u.`username` LIKE :search OR u.`email` LIKE :search OR t.`nip` LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($status) && in_array($status, ['active', 'inactive'], true)) {
            $whereClauses[] = "u.`status` = :status";
            $params[':status'] = $status;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "SELECT COUNT(t.id) as total 
                     FROM `teachers` t 
                     JOIN `users` u ON t.user_id = u.id 
                     {$whereSql}";
        $totalRes = $this->teacherModel->query($countSql, $params);
        $totalItems = (int)($totalRes[0]['total'] ?? 0);

        // Fetch paginated
        $dataSql = "SELECT t.*, u.name, u.username, u.email, u.status, u.created_at as user_created_at,
                           (SELECT COUNT(id) FROM subjects WHERE teacher_id = t.id) as total_subjects
                    FROM `teachers` t
                    JOIN `users` u ON t.user_id = u.id
                    {$whereSql}
                    ORDER BY u.`name` ASC
                    LIMIT {$perPage} OFFSET {$offset}";
        $teachers = $this->teacherModel->query($dataSql, $params);

        $this->view('admin/teachers/index', [
            'title' => 'Manajemen Guru Penguji - Super Admin',
            'teachers' => $teachers,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'status' => $status
        ]);
    }

    /**
     * Store new teacher & user
     */
    public function store(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/teachers');
            return;
        }

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $password = $this->request->string('password', 'password123');
        $nip = $this->request->string('nip');
        $phone = $this->request->string('phone');
        $gender = $this->request->string('gender', 'L');
        $status = $this->request->string('status', 'active');

        // Validation
        if (empty($name) || empty($username) || empty($email) || empty($nip)) {
            Session::setFlash('error', 'Nama, Username, Email, dan NIP wajib diisi.');
            $this->redirect('admin/teachers');
            return;
        }

        if (strlen($password) < 6) {
            Session::setFlash('error', 'Password minimal 6 karakter.');
            $this->redirect('admin/teachers');
            return;
        }

        if ($this->userModel->findWhere('username', $username)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan.");
            $this->redirect('admin/teachers');
            return;
        }

        if ($this->userModel->findWhere('email', $email)) {
            Session::setFlash('error', "Email '{$email}' sudah terdaftar.");
            $this->redirect('admin/teachers');
            return;
        }

        if ($this->teacherModel->findWhere('nip', $nip)) {
            Session::setFlash('error', "NIP '{$nip}' sudah terdaftar untuk guru lain.");
            $this->redirect('admin/teachers');
            return;
        }

        try {
            $this->userModel->beginTransaction();

            $userId = $this->userModel->createWithPassword([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'GURU',
                'status' => $status === 'inactive' ? 'inactive' : 'active'
            ]);

            $this->teacherModel->create([
                'user_id' => $userId,
                'nip' => $nip,
                'phone' => $phone,
                'gender' => in_array($gender, ['L', 'P'], true) ? $gender : 'L'
            ]);

            $this->userModel->commit();
            Session::setFlash('success', "Guru <strong>" . e($name) . "</strong> (NIP: " . e($nip) . ") berhasil ditambahkan!");
        } catch (Exception $e) {
            $this->userModel->rollBack();
            Session::setFlash('error', 'Gagal menambahkan guru: ' . $e->getMessage());
        }

        $this->redirect('admin/teachers');
    }

    /**
     * Update teacher & user data
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/teachers');
            return;
        }

        $teacherId = (int)$this->request->input('id');
        $teacher = $this->teacherModel->findById($teacherId);

        if (!$teacher) {
            Session::setFlash('error', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
            return;
        }

        $userId = (int)$teacher['user_id'];
        $user = $this->userModel->findById($userId);

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $password = $this->request->string('password');
        $nip = $this->request->string('nip');
        $phone = $this->request->string('phone');
        $gender = $this->request->string('gender', 'L');
        $status = $this->request->string('status', 'active');

        if (empty($name) || empty($username) || empty($email) || empty($nip)) {
            Session::setFlash('error', 'Nama, Username, Email, dan NIP wajib diisi.');
            $this->redirect('admin/teachers');
            return;
        }

        // Unique checks
        $checkUser = $this->userModel->query("SELECT id FROM users WHERE username = :u AND id != :id", [':u' => $username, ':id' => $userId]);
        if (!empty($checkUser)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan akun lain.");
            $this->redirect('admin/teachers');
            return;
        }

        $checkEmail = $this->userModel->query("SELECT id FROM users WHERE email = :e AND id != :id", [':e' => $email, ':id' => $userId]);
        if (!empty($checkEmail)) {
            Session::setFlash('error', "Email '{$email}' sudah digunakan akun lain.");
            $this->redirect('admin/teachers');
            return;
        }

        $checkNip = $this->teacherModel->query("SELECT id FROM teachers WHERE nip = :n AND id != :id", [':n' => $nip, ':id' => $teacherId]);
        if (!empty($checkNip)) {
            Session::setFlash('error', "NIP '{$nip}' sudah digunakan guru lain.");
            $this->redirect('admin/teachers');
            return;
        }

        if (!empty($password) && strlen($password) < 6) {
            Session::setFlash('error', 'Password baru minimal 6 karakter.');
            $this->redirect('admin/teachers');
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

            $this->teacherModel->update($teacherId, [
                'nip' => $nip,
                'phone' => $phone,
                'gender' => in_array($gender, ['L', 'P'], true) ? $gender : 'L'
            ]);

            $this->userModel->commit();
            Session::setFlash('success', "Data guru <strong>" . e($name) . "</strong> berhasil diperbarui!");
        } catch (Exception $e) {
            $this->userModel->rollBack();
            Session::setFlash('error', 'Gagal memperbarui data guru: ' . $e->getMessage());
        }

        $this->redirect('admin/teachers');
    }

    /**
     * Delete teacher and their associated user
     */
    public function delete(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/teachers');
            return;
        }

        $teacherId = (int)$this->request->input('id');
        $teacher = $this->teacherModel->findById($teacherId);

        if (!$teacher) {
            Session::setFlash('error', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
            return;
        }

        $userId = (int)$teacher['user_id'];
        $user = $this->userModel->findById($userId);

        try {
            // Delete user cascades to teacher table
            $this->userModel->delete($userId);
            Session::setFlash('success', "Data guru <strong>" . e($user['name'] ?? 'Guru') . "</strong> berhasil dihapus.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus data guru: ' . $e->getMessage());
        }

        $this->redirect('admin/teachers');
    }

    /**
     * Toggle status active <-> inactive
     */
    public function toggleStatus(): void
    {
        $teacherId = (int)$this->request->input('id');
        $teacher = $this->teacherModel->findById($teacherId);

        if (!$teacher) {
            Session::setFlash('error', 'Guru tidak ditemukan.');
            $this->redirect('admin/teachers');
            return;
        }

        $user = $this->userModel->findById((int)$teacher['user_id']);
        if (!$user) {
            Session::setFlash('error', 'Akun pengguna guru tidak ditemukan.');
            $this->redirect('admin/teachers');
            return;
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        $this->userModel->update((int)$user['id'], ['status' => $newStatus]);

        $statusLabel = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        Session::setFlash('success', "Status akun guru <strong>" . e($user['name']) . "</strong> berhasil {$statusLabel}.");
        $this->redirect('admin/teachers');
    }
}
