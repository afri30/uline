<?php

/**
 * AI Online Examination & Proctoring System
 * Front Controller & Application Entry Point
 */

declare(strict_types=1);

// Report all errors in development mode
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// 1. Simple PSR-4 Style Autoloader for App\ Namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// 2. Load Environment Variables (.env)
\App\Core\Env::load();

// 3. Load Helper Functions
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Models\User;

// 3. Initialize Secure Session
Session::start();

// 4. Instantiate Request & Response
$request = new Request();
$response = new Response();
$router = new Router($request, $response);

// --------------------------------------------------------------------------
// 5. Define Application Routes
// --------------------------------------------------------------------------

// Root Route - Redirects based on authentication status
Router::get('/', function() {
    if (is_logged_in()) {
        $user = auth();
        redirect(User::getDashboardUrl($user['role'] ?? ''));
    } else {
        redirect('login');
    }
});

// Authentication Routes (Guest Only)
Router::get('/login', 'AuthController@showLogin', [GuestMiddleware::class]);
Router::post('/login', 'AuthController@login', [GuestMiddleware::class]);
Router::post('/auth/whatsapp/send-otp', 'AuthController@sendWhatsAppOtp', [GuestMiddleware::class]);
Router::post('/auth/whatsapp/verify-otp', 'AuthController@verifyWhatsAppOtp', [GuestMiddleware::class]);
Router::get('/logout', 'AuthController@logout');

// Central Dashboard Dispatcher (Redirects to respective role dashboard)
Router::get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);

// Central User Profile Dispatcher (Redirects to respective role profile)
Router::get('/profile', function() {
    $user = auth();
    $role = $user['role'] ?? '';
    redirect(User::getProfileUrl($role));
}, [AuthMiddleware::class]);

// --------------------------------------------------------------------------
// SUPER ADMIN MODULE ROUTES (Strictly Protected for SUPER_ADMIN)
// --------------------------------------------------------------------------
$adminGuard = [
    AuthMiddleware::class,
    [RoleMiddleware::class, ['SUPER_ADMIN']]
];

// 1. Admin Dashboard
Router::get('/admin/dashboard', 'AdminController@dashboard', $adminGuard);

// 2. Users Management
Router::get('/admin/users', 'Admin\\UserController@index', $adminGuard);
Router::post('/admin/users/store', 'Admin\\UserController@store', $adminGuard);
Router::post('/admin/users/update', 'Admin\\UserController@update', $adminGuard);
Router::post('/admin/users/delete', 'Admin\\UserController@delete', $adminGuard);
Router::get('/admin/users/toggle-status', 'Admin\\UserController@toggleStatus', $adminGuard);

// 3. Teachers Management (Guru)
Router::get('/admin/teachers', 'Admin\\TeacherController@index', $adminGuard);
Router::post('/admin/teachers/store', 'Admin\\TeacherController@store', $adminGuard);
Router::post('/admin/teachers/update', 'Admin\\TeacherController@update', $adminGuard);
Router::post('/admin/teachers/delete', 'Admin\\TeacherController@delete', $adminGuard);
Router::get('/admin/teachers/toggle-status', 'Admin\\TeacherController@toggleStatus', $adminGuard);

// 4. Students Management (Peserta Didik)
Router::get('/admin/students', 'Admin\\StudentController@index', $adminGuard);
Router::post('/admin/students/store', 'Admin\\StudentController@store', $adminGuard);
Router::post('/admin/students/update', 'Admin\\StudentController@update', $adminGuard);
Router::post('/admin/students/delete', 'Admin\\StudentController@delete', $adminGuard);
Router::get('/admin/students/toggle-status', 'Admin\\StudentController@toggleStatus', $adminGuard);

// 5. Classes Management (Kelas)
Router::get('/admin/classes', 'Admin\\ClassController@index', $adminGuard);
Router::post('/admin/classes/store', 'Admin\\ClassController@store', $adminGuard);
Router::post('/admin/classes/update', 'Admin\\ClassController@update', $adminGuard);
Router::post('/admin/classes/delete', 'Admin\\ClassController@delete', $adminGuard);
Router::get('/admin/classes/{id}/students', 'Admin\\ClassController@students', $adminGuard);

// 6. Subjects Management (Mata Pelajaran)
Router::get('/admin/subjects', 'Admin\\SubjectController@index', $adminGuard);
Router::post('/admin/subjects/store', 'Admin\\SubjectController@store', $adminGuard);
Router::post('/admin/subjects/update', 'Admin\\SubjectController@update', $adminGuard);
Router::post('/admin/subjects/delete', 'Admin\\SubjectController@delete', $adminGuard);

// Indonesian Route Aliases for Master Management (/admin/guru, /admin/siswa, /admin/kelas, /admin/mapel)
Router::get('/admin/guru', 'Admin\\TeacherController@index', $adminGuard);
Router::post('/admin/guru/store', 'Admin\\TeacherController@store', $adminGuard);
Router::post('/admin/guru/update', 'Admin\\TeacherController@update', $adminGuard);
Router::post('/admin/guru/delete', 'Admin\\TeacherController@delete', $adminGuard);
Router::get('/admin/guru/toggle-status', 'Admin\\TeacherController@toggleStatus', $adminGuard);

Router::get('/admin/siswa', 'Admin\\StudentController@index', $adminGuard);
Router::post('/admin/siswa/store', 'Admin\\StudentController@store', $adminGuard);
Router::post('/admin/siswa/update', 'Admin\\StudentController@update', $adminGuard);
Router::post('/admin/siswa/delete', 'Admin\\StudentController@delete', $adminGuard);
Router::get('/admin/siswa/toggle-status', 'Admin\\StudentController@toggleStatus', $adminGuard);

Router::get('/admin/kelas', 'Admin\\ClassController@index', $adminGuard);
Router::post('/admin/kelas/store', 'Admin\\ClassController@store', $adminGuard);
Router::post('/admin/kelas/update', 'Admin\\ClassController@update', $adminGuard);
Router::post('/admin/kelas/delete', 'Admin\\ClassController@delete', $adminGuard);
Router::get('/admin/kelas/{id}/students', 'Admin\\ClassController@students', $adminGuard);

Router::get('/admin/mapel', 'Admin\\SubjectController@index', $adminGuard);
Router::post('/admin/mapel/store', 'Admin\\SubjectController@store', $adminGuard);
Router::post('/admin/mapel/update', 'Admin\\SubjectController@update', $adminGuard);
Router::post('/admin/mapel/delete', 'Admin\\SubjectController@delete', $adminGuard);

// 7. Settings (Pengaturan Akun & Sistem)
Router::get('/admin/settings', 'Admin\\SettingController@index', $adminGuard);
Router::get('/admin/profile', 'Admin\\SettingController@profile', $adminGuard);
Router::post('/admin/settings/profile', 'Admin\\SettingController@updateProfile', $adminGuard);
Router::post('/admin/settings/password', 'Admin\\SettingController@updatePassword', $adminGuard);

// 7b. WhatsApp Notification Gateway (Fonnte API - Super Admin)
Router::get('/admin/whatsapp', 'Admin\\WhatsAppController@index', $adminGuard);
Router::post('/admin/whatsapp/settings', 'Admin\\WhatsAppController@updateSettings', $adminGuard);
Router::post('/admin/whatsapp/test', 'Admin\\WhatsAppController@sendTest', $adminGuard);
Router::post('/admin/whatsapp/test-otp', 'Admin\\WhatsAppController@sendTestOtp', $adminGuard);
Router::post('/admin/whatsapp/verify-test-otp', 'Admin\\WhatsAppController@verifyTestOtp', $adminGuard);
Router::post('/admin/whatsapp/test-exam', 'Admin\\WhatsAppController@sendTestExam', $adminGuard);
Router::post('/admin/whatsapp/retry/{id}', 'Admin\\WhatsAppController@retry', $adminGuard);

// 7c. AI & Model Configuration (Super Admin)
Router::get('/admin/ai-config', 'Admin\\AIConfigController@index', $adminGuard);
Router::post('/admin/ai-config/update', 'Admin\\AIConfigController@update', $adminGuard);
Router::post('/admin/ai-config/test', 'Admin\\AIConfigController@testConnection', $adminGuard);
Router::get('/admin/ai', 'Admin\\AIConfigController@index', $adminGuard);
Router::get('/admin/konfigurasi-ai', 'Admin\\AIConfigController@index', $adminGuard);
Router::post('/admin/konfigurasi-ai/update', 'Admin\\AIConfigController@update', $adminGuard);

// 8. Exam Configuration (Konfigurasi Ujian - Role Guard: SUPER_ADMIN & GURU)
$adminOrGuruGuard = [
    AuthMiddleware::class,
    [RoleMiddleware::class, ['SUPER_ADMIN', 'GURU']]
];

Router::get('/admin/exam-config', 'Admin\\ExamConfigController@index', $adminOrGuruGuard);
Router::post('/admin/exam-config/update', 'Admin\\ExamConfigController@update', $adminOrGuruGuard);
Router::post('/admin/exam-config/generate-token', 'Admin\\ExamConfigController@generateToken', $adminOrGuruGuard);

// Indonesian Route Aliases for Exam Configuration
Router::get('/admin/konfigurasi-ujian', 'Admin\\ExamConfigController@index', $adminOrGuruGuard);
Router::post('/admin/konfigurasi-ujian/update', 'Admin\\ExamConfigController@update', $adminOrGuruGuard);
Router::post('/admin/konfigurasi-ujian/generate-token', 'Admin\\ExamConfigController@generateToken', $adminOrGuruGuard);

// Guru Direct Aliases for Exam Configuration
Router::get('/guru/exam-config', 'Admin\\ExamConfigController@index', $adminOrGuruGuard);
Router::post('/guru/exam-config/update', 'Admin\\ExamConfigController@update', $adminOrGuruGuard);

// --------------------------------------------------------------------------

// GURU MODULE ROUTES (Strictly Protected for GURU)
// --------------------------------------------------------------------------
$guruGuard = [
    AuthMiddleware::class,
    [RoleMiddleware::class, ['GURU']]
];

// 1. Guru Dashboard
Router::get('/guru/dashboard', 'GuruController@dashboard', $guruGuard);

// 2. Bank Soal (Questions Management)
Router::get('/guru/questions', 'Guru\\QuestionController@index', $guruGuard);
Router::post('/guru/questions/store', 'Guru\\QuestionController@store', $guruGuard);
Router::post('/guru/questions/update', 'Guru\\QuestionController@update', $guruGuard);
Router::post('/guru/questions/delete', 'Guru\\QuestionController@delete', $guruGuard);
Router::get('/guru/questions/preview', 'Guru\\QuestionController@preview', $guruGuard);

// 3. Guru Profile & Settings
Router::get('/guru/profile', 'Guru\\ProfileController@index', $guruGuard);
Router::post('/guru/profile', 'Guru\\ProfileController@updateProfile', $guruGuard);
Router::post('/guru/profile/password', 'Guru\\ProfileController@updatePassword', $guruGuard);

// 4. Manajemen Ujian (Exam Management)
Router::get('/guru/exams', 'Guru\\ExamController@index', $guruGuard);
Router::get('/guru/exams/create', 'Guru\\ExamController@create', $guruGuard);
Router::post('/guru/exams/store', 'Guru\\ExamController@store', $guruGuard);
Router::get('/guru/exams/show', 'Guru\\ExamController@show', $guruGuard);
Router::get('/guru/exams/edit', 'Guru\\ExamController@edit', $guruGuard);
Router::post('/guru/exams/update', 'Guru\\ExamController@update', $guruGuard);
Router::post('/guru/exams/delete', 'Guru\\ExamController@delete', $guruGuard);
Router::post('/guru/exams/toggle-status', 'Guru\\ExamController@toggleStatus', $guruGuard);
Router::get('/guru/exams/get-questions', 'Guru\\ExamController@getQuestions', $guruGuard);

// 5. Penilaian & Hasil Ujian (Exam Results & Grading)
Router::get('/guru/results', 'Guru\\ResultController@index', $guruGuard);
Router::get('/guru/results/export', 'Guru\\ResultController@exportCsv', $guruGuard);
Router::get('/guru/results/{exam_id}', 'Guru\\ResultController@show', $guruGuard);
Router::get('/guru/results/{exam_id}/{student_id}', 'Guru\\ResultController@studentDetail', $guruGuard);

// 6. Monitoring & AI Proctoring
Router::get('/guru/monitoring', 'Guru\\MonitoringController@index', $guruGuard);
Router::post('/guru/monitoring/review', 'Guru\\MonitoringController@updateReview', $guruGuard);
Router::post('/guru/monitoring/proctor-camera', 'Guru\\MonitoringController@uploadProctorCamera', $guruGuard);
Router::post('/guru/monitoring/capture-evidence', 'Guru\\MonitoringController@captureEvidence', $guruGuard);
Router::get('/guru/monitoring/live-data/{exam_id}', 'Guru\\MonitoringController@liveData', $guruGuard);
Router::get('/guru/monitoring/camera-frame/{exam_id}/{session_id}/{cam}', 'Guru\\MonitoringController@cameraFrame', $guruGuard);
Router::get('/guru/monitoring/evidence', 'Guru\\MonitoringController@serveEvidence', $guruGuard);
Router::get('/guru/monitoring/evidence/{filename}', 'Guru\\MonitoringController@serveEvidence', $guruGuard);
Router::get('/guru/monitoring/{exam_id}', 'Guru\\MonitoringController@examSessions', $guruGuard);
Router::get('/guru/monitoring/{exam_id}/{session_id}', 'Guru\\MonitoringController@sessionDetail', $guruGuard);
Router::post('/guru/monitoring/{exam_id}/session/{session_id}/delete', 'Guru\\MonitoringController@deleteSession', $guruGuard);

// 6b. Guru Camera Check & Supervision Readiness
Router::get('/guru/camera-check', 'Guru\\CameraController@check', $guruGuard);
Router::post('/guru/camera-check/verify', 'Guru\\CameraController@verify', $guruGuard);
Router::get('/guru/camera-check/snapshot', 'Guru\\CameraController@previewImage', $guruGuard);

// 7. Guru Additional Modules (Placeholders)
Router::get('/guru/students', 'GuruController@students', $guruGuard);

// --------------------------------------------------------------------------
// PESERTA ROUTES (Strictly Protected for PESERTA)
// --------------------------------------------------------------------------
$pesertaGuard = [
    AuthMiddleware::class,
    [RoleMiddleware::class, ['PESERTA']]
];

// 1. Peserta Dashboard
Router::get('/peserta/dashboard', 'PesertaController@dashboard', $pesertaGuard);

// 2. Exam Engine (Sistem Ujian Siswa)
Router::get('/peserta/exams', 'Peserta\\ExamEngineController@index', $pesertaGuard);
Router::get('/peserta/exams/detail', 'Peserta\\ExamEngineController@detail', $pesertaGuard);
Router::post('/peserta/exams/start', 'Peserta\\ExamEngineController@start', $pesertaGuard);
Router::get('/peserta/exams/room', 'Peserta\\ExamEngineController@room', $pesertaGuard);
Router::post('/peserta/exams/save-answer', 'Peserta\\ExamEngineController@saveAnswer', $pesertaGuard);
Router::post('/peserta/exams/heartbeat', 'Peserta\\ExamEngineController@heartbeat', $pesertaGuard);
Router::post('/peserta/exams/camera-event', 'Peserta\\ExamEngineController@logCameraEvent', $pesertaGuard);
Router::post('/peserta/exams/camera-snapshot', 'Peserta\\ExamEngineController@saveCameraSnapshot', $pesertaGuard);
Router::get('/peserta/exams/proctor-camera', 'Peserta\\ExamEngineController@getProctorCameraFrame', $pesertaGuard);
Router::post('/peserta/exams/proctor-event', 'Peserta\\ExamEngineController@logProctorEvent', $pesertaGuard);
Router::post('/peserta/exams/finish', 'Peserta\\ExamEngineController@finish', $pesertaGuard);
Router::get('/peserta/exams/result', 'Peserta\\ExamEngineController@result', $pesertaGuard);

// 3. Hasil & Riwayat Ujian Peserta
Router::get('/peserta/results', 'Peserta\\ResultController@index', $pesertaGuard);
Router::get('/peserta/results/{exam_id}', 'Peserta\\ResultController@show', $pesertaGuard);

// 4. Profil Peserta Didik & Pengaturan Akun
Router::get('/peserta/profile', 'Peserta\\ProfileController@index', $pesertaGuard);
Router::post('/peserta/profile', 'Peserta\\ProfileController@updateProfile', $pesertaGuard);
Router::post('/peserta/profile/password', 'Peserta\\ProfileController@updatePassword', $pesertaGuard);

// 5. Cek Kamera Peserta Didik
Router::get('/peserta/camera-check', 'Peserta\\CameraController@check', $pesertaGuard);
Router::post('/peserta/camera-check/verify', 'Peserta\\CameraController@verify', $pesertaGuard);
Router::get('/peserta/camera-check/snapshot', 'Peserta\\CameraController@previewImage', $pesertaGuard);

// --------------------------------------------------------------------------
// NOTIFICATION SYSTEM ROUTES (Web & AJAX API)
// --------------------------------------------------------------------------
Router::get('/notifications', 'NotificationController@index', [AuthMiddleware::class]);
Router::post('/notifications/read/{id}', 'NotificationController@markRead', [AuthMiddleware::class]);
Router::post('/notifications/mark-all-read', 'NotificationController@markAllRead', [AuthMiddleware::class]);
Router::get('/api/notifications/recent', 'NotificationController@apiRecent', [AuthMiddleware::class]);
Router::get('/api/notifications/unread-count', 'NotificationController@apiUnreadCount', [AuthMiddleware::class]);
Router::post('/api/notifications/{id}/read', 'NotificationController@apiMarkRead', [AuthMiddleware::class]);
Router::post('/api/notifications/mark-all-read', 'NotificationController@apiMarkAllRead', [AuthMiddleware::class]);

// Web Push API Routes (Service Worker & Push Manager)
Router::get('/api/push/vapid-public-key', 'NotificationController@apiVapidPublicKey', [AuthMiddleware::class]);
Router::post('/api/push/subscribe', 'NotificationController@apiPushSubscribe', [AuthMiddleware::class]);
Router::post('/api/push/unsubscribe', 'NotificationController@apiPushUnsubscribe', [AuthMiddleware::class]);
Router::get('/api/push/status', 'NotificationController@apiPushStatus', [AuthMiddleware::class]);

// --------------------------------------------------------------------------
// AI CHATBOT ROUTES (NVIDIA Provider & Guardrails Protected)
// --------------------------------------------------------------------------
Router::post('/api/chat/global', 'ChatbotController@chatGlobal', [AuthMiddleware::class]);
Router::post('/api/chat/explain-question', 'ChatbotController@explainQuestion', [AuthMiddleware::class]);

// --------------------------------------------------------------------------
// 6. Dispatch the Router
// --------------------------------------------------------------------------
try {
    $router->dispatch();
} catch (\Throwable $e) {
    // Graceful error display
    http_response_code(500);
    echo '<div style="font-family:sans-serif;padding:30px;background:#fff1f2;border:1px solid #fecdd3;border-radius:8px;max-width:800px;margin:40px auto;color:#9f1239;">';
    echo '<h2 style="margin-top:0;">Terjadi Kesalahan Sistem (500)</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<pre style="background:#881337;color:#fff;padding:15px;border-radius:6px;overflow:auto;font-size:12px;">' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    echo '</div>';
}
