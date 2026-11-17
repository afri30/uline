<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Env;
use App\Models\ExamConfig;
use App\Models\AuditLog;
use Exception;

class ExamConfigController extends Controller
{
    private ExamConfig $configModel;

    public function __construct()
    {
        parent::__construct();
        $this->configModel = new ExamConfig();
    }

    /**
     * Display Exam Configuration Form View
     */
    public function index(): void
    {
        $currentUser = auth();
        $config = $this->configModel->getActiveConfig();

        // Read secret credentials from .env
        $secretKey = env('EXAM_TOKEN_SECRET_KEY', $config['token_secret'] ?? 'exam_sec_f98c21a084eb73');

        // Check if currently inside the exam window
        $windowStatus = $this->configModel->checkExamWindow();

        $this->view('admin/exam_config/index', [
            'title' => 'Konfigurasi Ujian Online - Panel Admin & Guru',
            'config' => $config,
            'secretKey' => $secretKey,
            'windowStatus' => $windowStatus,
            'currentUser' => $currentUser
        ]);
    }

    /**
     * Update Exam Configuration and Secret Credentials in .env
     */
    public function update(): void
    {
        $isAjax = $this->request->isAjax();

        // 1. CSRF Token Verification
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            if ($isAjax) {
                $this->json(['status' => 'error', 'message' => 'Token CSRF tidak valid atau kedaluwarsa.'], 403);
                return;
            }
            Session::setFlash('error', 'Token CSRF tidak valid. Silakan muat ulang halaman.');
            $this->redirect('admin/exam-config');
            return;
        }

        // 2. Extract and Sanitize Inputs
        $title = trim($this->request->string('title', 'Konfigurasi Utama Ujian Online'));
        $duration = (int)$this->request->input('duration', 60);
        $randomQuestions = $this->request->input('random_questions') ? 1 : 0;
        $randomOptions = $this->request->input('random_options') ? 1 : 0;
        $startTime = trim($this->request->string('start_time'));
        $endTime = trim($this->request->string('end_time'));
        $examToken = strtoupper(trim($this->request->string('token')));
        $tokenSecret = trim($this->request->string('token_secret'));
        $isActive = $this->request->input('is_active') !== null ? (int)$this->request->input('is_active') : 1;

        // Convert datetime-local format (Y-m-d\TH:i) to standard MySQL format (Y-m-d H:i:s)
        if (str_contains($startTime, 'T')) {
            $startTime = str_replace('T', ' ', $startTime);
            if (strlen($startTime) === 16) {
                $startTime .= ':00';
            }
        }
        if (str_contains($endTime, 'T')) {
            $endTime = str_replace('T', ' ', $endTime);
            if (strlen($endTime) === 16) {
                $endTime .= ':00';
            }
        }

        // 3. Validation Rules
        if (empty($title)) {
            $error = 'Judul konfigurasi ujian tidak boleh kosong.';
            if ($isAjax) { $this->json(['status' => 'error', 'message' => $error], 422); return; }
            Session::setFlash('error', $error);
            $this->redirect('admin/exam-config');
            return;
        }

        if ($duration < 5 || $duration > 600) {
            $error = 'Durasi ujian harus berkisar antara 5 hingga 600 menit.';
            if ($isAjax) { $this->json(['status' => 'error', 'message' => $error], 422); return; }
            Session::setFlash('error', $error);
            $this->redirect('admin/exam-config');
            return;
        }

        if (empty($startTime) || empty($endTime)) {
            $error = 'Waktu mulai dan waktu selesai ujian wajib diisi.';
            if ($isAjax) { $this->json(['status' => 'error', 'message' => $error], 422); return; }
            Session::setFlash('error', $error);
            $this->redirect('admin/exam-config');
            return;
        }

        $startTimestamp = strtotime($startTime);
        $endTimestamp = strtotime($endTime);

        if (!$startTimestamp || !$endTimestamp) {
            $error = 'Format waktu mulai atau selesai tidak valid.';
            if ($isAjax) { $this->json(['status' => 'error', 'message' => $error], 422); return; }
            Session::setFlash('error', $error);
            $this->redirect('admin/exam-config');
            return;
        }

        if ($endTimestamp <= $startTimestamp) {
            $error = 'Waktu selesai ujian harus lebih besar dari waktu mulai.';
            if ($isAjax) { $this->json(['status' => 'error', 'message' => $error], 422); return; }
            Session::setFlash('error', $error);
            $this->redirect('admin/exam-config');
            return;
        }

        if (empty($examToken) || strlen($examToken) < 3) {
            $error = 'Token ujian wajib diisi minimal 3 karakter.';
            if ($isAjax) { $this->json(['status' => 'error', 'message' => $error], 422); return; }
            Session::setFlash('error', $error);
            $this->redirect('admin/exam-config');
            return;
        }

        try {
            // 4. Save Database Configuration
            $dataToSave = [
                'title' => $title,
                'duration' => $duration,
                'random_questions' => $randomQuestions,
                'random_options' => $randomOptions,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'token' => $examToken,
                'token_secret' => $tokenSecret,
                'is_active' => $isActive
            ];

            $this->configModel->saveConfig($dataToSave);

            // 5. Save Secret Credential to .env file
            if (!empty($tokenSecret)) {
                Env::set('EXAM_TOKEN_SECRET_KEY', $tokenSecret);
            }

            // 6. Log to AuditLog (if model available)
            try {
                $currentUser = auth();
                $auditLog = new AuditLog();
                $auditLog->log(
                    (int)($currentUser['id'] ?? 0),
                    'UPDATE_EXAM_CONFIG',
                    "Memperbarui konfigurasi ujian (Durasi: {$duration}m, Token: {$examToken}, Acak: {$randomQuestions})",
                    [
                        'duration' => $duration,
                        'random_questions' => $randomQuestions,
                        'random_options' => $randomOptions,
                        'token' => $examToken,
                        'start_time' => $startTime,
                        'end_time' => $endTime
                    ]
                );
            } catch (\Throwable $e) {
                // Non-critical audit logging failure ignored
            }

            try {
                (new \App\Services\Notification\NotificationService())->notifySuperAdmins(
                    "Konfigurasi Ujian Diperbarui",
                    "Pengaturan parameter ujian global (durasi: {$duration} menit, acak soal: " . ($randomQuestions ? 'Ya' : 'Tidak') . ") telah diperbarui.",
                    'system',
                    'admin/exam-config',
                    'exam_config_updated',
                    1
                );
            } catch (\Throwable $e) {}

            $successMsg = 'Konfigurasi ujian dan kredensial rahasia di .env berhasil disimpan!';

            if ($isAjax) {
                $this->json([
                    'status' => 'success',
                    'message' => $successMsg,
                    'data' => [
                        'duration' => $duration,
                        'random_questions' => $randomQuestions,
                        'random_options' => $randomOptions,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'token' => $examToken
                    ]
                ]);
                return;
            }

            Session::setFlash('success', $successMsg);
            $this->redirect('admin/exam-config');
        } catch (Exception $e) {
            $errorMsg = 'Gagal menyimpan konfigurasi: ' . $e->getMessage();
            if ($isAjax) {
                $this->json(['status' => 'error', 'message' => $errorMsg], 500);
                return;
            }
            Session::setFlash('error', $errorMsg);
            $this->redirect('admin/exam-config');
        }
    }

    /**
     * AJAX endpoint to generate a secure random exam token
     */
    public function generateToken(): void
    {
        $newToken = ExamConfig::generateToken(6);
        $this->json([
            'status' => 'success',
            'token' => $newToken
        ]);
    }
}
