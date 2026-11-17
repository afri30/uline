<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\WhatsAppOtp;
use App\Services\Notification\FonnteService;
use Exception;
use Throwable;

class AuthController extends Controller
{
    private User $userModel;
    private WhatsAppOtp $otpModel;
    private FonnteService $fonnteService;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->otpModel = new WhatsAppOtp();
        $this->fonnteService = new FonnteService();
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // If user already authenticated, redirect to their role dashboard
        if (is_logged_in()) {
            $user = auth();
            $redirectUrl = User::getDashboardUrl($user['role'] ?? '');
            $this->redirect($redirectUrl);
            return;
        }

        $this->view('auth/login', [
            'title' => 'Login - AI Online Examination & Proctoring System'
        ], 'auth');
    }

    /**
     * Process login submission
     */
    public function login(): void
    {
        // Verify CSRF Token
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => 'Token keamanan CSRF tidak valid atau sesi kadaluarsa.'], 400);
            }
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect('login');
            return;
        }

        $identifier = $this->request->string('identifier');
        $password = $this->request->string('password');

        // Validation: required fields
        if (empty($identifier) && empty($password)) {
            $errorMsg = 'Username/Email dan Password wajib diisi.';
            $this->handleLoginFailure($errorMsg, 422);
            return;
        }

        if (empty($identifier)) {
            $errorMsg = 'Username atau Email wajib diisi.';
            $this->handleLoginFailure($errorMsg, 422);
            return;
        }

        if (empty($password)) {
            $errorMsg = 'Kata sandi (password) wajib diisi.';
            $this->handleLoginFailure($errorMsg, 422);
            return;
        }

        try {
            // Authenticate user (checks existence, password_verify, and status === 'active')
            $user = $this->userModel->authenticate($identifier, $password);

            // Regenerate session ID for security against session fixation
            Session::regenerate();
            Session::set('user', $user);

            // Determine target dashboard based on role
            $targetUrl = User::getDashboardUrl($user['role']);
            $roleLabel = str_replace('_', ' ', $user['role']);

            // Audit log login
            audit_log('LOGIN', "Pengguna {$user['name']} ({$user['username']}) berhasil login dengan role {$user['role']}.", [
                'role' => $user['role'],
                'user_id' => $user['id']
            ], (int)$user['id']);

            Session::setFlash('success', "Selamat datang, <strong>" . e($user['name']) . "</strong>! Anda berhasil masuk sebagai <strong>" . e($roleLabel) . "</strong>.");

            if ($this->request->isAjax()) {
                $this->json([
                    'status' => 'success',
                    'message' => 'Login berhasil! Mengalihkan ke dashboard...',
                    'redirect' => base_url($targetUrl)
                ]);
                return;
            }

            $this->redirect($targetUrl);

        } catch (Exception $e) {
            $this->handleLoginFailure($e->getMessage(), 401);
        }
    }

    /**
     * Helper for login failure handling
     */
    private function handleLoginFailure(string $message, int $statusCode = 400): void
    {
        if ($this->request->isAjax()) {
            $this->json(['status' => 'error', 'message' => $message], $statusCode);
        }
        Session::setFlash('error', $message);
        $this->redirect('login');
    }

    /**
     * Request OTP via WhatsApp (Fonnte API)
     */
    public function sendWhatsAppOtp(): void
    {
        // 1. Verify CSRF Token
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => 'Token keamanan CSRF tidak valid.'], 400);
            }
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect('login');
            return;
        }

        $inputPhone = trim($this->request->string('phone_number'));
        if (empty($inputPhone)) {
            $msg = 'Nomor WhatsApp wajib diisi.';
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $msg], 422);
            }
            Session::setFlash('error', $msg);
            $this->redirect('login');
            return;
        }

        // 2. Find user by phone number or identifier
        $user = $this->userModel->findByPhone($inputPhone);
        if (!$user) {
            // Also check if entered as username or email
            $user = $this->userModel->findByUsernameOrEmail($inputPhone);
        }

        if (!$user) {
            $msg = 'Nomor WhatsApp atau akun tidak ditemukan dalam sistem. Pastikan nomor telah terdaftar.';
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $msg], 404);
            }
            Session::setFlash('error', $msg);
            $this->redirect('login');
            return;
        }

        // 3. Check active status
        if (($user['status'] ?? '') !== 'active') {
            $msg = 'Akun Anda saat ini berstatus non-aktif. Silakan hubungi Administrator.';
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $msg], 403);
            }
            Session::setFlash('error', $msg);
            $this->redirect('login');
            return;
        }

        $targetPhone = !empty($user['phone_number']) ? $user['phone_number'] : $inputPhone;
        $formattedPhone = $this->fonnteService->formatPhoneNumber($targetPhone);

        if (!$formattedPhone) {
            $msg = 'Format nomor WhatsApp tidak valid. Pastikan nomor diawali 08xx atau 628xx.';
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $msg], 422);
            }
            Session::setFlash('error', $msg);
            $this->redirect('login');
            return;
        }

        // 4. Rate Limiting Check (60s cooldown, max 10/hour)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $rateLimit = $this->otpModel->checkRateLimit($formattedPhone, $clientIp);
        if (!$rateLimit['allowed']) {
            if ($this->request->isAjax()) {
                $this->json([
                    'status' => 'error',
                    'message' => $rateLimit['message'],
                    'retry_after' => $rateLimit['retry_after'] ?? 60
                ], 429);
            }
            Session::setFlash('error', $rateLimit['message']);
            $this->redirect('login');
            return;
        }

        // 5. Generate secure 6-digit OTP
        $otpData = $this->otpModel->generateOtp((int)$user['id'], $formattedPhone, 'LOGIN', 5, $clientIp);
        $otpCode = $otpData['otp_code'];

        // 6. Send OTP via Fonnte Gateway
        $sendResult = $this->fonnteService->sendLoginOtp($formattedPhone, (string)$user['name'], $otpCode, (int)$user['id']);

        // Mask phone number for UI privacy (e.g. 0812****7890)
        $len = strlen($targetPhone);
        $maskedPhone = ($len > 7) ? substr($targetPhone, 0, 4) . '****' . substr($targetPhone, -4) : $targetPhone;

        // Save state in session for OTP verification step
        Session::set('whatsapp_login_state', [
            'user_id' => (int)$user['id'],
            'phone' => $targetPhone,
            'masked_phone' => $maskedPhone,
            'otp_sent_at' => time()
        ]);

        $successMsg = "Kode OTP verifikasi telah dikirim ke WhatsApp Anda ({$maskedPhone}). Silakan periksa pesan masuk WhatsApp.";

        if ($this->request->isAjax()) {
            $this->json([
                'status' => 'success',
                'message' => $successMsg,
                'user_id' => (int)$user['id'],
                'masked_phone' => $maskedPhone,
                'cooldown' => 60,
                'fonnte_status' => $sendResult['status'] ?? 'SUCCESS'
            ]);
            return;
        }

        Session::setFlash('success', $successMsg);
        $this->redirect('login?tab=wa&step=verify');
    }

    /**
     * Verify OTP code & authenticate user
     */
    public function verifyWhatsAppOtp(): void
    {
        // 1. Verify CSRF Token
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => 'Token keamanan CSRF tidak valid.'], 400);
            }
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect('login');
            return;
        }

        $sessionState = Session::get('whatsapp_login_state', []);
        $userId = (int)$this->request->string('user_id', (string)($sessionState['user_id'] ?? 0));
        $submittedOtp = preg_replace('/[^\d]/', '', $this->request->string('otp_code'));

        if (empty($userId) || empty($submittedOtp)) {
            $msg = 'Kode OTP 6-digit wajib diisi.';
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $msg], 422);
            }
            Session::setFlash('error', $msg);
            $this->redirect('login?tab=wa&step=verify');
            return;
        }

        // 2. Verify OTP in database
        $verifyResult = $this->otpModel->verifyOtp($userId, $submittedOtp, 'LOGIN');
        if (!$verifyResult['valid']) {
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $verifyResult['message']], 401);
            }
            Session::setFlash('error', $verifyResult['message']);
            $this->redirect('login?tab=wa&step=verify');
            return;
        }

        // 3. OTP is valid: Authenticate user
        $user = $this->userModel->findById($userId);
        if (!$user || ($user['status'] ?? '') !== 'active') {
            $msg = 'Akun Anda tidak ditemukan atau berstatus non-aktif.';
            if ($this->request->isAjax()) {
                $this->json(['status' => 'error', 'message' => $msg], 403);
            }
            Session::setFlash('error', $msg);
            $this->redirect('login');
            return;
        }

        // Complete user profile & regenerate session ID
        $sessionUser = $this->userModel->prepareUserForSession($user);
        Session::regenerate();
        Session::set('user', $sessionUser);
        Session::remove('whatsapp_login_state');

        $targetUrl = User::getDashboardUrl($sessionUser['role']);
        $roleLabel = str_replace('_', ' ', $sessionUser['role']);

        // Audit log login WhatsApp
        audit_log('LOGIN_WHATSAPP', "Pengguna {$sessionUser['name']} ({$sessionUser['username']}) berhasil login menggunakan WhatsApp OTP.", [
            'role' => $sessionUser['role'],
            'user_id' => $sessionUser['id'],
            'phone_number' => $sessionUser['phone_number'] ?? null,
            'auth_method' => 'whatsapp_otp'
        ], (int)$sessionUser['id']);

        Session::setFlash('success', "Selamat datang, <strong>" . e($sessionUser['name']) . "</strong>! Anda berhasil masuk via WhatsApp sebagai <strong>" . e($roleLabel) . "</strong>.");

        if ($this->request->isAjax()) {
            $this->json([
                'status' => 'success',
                'message' => 'Verifikasi berhasil! Mengalihkan ke dashboard...',
                'redirect' => base_url($targetUrl)
            ]);
            return;
        }

        $this->redirect($targetUrl);
    }

    /**
     * Process logout
     */
    public function logout(): void
    {
        $user = auth();
        if ($user) {
            audit_log('LOGOUT', "Pengguna {$user['name']} ({$user['username']}) keluar dari sistem.", [
                'user_id' => $user['id'],
                'role' => $user['role']
            ], (int)$user['id']);
        }

        Session::destroy();
        Session::start();
        Session::setFlash('success', 'Anda telah berhasil keluar (logout) dari sistem.');
        $this->redirect('login');
    }
}
