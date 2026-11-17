<?php

declare(strict_types=1);

namespace App\Controllers\Peserta;

use App\Core\Controller;
use App\Models\Student;
use App\Models\Exam;
use App\Services\Proctoring\CameraStreamService;
use Exception;

class CameraController extends Controller
{
    private CameraStreamService $cameraService;
    private Student $studentModel;
    private Exam $examModel;

    public function __construct()
    {
        parent::__construct();
        $this->cameraService = new CameraStreamService();
        $this->studentModel = new Student();
        $this->examModel = new Exam();
    }

    /**
     * Display pre-exam / standalone camera check page for Peserta (/peserta/camera-check)
     */
    public function check(): void
    {
        $user = auth();
        $userId = (int)($user['id'] ?? 0);
        $student = $this->studentModel->findByUserIdWithClass($userId);

        $availableExams = [];
        if ($student && !empty($student['class_id'])) {
            $availableExams = $this->examModel->getAvailableForStudentClass(
                (int)$student['class_id'],
                (int)$student['id']
            );
        }

        $checkInData = $this->cameraService->getCheckInStatus('PESERTA', $userId);

        $this->view('peserta/camera/check', [
            'title' => 'Cek & Uji Kamera Peserta - AI Online Exam',
            'user' => $user,
            'student' => $student,
            'availableExams' => $availableExams,
            'checkInData' => $checkInData
        ]);
    }

    /**
     * Save and verify student test camera snapshot (/peserta/camera-check/verify)
     */
    public function verify(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            $this->json(['status' => 'error', 'message' => 'Token CSRF tidak valid.'], 403);
            return;
        }

        $user = auth();
        $userId = (int)($user['id'] ?? 0);
        $imageBase64 = (string)$this->request->input('image_base64', '');

        try {
            $meta = [
                'camera_count' => (int)$this->request->input('camera_count', 1),
                'cam1_label' => $this->request->string('cam1_label', 'Kamera Utama'),
                'cam2_label' => $this->request->string('cam2_label', ''),
                'resolution' => $this->request->string('resolution', '-'),
            ];
            $saved = $this->cameraService->saveCheckInStatus('PESERTA', $userId, $meta, $imageBase64 ?: null);

            $this->json([
                'status' => 'success',
                'message' => 'Kamera peserta berhasil diverifikasi.',
                'data' => $saved
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Serve logged-in student's own test snapshot safely (/peserta/camera-check/snapshot)
     */
    public function previewImage(): void
    {
        $user = auth();
        $userId = (int)($user['id'] ?? 0);
        $path = $this->cameraService->getFramePath('checkin_PESERTA', $userId, 'camera_1');
        $img = $path ? $this->cameraService->readImageForHttp($path) : null;

        if (!$img) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $img['mime']);
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $img['data'];
        exit;
    }
}

