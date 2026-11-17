<?php

namespace App\Controllers\Guru;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Teacher;
use Exception;

class ProfileController extends Controller
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
     * Display Guru profile page
     */
    public function index(): void
    {
        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);
        $user = $this->userModel->findById($userId);
        $teacher = $this->teacherModel->findByUserId($userId);

        $this->view('guru/profile/index', [
            'title' => 'Profil Guru Penguji - AI Online Exam',
            'user' => $user,
            'teacher' => $teacher
        ]);
    }

    /**
     * Update Guru profile info
     */
    public function updateProfile(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/profile');
            return;
        }

        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);
        $teacher = $this->teacherModel->findByUserId($userId);

        $name = $this->request->string('name');
        $email = $this->request->string('email');
        $phone = $this->request->string('phone');
        $gender = $this->request->string('gender', 'L');

        if (empty($name) || empty($email)) {
            Session::setFlash('error', 'Nama dan Email wajib diisi.');
            $this->redirect('guru/profile');
            return;
        }

        // Email uniqueness check
        $checkEmail = $this->userModel->query("SELECT id FROM users WHERE email = :e AND id != :id", [':e' => $email, ':id' => $userId]);
        if (!empty($checkEmail)) {
            Session::setFlash('error', 'Alamat email sudah digunakan oleh akun lain.');
            $this->redirect('guru/profile');
            return;
        }

        try {
            $this->userModel->update($userId, [
                'name' => $name,
                'email' => $email
            ]);

            if ($teacher) {
                $this->teacherModel->update((int)$teacher['id'], [
                    'phone' => $phone,
                    'gender' => in_array($gender, ['L', 'P'], true) ? $gender : 'L'
                ]);
            }

            // Update session
            $updatedUser = $this->userModel->findById($userId);
            unset($updatedUser['password']);
            $updatedUser['teacher_profile'] = $this->teacherModel->findByUserId($userId);
            Session::set('user', $updatedUser);

            Session::setFlash('success', 'Profil Anda berhasil diperbarui!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        }

        $this->redirect('guru/profile');
    }

    /**
     * Update Guru password
     */
    public function updatePassword(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/profile');
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
            $this->redirect('guru/profile');
            return;
        }

        if (!password_verify($currentPassword, $user['password'])) {
            Session::setFlash('error', 'Kata sandi saat ini (lama) tidak sesuai.');
            $this->redirect('guru/profile');
            return;
        }

        if (strlen($newPassword) < 6) {
            Session::setFlash('error', 'Kata sandi baru minimal harus 6 karakter.');
            $this->redirect('guru/profile');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'Konfirmasi kata sandi baru tidak cocok.');
            $this->redirect('guru/profile');
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

        $this->redirect('guru/profile');
    }
}
