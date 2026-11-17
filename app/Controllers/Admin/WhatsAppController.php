<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use App\Services\Notification\FonnteService;
use Throwable;

class WhatsAppController extends Controller
{
    private FonnteService $fonnteService;
    private WhatsAppLog $logModel;
    private WhatsAppSetting $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->fonnteService = new FonnteService();
        $this->logModel = new WhatsAppLog();
        $this->settingModel = new WhatsAppSetting();
    }

    /**
     * Tampilan utama dashboard pengaturan & riwayat WhatsApp Fonnte
     */
    public function index(): void
    {
        $statusFilter = $this->request->string('status', '');
        $typeFilter = $this->request->string('type', '');
        $search = $this->request->string('q', '');
        $page = max(1, (int)$this->request->string('page', '1'));
        $perPage = 15;

        $filters = [];
        if (!empty($statusFilter)) {
            $filters['status'] = $statusFilter;
        }
        if (!empty($typeFilter)) {
            $filters['notification_type'] = $typeFilter;
        }
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $logs = $this->logModel->getLogs($filters, $page, $perPage);
        $totalLogs = $this->logModel->getTotalCount($filters);
        $totalPages = max(1, (int)ceil($totalLogs / $perPage));
        $stats = $this->logModel->getStats();

        $configStatus = $this->fonnteService->getConfigurationStatus();
        $deviceInfo = $this->fonnteService->getDeviceStatus();
        $rawSettings = $this->settingModel->getAllSettings();

        // Normalized settings with defaults
        $settings = [
            'notify_login_otp' => ($rawSettings['notify_login_otp']['value'] ?? '1') === '1',
            'notify_new_exam' => ($rawSettings['notify_new_exam']['value'] ?? '1') === '1',
            'notify_exam_reminder' => ($rawSettings['notify_exam_reminder']['value'] ?? '1') === '1',
            'notify_schedule_change' => ($rawSettings['notify_schedule_change']['value'] ?? '1') === '1',
            'notify_exam_result' => ($rawSettings['notify_exam_result']['value'] ?? '1') === '1',
            'notify_teacher_finish' => ($rawSettings['notify_teacher_finish']['value'] ?? '1') === '1',
            'reminder_lead_minutes' => (int)($rawSettings['reminder_lead_minutes']['value'] ?? 15),
        ];

        $testOtpSession = Session::get('test_whatsapp_otp');
        $hasActiveTestOtp = !empty($testOtpSession) && time() < (int)($testOtpSession['expires_at'] ?? 0);

        $this->view('admin/whatsapp/index', [
            'title' => 'Pengaturan & Log WhatsApp Gateway (Fonnte)',
            'configStatus' => $configStatus,
            'deviceInfo' => $deviceInfo,
            'settings' => $settings,
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'search' => $search,
            'testOtpSession' => $testOtpSession,
            'hasActiveTestOtp' => $hasActiveTestOtp
        ]);
    }

    /**
     * Simpan pengaturan preferensi notifikasi WhatsApp
     */
    public function updateSettings(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $scope = $this->request->string('scope', 'all');
        $data = [];

        if ($scope === 'otp' || $scope === 'all') {
            $data['notify_login_otp'] = $this->request->string('notify_login_otp') === '1' ? '1' : '0';
        }

        if ($scope === 'exam' || $scope === 'all') {
            $data['notify_new_exam'] = $this->request->string('notify_new_exam') === '1' ? '1' : '0';
            $data['notify_exam_reminder'] = $this->request->string('notify_exam_reminder') === '1' ? '1' : '0';
            $data['notify_schedule_change'] = $this->request->string('notify_schedule_change') === '1' ? '1' : '0';
            $data['notify_exam_result'] = $this->request->string('notify_exam_result') === '1' ? '1' : '0';
            $data['notify_teacher_finish'] = $this->request->string('notify_teacher_finish') === '1' ? '1' : '0';
            $data['reminder_lead_minutes'] = (string)max(5, (int)$this->request->string('reminder_lead_minutes', '15'));
        }

        $success = $this->settingModel->updateSettings($data);

        if ($success) {
            $label = match($scope) {
                'otp' => 'Pengaturan notifikasi OTP WhatsApp',
                'exam' => 'Pengaturan notifikasi Informasi Ujian',
                default => 'Pengaturan notifikasi WhatsApp'
            };
            Session::setFlash('success', "{$label} berhasil diperbarui.");
        } else {
            Session::setFlash('error', 'Gagal menyimpan pengaturan notifikasi.');
        }

        $this->redirect('admin/whatsapp');
    }

    /**
     * Uji Coba Pengiriman Notifikasi 1: Kode OTP WhatsApp
     */
    public function sendTestOtp(): void
    {
        $token = $this->request->string('_csrf_token');
        $isAjax = $this->request->isAjax() || $this->request->string('format') === 'json';

        if (!csrf_verify($token)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Token keamanan CSRF tidak valid.'], 403);
                return;
            }
            Session::setFlash('error', 'Token keamanan CSRF tidak valid.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $targetPhone = trim($this->request->string('phone'));
        $userName = trim($this->request->string('name', 'Pengguna Pengujian'));
        if (empty($userName)) {
            $userName = 'Pengguna Pengujian';
        }

        if (empty($targetPhone)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Nomor WhatsApp tujuan wajib diisi.'], 422);
                return;
            }
            Session::setFlash('error', 'Nomor WhatsApp tujuan wajib diisi.');
            $this->redirect('admin/whatsapp');
            return;
        }

        // Generate 6-digit test OTP code
        $otpCode = (string)random_int(100000, 999999);
        $adminUser = auth();
        $adminId = (int)($adminUser['id'] ?? 0);

        try {
            $result = $this->fonnteService->sendLoginOtp($targetPhone, $userName, $otpCode, $adminId);
        } catch (\Throwable $e) {
            $err = 'Gagal memproses pengiriman OTP: ' . $e->getMessage();
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $err], 500);
                return;
            }
            Session::setFlash('error', $err);
            $this->redirect('admin/whatsapp');
            return;
        }

        if ($result['success']) {
            Session::set('test_whatsapp_otp', [
                'code' => $otpCode,
                'phone' => $targetPhone,
                'name' => $userName,
                'expires_at' => time() + 300,
                'created_at' => time()
            ]);

            $msg = "Kode OTP uji coba ({$otpCode}) berhasil dikirim ke nomor {$targetPhone} via WhatsApp!";
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => $msg,
                    'otp' => $otpCode,
                    'phone' => $targetPhone,
                    'name' => $userName,
                    'stage' => $result['stage'] ?? 'Terkirim'
                ]);
                return;
            }
            Session::setFlash('success', $msg);
        } else {
            $msg = 'Pengiriman OTP gagal: ' . ($result['message'] ?? 'Terjadi kesalahan gateway');
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => $msg,
                    'error' => $result['error'] ?? ''
                ], 500);
                return;
            }
            Session::setFlash('error', $msg);
        }

        $this->redirect('admin/whatsapp');
    }

    /**
     * Uji Coba Verifikasi Kode OTP (Simulasi Admin)
     */
    public function verifyTestOtp(): void
    {
        $token = $this->request->string('_csrf_token');
        $isAjax = $this->request->isAjax() || $this->request->string('format') === 'json';

        if (!csrf_verify($token)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Token keamanan CSRF tidak valid.'], 403);
                return;
            }
            Session::setFlash('error', 'Token keamanan CSRF tidak valid.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $inputCode = preg_replace('/[^\d]/', '', trim($this->request->string('otp_code')));
        $testData = Session::get('test_whatsapp_otp');

        if (empty($inputCode) || strlen($inputCode) !== 6) {
            $msg = 'Silakan masukkan 6 digit angka kode OTP yang diterima melalui WhatsApp.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::setFlash('error', $msg);
            $this->redirect('admin/whatsapp');
            return;
        }

        if (empty($testData) || empty($testData['code'])) {
            $msg = 'Belum ada kode OTP simulasi yang dikirimkan atau sesi simulasi telah berakhir. Silakan kirim kode OTP baru terlebih dahulu.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            Session::setFlash('error', $msg);
            $this->redirect('admin/whatsapp');
            return;
        }

        if (time() > (int)($testData['expires_at'] ?? 0)) {
            Session::remove('test_whatsapp_otp');
            $msg = 'Kode OTP simulasi telah kadaluwarsa (melebihi batas waktu 5 menit). Silakan kirim kode baru.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            Session::setFlash('error', $msg);
            $this->redirect('admin/whatsapp');
            return;
        }

        if ($inputCode !== (string)$testData['code']) {
            $msg = "Kode OTP ({$inputCode}) SALAH atau tidak cocok dengan kode yang dikirimkan ke nomor {$testData['phone']}. Periksa kembali pesan di WhatsApp Anda.";
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::setFlash('error', $msg);
            $this->redirect('admin/whatsapp');
            return;
        }

        // Sukses verifikasi kode OTP simulasi
        $phone = (string)$testData['phone'];
        $name = (string)($testData['name'] ?? 'Pengguna');
        Session::remove('test_whatsapp_otp');

        $msg = "Selamat! Kode OTP ({$inputCode}) VALID dan BERHASIL DIVERIFIKASI! Pada alur sistem nyata, pengguna ({$name}) akan langsung otomatis masuk (login) ke Dashboard.";
        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => $msg,
                'phone' => $phone,
                'name' => $name,
                'otp' => $inputCode
            ]);
            return;
        }

        Session::setFlash('success', $msg);
        $this->redirect('admin/whatsapp');
    }

    /**
     * Uji Coba Pengiriman Notifikasi 2: Informasi Ujian (Jadwal, Pengingat, Perubahan, Nilai)
     */
    public function sendTestExam(): void
    {
        $token = $this->request->string('_csrf_token');
        $isAjax = $this->request->isAjax() || $this->request->string('format') === 'json';

        if (!csrf_verify($token)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Token keamanan CSRF tidak valid.'], 403);
                return;
            }
            Session::setFlash('error', 'Token keamanan CSRF tidak valid.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $targetPhone = trim($this->request->string('phone'));
        $studentName = trim($this->request->string('name', 'Siswa Simulasi'));
        $noticeType = trim($this->request->string('notice_type', 'exam_new'));
        $examTitle = trim($this->request->string('exam_title', 'Simulasi Ujian Akhir Semester (UAS)'));

        if (empty($studentName)) {
            $studentName = 'Siswa Simulasi';
        }
        if (empty($examTitle)) {
            $examTitle = 'Simulasi Ujian Akhir Semester (UAS)';
        }

        if (empty($targetPhone)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Nomor WhatsApp tujuan wajib diisi.'], 422);
                return;
            }
            Session::setFlash('error', 'Nomor WhatsApp tujuan wajib diisi.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $adminUser = auth();
        $adminId = (int)($adminUser['id'] ?? 0);

        try {
            $result = match($noticeType) {
                'exam_reminder' => $this->fonnteService->sendExamReminderNotification(
                    $targetPhone,
                    $studentName,
                    $examTitle,
                    date('H:i', strtotime('+15 minutes')),
                    '15 menit',
                    $adminId,
                    null
                ),
                'schedule_change' => $this->fonnteService->sendScheduleChangeNotification(
                    $targetPhone,
                    $studentName,
                    $examTitle,
                    date('d/m/Y H:i', strtotime('+1 day 08:00')),
                    $adminId,
                    null
                ),
                'exam_result' => $this->fonnteService->sendExamResultNotification(
                    $targetPhone,
                    $studentName,
                    $examTitle,
                    88.5,
                    $adminId,
                    null
                ),
                'teacher_finish' => $this->fonnteService->sendTeacherExamSummaryNotification(
                    $targetPhone,
                    $studentName,
                    $examTitle,
                    30,
                    30,
                    $adminId,
                    null
                ),
                default => $this->fonnteService->sendNewExamNotification(
                    $targetPhone,
                    $studentName,
                    $examTitle,
                    'Semua Mata Pelajaran',
                    date('d/m/Y'),
                    date('H:i', strtotime('+1 hour')),
                    90,
                    $adminId,
                    null
                ),
            };
        } catch (\Throwable $e) {
            $err = 'Gagal memproses pengiriman notifikasi ujian: ' . $e->getMessage();
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $err], 500);
                return;
            }
            Session::setFlash('error', $err);
            $this->redirect('admin/whatsapp');
            return;
        }

        if ($result['success']) {
            $typeName = match($noticeType) {
                'exam_reminder' => 'Pengingat Ujian',
                'schedule_change' => 'Perubahan Jadwal',
                'exam_result' => 'Hasil Nilai Ujian',
                'teacher_finish' => 'Laporan Guru',
                default => 'Jadwal Ujian Baru'
            };
            $msg = "Notifikasi {$typeName} berhasil dikirim ke nomor {$targetPhone} via Fonnte!";
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => $msg,
                    'stage' => $result['stage'] ?? 'Terkirim'
                ]);
                return;
            }
            Session::setFlash('success', $msg);
        } else {
            $msg = 'Pengiriman notifikasi ujian gagal: ' . ($result['message'] ?? 'Terjadi kesalahan gateway');
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => $msg,
                    'error' => $result['error'] ?? ''
                ], 500);
                return;
            }
            Session::setFlash('error', $msg);
        }

        $this->redirect('admin/whatsapp');
    }

    /**
     * Kirim pesan uji coba umum (General Test Message) ke nomor tertentu
     */
    public function sendTest(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $targetPhone = trim($this->request->string('test_phone'));
        $customNote = trim($this->request->string('test_note', ''));

        if (empty($targetPhone)) {
            Session::setFlash('error', 'Nomor telepon tujuan pengujian wajib diisi.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $adminUser = auth();
        $adminId = (int)($adminUser['id'] ?? 0);

        $result = $this->fonnteService->sendTestMessage($targetPhone, $adminId, $customNote);

        if ($result['success']) {
            Session::setFlash('success', 'Pesan uji coba berhasil dikirim ke gateway Fonnte! Status: ' . ($result['stage'] ?? 'Terkirim'));
        } else {
            Session::setFlash('error', 'Pengujian gagal: ' . ($result['message'] ?? 'Terjadi kesalahan'));
        }

        $this->redirect('admin/whatsapp');
    }

    /**
     * Retry pengiriman pesan gagal
     */
    public function retry(int $id): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $log = $this->logModel->findWithUser($id);
        if (!$log) {
            Session::setFlash('error', 'Log pengiriman tidak ditemukan.');
            $this->redirect('admin/whatsapp');
            return;
        }

        $result = $this->fonnteService->sendMessage(
            $log['phone_number'],
            $log['message'],
            $log['notification_type'] ?? 'GENERAL',
            $log['user_id'] ? (int)$log['user_id'] : null,
            null // retry tanpa idempotency key lama agar dapat diproses
        );

        if ($result['success']) {
            Session::setFlash('success', 'Pesan berhasil dikirim ulang ke gateway Fonnte!');
        } else {
            Session::setFlash('error', 'Gagal mengirim ulang pesan: ' . ($result['message'] ?? 'Kesalahan koneksi/gateway'));
        }

        $this->redirect('admin/whatsapp');
    }
}
