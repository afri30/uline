<?php

declare(strict_types=1);

namespace App\Controllers\Guru;

use App\Core\Controller;
use App\Models\Teacher;
use App\Models\Exam;
use App\Services\Proctoring\CameraStreamService;
use App\Services\Proctoring\ProctoringService;
use Exception;

class CameraController extends Controller
{
    private CameraStreamService $cameraService;
    private Teacher $teacherModel;
    private Exam $examModel;
    private ProctoringService $proctoringService;

    public function __construct()
    {
        parent::__construct();
        $this->cameraService = new CameraStreamService();
        $this->teacherModel = new Teacher();
        $this->examModel = new Exam();
        $this->proctoringService = new ProctoringService();
    }

    /**
     * Display camera check & supervision camera center for Guru (/guru/camera-check)
     */
    public function check(): void
    {
        $user = auth();
        $userId = (int)($user['id'] ?? 0);
        $teacher = $this->teacherModel->findByUserId($userId);

        if (!$teacher) {
            http_response_code(403);
            die("Akun Anda belum terdaftar sebagai Guru.");
        }

        $exams = $this->examModel->getByTeacherId((int)$teacher['id'], [], 30, 0);
        foreach ($exams as &$ex) {
            $overview = $this->proctoringService->getExamMonitoringOverview((int)$ex['id']);
            $ex['online_count'] = $overview['summary']['online_count'] ?? 0;
            $ex['total_participants'] = $overview['summary']['total_participants'] ?? 0;
            $ex['proctor_cam_live'] = $this->cameraService->isProctorCameraLive((int)$ex['id']);
        }
        unset($ex);

        $checkInData = $this->cameraService->getCheckInStatus('GURU', $userId);

        $this->view('guru/camera/check', [
            'title' => 'Cek & Monitor Kamera Guru - AI Online Exam',
            'user' => $user,
            'teacher' => $teacher,
            'exams' => $exams,
            'checkInData' => $checkInData
        ]);
    }

    /**
     * Verify and save Guru camera readiness snapshot (/guru/camera-check/verify)
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
                'cam1_label' => $this->request->string('cam1_label', 'Kamera Pengawas'),
                'cam2_label' => $this->request->string('cam2_label', ''),
                'resolution' => $this->request->string('resolution', '-'),
            ];
            $saved = $this->cameraService->saveCheckInStatus('GURU', $userId, $meta, $imageBase64 ?: null);

            $this->json([
                'status' => 'success',
                'message' => 'Kamera pengawas Guru berhasil diverifikasi.',
                'data' => $saved
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Serve logged-in teacher's own verification snapshot (/guru/camera-check/snapshot)
     */
    public function previewImage(): void
    {
        $user = auth();
        $userId = (int)($user['id'] ?? 0);
        $path = $this->cameraService->getFramePath('checkin_GURU', $userId, 'camera_1');
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

