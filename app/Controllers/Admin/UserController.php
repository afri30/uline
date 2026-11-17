<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Exception;

class UserController extends Controller
{
    private User $userModel;
    private Teacher $teacherModel;
    private Student $studentModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->teacherModel = new Teacher();
        $this->studentModel = new Student();
    }

    /**
     * Display list of users with search, role filter & pagination
     */
    public function index(): void
    {
        $search = $this->request->string('q');
        $role = $this->request->string('role');
        $status = $this->request->string('status');
        $page = max(1, (int)$this->request->input('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(`name` LIKE :search OR `username` LIKE :search OR `email` LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($role) && in_array($role, ['SUPER_ADMIN', 'GURU', 'PESERTA'], true)) {
            $whereClauses[] = "`role` = :role";
            $params[':role'] = $role;
        }

        if (!empty($status) && in_array($status, ['active', 'inactive'], true)) {
            $whereClauses[] = "`status` = :status";
            $params[':status'] = $status;
        }

        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        // Total count for current filter
        $countSql = "SELECT COUNT(*) as total FROM `users` {$whereSql}";
        $totalRes = $this->userModel->query($countSql, $params);
        $totalItems = (int)($totalRes[0]['total'] ?? 0);

        // Overall User Statistics for Metric Cards (like Dashboard)
        $statsSql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN `role` = 'SUPER_ADMIN' THEN 1 ELSE 0 END) as total_admin,
            SUM(CASE WHEN `role` = 'GURU' THEN 1 ELSE 0 END) as total_guru,
            SUM(CASE WHEN `role` = 'PESERTA' THEN 1 ELSE 0 END) as total_peserta,
            SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) as total_active,
            SUM(CASE WHEN `status` = 'inactive' THEN 1 ELSE 0 END) as total_inactive
        FROM `users`";
        $statsResult = $this->userModel->query($statsSql);
        $userStats = [
            'total' => (int)($statsResult[0]['total'] ?? 0),
            'admin' => (int)($statsResult[0]['total_admin'] ?? 0),
            'guru' => (int)($statsResult[0]['total_guru'] ?? 0),
            'peserta' => (int)($statsResult[0]['total_peserta'] ?? 0),
            'active' => (int)($statsResult[0]['total_active'] ?? 0),
            'inactive' => (int)($statsResult[0]['total_inactive'] ?? 0),
        ];

        // Fetch data sorted by hierarchy: SUPER_ADMIN first, then GURU, then PESERTA, then id ASC
        $dataSql = "SELECT id, name, username, email, role, status, created_at, updated_at 
                    FROM `users` {$whereSql} 
                    ORDER BY CASE 
                        WHEN `role` = 'SUPER_ADMIN' THEN 1 
                        WHEN `role` = 'GURU' THEN 2 
                        WHEN `role` = 'PESERTA' THEN 3 
                        ELSE 4 
                    END ASC, `id` ASC 
                    LIMIT {$perPage} OFFSET {$offset}";
        $users = $this->userModel->query($dataSql, $params);

        $this->view('admin/users/index', [
            'title' => 'Manajemen Pengguna - Super Admin',
            'users' => $users,
            'stats' => $userStats,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'role' => $role,
            'status' => $status
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/users');
            return;
        }

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $password = $this->request->string('password');
        $role = $this->request->string('role');
        $status = $this->request->string('status', 'active');

        // Validation
        if (empty($name) || empty($username) || empty($email) || empty($password) || empty($role)) {
            Session::setFlash('error', 'Semua bidang wajib diisi.');
            $this->redirect('admin/users');
            return;
        }

        if (strlen($password) < 6) {
            Session::setFlash('error', 'Password minimal harus 6 karakter.');
            $this->redirect('admin/users');
            return;
        }

        // Unique checks
        if ($this->userModel->findWhere('username', $username)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan.");
            $this->redirect('admin/users');
            return;
        }

        if ($this->userModel->findWhere('email', $email)) {
            Session::setFlash('error', "Email '{$email}' sudah terdaftar.");
            $this->redirect('admin/users');
            return;
        }

        try {
            $this->userModel->beginTransaction();

            $newUserId = $this->userModel->createWithPassword([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'status' => $status === 'inactive' ? 'inactive' : 'active'
            ]);

            // Synchronize role profile table
            if ($role === 'GURU') {
                $nip = '19' . date('y') . str_pad((string)$newUserId, 4, '0', STR_PAD_LEFT) . rand(100, 999);
                $this->teacherModel->create([
                    'user_id' => $newUserId,
                    'nip' => $nip,
                    'gender' => 'L'
                ]);
            } elseif ($role === 'PESERTA') {
                $nis = date('Y') . str_pad((string)$newUserId, 4, '0', STR_PAD_LEFT);
                $this->studentModel->create([
                    'user_id' => $newUserId,
                    'nis' => $nis,
                    'gender' => 'L'
                ]);
            }

            $this->userModel->commit();

            try {
                (new \App\Services\Notification\NotificationService())->notifySuperAdmins(
                    "Pengguna Baru Didaftarkan",
                    "Akun pengguna baru '{$name}' (@{$username}) dengan peran {$role} telah berhasil ditambahkan ke sistem.",
                    'system',
                    'admin/users',
                    'user_created',
                    (int)$newUserId
                );
            } catch (\Throwable $e) {}

            Session::setFlash('success', "Pengguna <strong>" . e($name) . "</strong> (" . e($role) . ") berhasil ditambahkan!");
        } catch (Exception $e) {
            $this->userModel->rollBack();
            Session::setFlash('error', 'Gagal menambahkan pengguna: ' . $e->getMessage());
        }

        $this->redirect('admin/users');
    }

    /**
     * Update user details
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/users');
            return;
        }

        $id = (int)$this->request->input('id');
        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('admin/users');
            return;
        }

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $password = $this->request->string('password');
        $role = $this->request->string('role');
        $status = $this->request->string('status', 'active');

        if (empty($name) || empty($username) || empty($email) || empty($role)) {
            Session::setFlash('error', 'Nama, Username, Email, dan Role wajib diisi.');
            $this->redirect('admin/users');
            return;
        }

        // Check unique username except current
        $existingUser = $this->userModel->query("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1", [
            ':u' => $username,
            ':id' => $id
        ]);
        if (!empty($existingUser)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan oleh pengguna lain.");
            $this->redirect('admin/users');
            return;
        }

        // Check unique email except current
        $existingEmail = $this->userModel->query("SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1", [
            ':e' => $email,
            ':id' => $id
        ]);
        if (!empty($existingEmail)) {
            Session::setFlash('error', "Email '{$email}' sudah digunakan oleh pengguna lain.");
            $this->redirect('admin/users');
            return;
        }

        if (!empty($password) && strlen($password) < 6) {
            Session::setFlash('error', 'Password baru minimal 6 karakter.');
            $this->redirect('admin/users');
            return;
        }

        try {
            $this->userModel->beginTransaction();

            $data = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'status' => $status === 'inactive' ? 'inactive' : 'active'
            ];

            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);
            }

            $this->userModel->update($id, $data);

            // Synchronize profile if role changed
            if ($role === 'GURU') {
                $existingTeacher = $this->teacherModel->findWhere('user_id', $id);
                if (!$existingTeacher) {
                    $nip = '19' . date('y') . str_pad((string)$id, 4, '0', STR_PAD_LEFT) . rand(100, 999);
                    $this->teacherModel->create([
                        'user_id' => $id,
                        'nip' => $nip,
                        'gender' => 'L'
                    ]);
                }
            } elseif ($role === 'PESERTA') {
                $existingStudent = $this->studentModel->findWhere('user_id', $id);
                if (!$existingStudent) {
                    $nis = date('Y') . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                    $this->studentModel->create([
                        'user_id' => $id,
                        'nis' => $nis,
                        'gender' => 'L'
                    ]);
                }
            }

            $this->userModel->commit();
            Session::setFlash('success', "Data pengguna <strong>" . e($name) . "</strong> berhasil diperbarui!");
        } catch (Exception $e) {
            $this->userModel->rollBack();
            Session::setFlash('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }

        $this->redirect('admin/users');
    }

    /**
     * Delete user
     */
    public function delete(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/users');
            return;
        }

        $id = (int)$this->request->input('id');
        $currentUser = auth();

        if ($id === (int)($currentUser['id'] ?? 0)) {
            Session::setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
            $this->redirect('admin/users');
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            Session::setFlash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('admin/users');
            return;
        }

        try {
            $this->userModel->delete($id);
            Session::setFlash('success', "Pengguna <strong>" . e($user['name']) . "</strong> berhasil dihapus.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }

        $this->redirect('admin/users');
    }

    /**
     * Toggle user status (active <-> inactive)
     */
    public function toggleStatus(): void
    {
        $id = (int)$this->request->input('id');
        $currentUser = auth();

        if ($id === (int)($currentUser['id'] ?? 0)) {
            Session::setFlash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            $this->redirect('admin/users');
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            Session::setFlash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('admin/users');
            return;
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        $this->userModel->update($id, ['status' => $newStatus]);

        $statusLabel = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        Session::setFlash('success', "Akun <strong>" . e($user['name']) . "</strong> berhasil {$statusLabel}.");
        $this->redirect('admin/users');
    }
}
