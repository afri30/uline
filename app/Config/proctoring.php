<?php

declare(strict_types=1);

use App\Core\Env;

if (class_exists(Env::class)) {
    Env::load();
}

/**
 * AI Proctoring, WebRTC Dual Camera, and Risk Scoring Configuration
 */
return [
    'dual_camera' => [
        'enabled' => function_exists('env') ? (bool)env('PROCTORING_DUAL_CAMERA', true) : true,
        'camera_1' => [
            'type' => 'camera_1',
            'label' => 'Face Camera (Kamera Wajah)',
            'required' => true,
        ],
        'camera_2' => [
            'type' => 'camera_2',
            'label' => 'Desk Camera (Kamera Meja & Lingkungan)',
            'required' => false,
            'fallback_to_single' => true,
        ],
    ],

    // Severity weights for AI anomaly detection
    'severity_weights' => [
        'INFO' => 1,
        'WARNING' => 5,
        'HIGH' => 15,
    ],

    // Risk score thresholds (0 - 100)
    'risk_thresholds' => [
        'low_max' => 20,
        'medium_max' => 50,
        'categories' => [
            'LOW' => [
                'min' => 0,
                'max' => 20,
                'label' => 'Rendah (Aman)',
                'color' => 'success',
                'badge_class' => 'bg-success-subtle text-success border border-success',
                'requires_review' => false,
            ],
            'MEDIUM' => [
                'min' => 21,
                'max' => 50,
                'label' => 'Sedang (Perlu Perhatian)',
                'color' => 'warning',
                'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning',
                'requires_review' => false,
            ],
            'HIGH' => [
                'min' => 51,
                'max' => 100,
                'label' => 'Tinggi (Memerlukan Reviu)',
                'color' => 'danger',
                'badge_class' => 'bg-danger-subtle text-danger border border-danger',
                'requires_review' => true,
            ],
        ],
    ],

    // Proctoring anomaly events
    'events' => [
        'FACE_NOT_DETECTED' => [
            'severity' => 'WARNING',
            'description' => 'Wajah peserta tidak terdeteksi pada kamera.',
        ],
        'MULTIPLE_FACES' => [
            'severity' => 'HIGH',
            'description' => 'Terdeteksi lebih dari satu wajah di depan kamera.',
        ],
        'LOOKING_AWAY' => [
            'severity' => 'WARNING',
            'description' => 'Peserta terdeteksi menoleh ke luar area layar ujian.',
        ],
        'CAMERA_DISCONNECTED' => [
            'severity' => 'HIGH',
            'description' => 'Aliran video kamera terputus atau dinonaktifkan.',
        ],
        'TAB_SWITCHED' => [
            'severity' => 'WARNING',
            'description' => 'Peserta beralih ke tab atau jendela lain di browser.',
        ],
        'SUSPICIOUS_ACTIVITY' => [
            'severity' => 'HIGH',
            'description' => 'Aktivitas mencurigakan terdeteksi pada area meja ujian.',
        ],
    ],

    // Heartbeat monitoring
    'heartbeat' => [
        'interval_seconds' => function_exists('env') ? (int)env('PROCTORING_HEARTBEAT_INTERVAL', 15) : 15,
        'offline_threshold_seconds' => function_exists('env') ? (int)env('PROCTORING_OFFLINE_THRESHOLD', 45) : 45,
    ],

    // Teacher Review statuses
    'review_statuses' => [
        'pending' => 'Pending (Menunggu Reviu)',
        'reviewed' => 'Reviewed (Telah Ditinjau)',
        'confirmed' => 'Confirmed (Pelanggaran Terkonfirmasi)',
        'dismissed' => 'Dismissed (Diabaikan / False Alarm)',
    ],
];
