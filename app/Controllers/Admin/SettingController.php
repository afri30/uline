<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use Exception;

class SettingController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * Resolve safe redirect path (admin/settings or admin/profile)
     */
    private function getRedirectTarget(): string
    {
        $target = trim($this->request->string('redirect_to', 'admin/settings'));
        return in_array($target, ['admin/settings', 'admin/profile'], true) ? $target : 'admin/settings';
    }

    /**
     * Display settings page
     */
    public function index(): void
    {
        $currentUser = auth();
        $user = $this->userModel->findById((int)($currentUser['id'] ?? 0));

        $this->view('admin/settings/index', [
            'title' => 'Pengaturan Akun & Sistem - Super Admin',
            'user' => $user,
            'aiConfig' => config('ai')
        ]);
    }

    /**
     * Display dedicated Super Admin profile page (/admin/profile)
     */
    public function profile(): void
    {
        $currentUser = auth();
        $user = $this->userModel->findById((int)($currentUser['id'] ?? 0));

        $this->view('admin/profile/index', [
            'title' => 'Profil Super Administrator - AI Online Exam',
            'user' => $user
        ]);
    }

    /**
     * Update Super Admin profile
     */
    public function updateProfile(): void
    {
        $redirectTarget = $this->getRedirectTarget();
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect($redirectTarget);
            return;
        }

        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);

        $name = $this->request->string('name');
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $phone = trim($this->request->string('phone_number'));

        if (empty($name) || empty($username) || empty($email)) {
            Session::setFlash('error', 'Nama, Username, dan Email wajib diisi.');
            $this->redirect($redirectTarget);
            return;
        }

        // Unique checks
        $checkUser = $this->userModel->query("SELECT id FROM users WHERE username = :u AND id != :id", [':u' => $username, ':id' => $userId]);
        if (!empty($checkUser)) {
            Session::setFlash('error', "Username '{$username}' sudah digunakan akun lain.");
            $this->redirect($redirectTarget);
            return;
        }

        $checkEmail = $this->userModel->query("SELECT id FROM users WHERE email = :e AND id != :id", [':e' => $email, ':id' => $userId]);
        if (!empty($checkEmail)) {
            Session::setFlash('error', "Email '{$email}' sudah digunakan akun lain.");
            $this->redirect($redirectTarget);
            return;
        }

        try {
            $updateData = [
                'name' => $name,
                'username' => $username,
                'email' => $email
            ];
            if (array_key_exists('phone_number', $this->request->all())) {
                $updateData['phone_number'] = $phone;
            }
            $this->userModel->update($userId, $updateData);

            // Update session cache
            $updatedUser = $this->userModel->findById($userId);
            unset($updatedUser['password']);
            Session::set('user', $updatedUser);

            Session::setFlash('success', 'Profil Administrator berhasil diperbarui!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        }

        $this->redirect($redirectTarget);
    }

    /**
     * Update Super Admin password
     */
    public function updatePassword(): void
    {
        $redirectTarget = $this->getRedirectTarget();
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect($redirectTarget);
            return;
        }

        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);
        $user = $this->userModel->findById($userId);

        $currentPassword = $this->request->string('current_password');
        $newPassword = $this->request->string('new_password');
        $confirmPassword = $this->request->string('confirm_password');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::setFlash('error', 'Semua kolom kata sandi wajib diisi.');
            $this->redirect($redirectTarget);
            return;
        }

        if (!password_verify($currentPassword, $user['password'])) {
            Session::setFlash('error', 'Kata sandi saat ini (lama) tidak sesuai.');
            $this->redirect($redirectTarget);
            return;
        }

        if (strlen($newPassword) < 6) {
            Session::setFlash('error', 'Kata sandi baru minimal harus 6 karakter.');
            $this->redirect($redirectTarget);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'Konfirmasi kata sandi baru tidak cocok.');
            $this->redirect($redirectTarget);
            return;
        }

        try {
            $this->userModel->update($userId, [
                'password' => password_hash($newPassword, PASSWORD_BCRYPT)
            ]);

            Session::setFlash('success', 'Kata sandi Anda berhasil diperbarui!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui kata sandi: ' . $e->getMessage());
        }

        $this->redirect($redirectTarget);
    }
}
