<?php

declare(strict_types=1);

namespace App\Services\Proctoring;

interface DetectionServiceInterface
{
    public function evaluateRawEvent(string $rawEventType, ?array $rawMetadata = null): array;
    public function getEventTypesCatalog(): array;
}

class DetectionService implements DetectionServiceInterface
{
    public const EVENT_FACE_NOT_DETECTED = 'FACE_NOT_DETECTED';
    public const EVENT_MULTIPLE_FACES = 'MULTIPLE_FACES';
    public const EVENT_CAMERA_DISCONNECTED = 'CAMERA_DISCONNECTED';
    public const EVENT_LOOKING_AWAY = 'LOOKING_AWAY';
    public const EVENT_SUSPICIOUS_ACTIVITY = 'SUSPICIOUS_ACTIVITY';
    public const EVENT_TAB_SWITCHED = 'TAB_SWITCHED';

    /**
     * Catalogue of all detectable proctoring events
     */
    public function getEventTypesCatalog(): array
    {
        return [
            self::EVENT_FACE_NOT_DETECTED => [
                'name' => 'Wajah Tidak Terdeteksi (Face Not Detected)',
                'default_severity' => 'WARNING',
                'default_confidence' => 0.92,
                'description' => 'Peserta meninggalkan frame kamera atau wajah terhalangi objek.',
                'icon' => 'bi-person-x-fill',
                'badge' => 'warning'
            ],
            self::EVENT_MULTIPLE_FACES => [
                'name' => 'Terdeteksi Lebih Dari Satu Wajah (Multiple Faces)',
                'default_severity' => 'HIGH',
                'default_confidence' => 0.97,
                'description' => 'Terdapat orang lain atau wajah kedua yang masuk ke dalam area pandang kamera.',
                'icon' => 'bi-people-fill',
                'badge' => 'danger'
            ],
            self::EVENT_CAMERA_DISCONNECTED => [
                'name' => 'Koneksi Kamera Terputus (Camera Disconnected)',
                'default_severity' => 'HIGH',
                'default_confidence' => 1.0,
                'description' => 'Aliran video kamera terhenti, dicabut, atau dinonaktifkan.',
                'icon' => 'bi-camera-video-off-fill',
                'badge' => 'danger'
            ],
            self::EVENT_LOOKING_AWAY => [
                'name' => 'Pandangan Mengarah Keluar Layar (Looking Away)',
                'default_severity' => 'WARNING',
                'default_confidence' => 0.84,
                'description' => 'Arah pandangan mata atau kepala peserta menoleh ke samping/bawah secara mencurigakan.',
                'icon' => 'bi-eye-slash-fill',
                'badge' => 'warning'
            ],
            self::EVENT_SUSPICIOUS_ACTIVITY => [
                'name' => 'Aktivitas Gerakan Mencurigakan (Suspicious Activity)',
                'default_severity' => 'WARNING',
                'default_confidence' => 0.78,
                'description' => 'Gerakan tangan berulang atau upaya menyembunyikan media lain di sekitar meja.',
                'icon' => 'bi-exclamation-triangle-fill',
                'badge' => 'warning'
            ],
            self::EVENT_TAB_SWITCHED => [
                'name' => 'Berpindah Tab / Aplikasi (Tab / Window Switched)',
                'default_severity' => 'WARNING',
                'default_confidence' => 1.0,
                'description' => 'Peserta meminimalkan browser atau berpindah ke tab/jendela aplikasi lain.',
                'icon' => 'bi-window-stack',
                'badge' => 'warning'
            ]
        ];
    }

    /**
     * Evaluate incoming raw detection data and standardize event payload
     */
    public function evaluateRawEvent(string $rawEventType, ?array $rawMetadata = null): array
    {
        $catalog = $this->getEventTypesCatalog();
        $normalizedType = strtoupper(trim($rawEventType));

        // Fallback for custom or unmapped types
        if (!isset($catalog[$normalizedType])) {
            // Map common aliases
            if (stripos($normalizedType, 'DISCONNECT') !== false) {
                $normalizedType = self::EVENT_CAMERA_DISCONNECTED;
            } elseif (stripos($normalizedType, 'MULTIPLE') !== false) {
                $normalizedType = self::EVENT_MULTIPLE_FACES;
            } elseif (stripos($normalizedType, 'LOOK') !== false || stripos($normalizedType, 'GAZE') !== false) {
                $normalizedType = self::EVENT_LOOKING_AWAY;
            } elseif (stripos($normalizedType, 'TAB') !== false || stripos($normalizedType, 'BLUR') !== false) {
                $normalizedType = self::EVENT_TAB_SWITCHED;
            } elseif (stripos($normalizedType, 'FACE') !== false || stripos($normalizedType, 'NO_FACE') !== false) {
                $normalizedType = self::EVENT_FACE_NOT_DETECTED;
            } else {
                $normalizedType = self::EVENT_SUSPICIOUS_ACTIVITY;
            }
        }

        $metaInfo = $catalog[$normalizedType] ?? [
            'name' => $normalizedType,
            'default_severity' => 'INFO',
            'default_confidence' => 0.80,
            'description' => 'Event pengawasan terdeteksi.',
            'icon' => 'bi-info-circle-fill',
            'badge' => 'info'
        ];

        // Read or override severity/confidence from metadata if provided
        $severity = $rawMetadata['severity'] ?? $metaInfo['default_severity'];
        $confidence = isset($rawMetadata['confidence']) ? (float)$rawMetadata['confidence'] : $metaInfo['default_confidence'];

        return [
            'event_type' => $normalizedType,
            'event_name' => $metaInfo['name'],
            'severity' => strtoupper($severity),
            'confidence' => $confidence,
            'description' => $metaInfo['description'],
            'icon' => $metaInfo['icon'],
            'metadata' => $rawMetadata
        ];
    }

    /**
     * Check if event type is recognized in catalog
     */
    public function isValidEventType(string $eventType): bool
    {
        $catalog = $this->getEventTypesCatalog();
        return isset($catalog[strtoupper(trim($eventType))]);
    }

    /**
     * Get default severity for an event type
     */
    public function getDefaultSeverity(string $eventType): string
    {
        $catalog = $this->getEventTypesCatalog();
        $type = strtoupper(trim($eventType));
        return $catalog[$type]['default_severity'] ?? 'INFO';
    }

    /**
     * Rule-based heuristic evaluation for client/sensor indicators
     */
    public function evaluateHeuristic(string $category, array $params): ?array
    {
        if ($category === 'face_count') {
            $count = (int)($params['count'] ?? 1);
            $duration = (float)($params['duration_seconds'] ?? 0);

            if ($count === 0 && $duration >= 5.0) {
                return [
                    'event_type' => self::EVENT_FACE_NOT_DETECTED,
                    'severity' => 'WARNING',
                    'confidence' => 0.92,
                    'reason' => "Wajah tidak terdeteksi selama {$duration} detik."
                ];
            } elseif ($count > 1) {
                return [
                    'event_type' => self::EVENT_MULTIPLE_FACES,
                    'severity' => 'HIGH',
                    'confidence' => 0.97,
                    'reason' => "Terdeteksi {$count} wajah dalam frame kamera."
                ];
            }
        } elseif ($category === 'visibility') {
            if (!empty($params['tab_hidden'])) {
                return [
                    'event_type' => self::EVENT_TAB_SWITCHED,
                    'severity' => 'WARNING',
                    'confidence' => 1.0,
                    'reason' => 'Jendela ujian kehilangan fokus atau tab browser diganti.'
                ];
            }
        }

        return null;
    }
}
