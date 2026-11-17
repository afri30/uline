<?php

declare(strict_types=1);

namespace App\Controllers\Peserta;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Student;
use Exception;

class ProfileController extends Controller
{
    private User $userModel;
    private Student $studentModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->studentModel = new Student();
    }

    /**
     * Display Peserta Didik profile page
     */
    public function index(): void
    {
        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);
        $user = $this->userModel->findById($userId);
        $student = $this->studentModel->findByUserIdWithClass($userId);

        $this->view('peserta/profile/index', [
            'title' => 'Profil Peserta Didik - AI Online Exam',
            'user' => $user,
            'student' => $student
        ]);
    }

    /**
     * Update Peserta Didik profile info
     */
    public function updateProfile(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('peserta/profile');
            return;
        }

        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);
        $student = $this->studentModel->findByUserId($userId);

        $name = trim($this->request->string('name'));
        $email = trim($this->request->string('email'));
        $phone = trim($this->request->string('phone'));
        $gender = trim($this->request->string('gender', 'L'));

        if (empty($name) || empty($email)) {
            Session::setFlash('error', 'Nama dan Email wajib diisi.');
            $this->redirect('peserta/profile');
            return;
        }

        // Email uniqueness check
        $checkEmail = $this->userModel->query(
            "SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1",
            [':e' => $email, ':id' => $userId]
        );
        if (!empty($checkEmail)) {
            Session::setFlash('error', 'Alamat email sudah digunakan oleh akun lain.');
            $this->redirect('peserta/profile');
            return;
        }

        try {
            // Update users table
            $this->userModel->update($userId, [
                'name' => $name,
                'email' => $email,
                'phone_number' => $phone
            ]);

            // Update students table
            if ($student) {
                $this->studentModel->update((int)$student['id'], [
                    'phone' => $phone,
                    'gender' => in_array($gender, ['L', 'P'], true) ? $gender : 'L'
                ]);
            }

            // Refresh session cache
            $updatedUser = $this->userModel->findById($userId);
            if ($updatedUser) {
                $sessionUser = $this->userModel->prepareUserForSession($updatedUser);
                Session::set('user', $sessionUser);
            }

            Session::setFlash('success', 'Profil Anda berhasil diperbarui!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        }

        $this->redirect('peserta/profile');
    }

    /**
     * Update Peserta Didik password
     */
    public function updatePassword(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('peserta/profile');
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
            $this->redirect('peserta/profile');
            return;
        }

        if (!password_verify($currentPassword, $user['password'])) {
            Session::setFlash('error', 'Kata sandi saat ini (lama) tidak sesuai.');
            $this->redirect('peserta/profile');
            return;
        }

        if (strlen($newPassword) < 6) {
            Session::setFlash('error', 'Kata sandi baru minimal harus 6 karakter.');
            $this->redirect('peserta/profile');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'Konfirmasi kata sandi baru tidak cocok.');
            $this->redirect('peserta/profile');
            return;
        }

        try {
            $this->userModel->update($userId, [
                'password' => password_hash($newPassword, PASSWORD_BCRYPT)
            ]);

            Session::setFlash('success', 'Kata sandi berhasil diperbarui!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui kata sandi: ' . $e->getMessage());
        }

        $this->redirect('peserta/profile');
    }
}

