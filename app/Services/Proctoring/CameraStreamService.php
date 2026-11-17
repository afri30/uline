<?php

declare(strict_types=1);

namespace App\Services\Proctoring;

use Exception;

class CameraStreamService
{
    private string $cameraDir;
    private string $evidenceDir;

    public const MAX_IMAGE_BYTES = 512000; // 500 KB max per snapshot

    public function __construct(?string $baseStoragePath = null)
    {
        $storageRoot = $baseStoragePath ?? (dirname(__DIR__, 3) . '/storage');
        $this->cameraDir = rtrim($storageRoot, '/\\') . DIRECTORY_SEPARATOR . 'cameras';
        $this->evidenceDir = rtrim($storageRoot, '/\\') . DIRECTORY_SEPARATOR . 'evidence';

        $this->ensureDirectory($this->cameraDir);
        $this->ensureDirectory($this->evidenceDir);
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Decode and validate base64 image data URI against MIME & binary magic bytes
     *
     * @return array{binary: string, ext: string, mime: string}
     * @throws Exception
     */
    private function decodeAndValidateImage(string $base64Data): array
    {
        $raw = trim($base64Data);
        if (preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.+)$#i', $raw, $m)) {
            $raw = $m[2];
        }

        $binary = base64_decode($raw, true);
        if ($binary === false || strlen($binary) < 64) {
            throw new Exception('Format gambar kamera tidak valid atau korup.');
        }

        if (strlen($binary) > self::MAX_IMAGE_BYTES) {
            throw new Exception('Ukuran snapshot kamera melebihi batas maksimum 500 KB.');
        }

        // Verify actual image magic bytes
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return ['binary' => $binary, 'ext' => 'jpg', 'mime' => 'image/jpeg'];
        }
        if (str_starts_with($binary, "\x89PNG\r\n\x1A\n")) {
            return ['binary' => $binary, 'ext' => 'png', 'mime' => 'image/png'];
        }
        if (substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
            return ['binary' => $binary, 'ext' => 'webp', 'mime' => 'image/webp'];
        }

        throw new Exception('Tipe berkas gambar tidak didukung (hanya JPEG/PNG/WebP).');
    }

    /**
     * Normalize category & camera type to safe alphanumeric filenames
     */
    private function buildFrameFilename(string $category, int $targetId, string $cameraType): string
    {
        $safeCategory = preg_replace('/[^a-zA-Z0-9_]/', '', $category) ?: 'session';
        $safeCam = preg_replace('/[^a-zA-Z0-9_]/', '', $cameraType) ?: 'camera_1';
        $safeId = max(0, $targetId);

        return "{$safeCategory}_{$safeId}_{$safeCam}.jpg";
    }

    /**
     * Atomically write binary file so concurrent HTTP readers never read partial frames
     */
    private function writeAtomic(string $destPath, string $content): void
    {
        $tmpPath = $destPath . '.tmp.' . uniqid('', true);
        if (file_put_contents($tmpPath, $content, LOCK_EX) === false) {
            throw new Exception('Gagal menulis berkas kamera ke penyimpanan.');
        }
        @rename($tmpPath, $destPath);
        if (!file_exists($destPath) && file_exists($tmpPath)) {
            @unlink($destPath);
            @rename($tmpPath, $destPath);
        }
    }

    /**
     * Save live WebRTC frame snapshot for student session or guru proctor exam
     */
    public function saveBase64Snapshot(string $base64Data, string $category, int $targetId, string $cameraType = 'camera_1'): array
    {
        $decoded = $this->decodeAndValidateImage($base64Data);
        $filename = $this->buildFrameFilename($category, $targetId, $cameraType);
        $destPath = $this->cameraDir . DIRECTORY_SEPARATOR . $filename;

        $this->writeAtomic($destPath, $decoded['binary']);

        return [
            'filename' => $filename,
            'path' => $destPath,
            'size' => strlen($decoded['binary']),
            'mime' => $decoded['mime'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get absolute path to latest frame if it exists
     */
    public function getFramePath(string $category, int $targetId, string $cameraType = 'camera_1'): ?string
    {
        $filename = $this->buildFrameFilename($category, $targetId, $cameraType);
        $path = $this->cameraDir . DIRECTORY_SEPARATOR . $filename;

        return (file_exists($path) && is_file($path)) ? $path : null;
    }

    /**
     * Get age of latest frame in seconds (or null if no frame exists)
     */
    public function getFrameAgeSeconds(string $category, int $targetId, string $cameraType = 'camera_1'): ?int
    {
        $path = $this->getFramePath($category, $targetId, $cameraType);
        if ($path === null) {
            return null;
        }
        clearstatcache(true, $path);
        $mtime = @filemtime($path);
        return ($mtime !== false) ? max(0, time() - $mtime) : null;
    }

    /**
     * Delete latest frame (e.g. when Guru turns off supervision camera)
     */
    public function removeFrame(string $category, int $targetId, string $cameraType = 'camera_1'): void
    {
        $path = $this->getFramePath($category, $targetId, $cameraType);
        if ($path !== null) {
            @unlink($path);
        }
    }

    /**
     * Check whether Guru Proctor Camera is actively streaming for an exam
     */
    public function isProctorCameraLive(int $examId, int $maxAgeSeconds = 25): bool
    {
        $age = $this->getFrameAgeSeconds('proctor_exam', $examId, 'camera_1');
        return $age !== null && $age <= $maxAgeSeconds;
    }

    /**
     * Copy current live frame into permanent timestamped evidence directory
     *
     * @return string|null Relative protected route segment filename (e.g. "ev_session_1_camera_1_1730000000.jpg")
     */
    public function copyLiveFrameToEvidence(int $sessionId, string $cameraType = 'camera_1', string $prefix = 'ev'): ?string
    {
        $livePath = $this->getFramePath('session', $sessionId, $cameraType);
        if ($livePath === null) {
            return null;
        }

        $safePrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'ev';
        $safeCam = preg_replace('/[^a-zA-Z0-9_]/', '', $cameraType) ?: 'camera_1';
        $filename = "{$safePrefix}_session_{$sessionId}_{$safeCam}_" . date('Ymd_His') . '_' . substr((string)mt_rand(1000, 9999), 0, 4) . '.jpg';
        $destPath = $this->evidenceDir . DIRECTORY_SEPARATOR . $filename;

        $content = @file_get_contents($livePath);
        if ($content === false) {
            return null;
        }

        $this->writeAtomic($destPath, $content);
        return $filename;
    }

    /**
     * Save base64 snapshot directly as evidence file
     */
    public function saveBase64Evidence(string $base64Data, int $sessionId, string $cameraType = 'camera_1'): ?string
    {
        $decoded = $this->decodeAndValidateImage($base64Data);
        $safeCam = preg_replace('/[^a-zA-Z0-9_]/', '', $cameraType) ?: 'camera_1';
        $filename = "ev_session_{$sessionId}_{$safeCam}_" . date('Ymd_His') . '_' . substr((string)mt_rand(1000, 9999), 0, 4) . '.' . $decoded['ext'];
        $destPath = $this->evidenceDir . DIRECTORY_SEPARATOR . $filename;

        $this->writeAtomic($destPath, $decoded['binary']);
        return $filename;
    }

    /**
     * Safely resolve evidence image filename (prevents path traversal)
     */
    public function getEvidenceFilePath(string $filename): ?string
    {
        $safe = basename($filename);
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|webp)$/i', $safe)) {
            return null;
        }
        $fullPath = $this->evidenceDir . DIRECTORY_SEPARATOR . $safe;
        return (file_exists($fullPath) && is_file($fullPath)) ? $fullPath : null;
    }

    /**
     * Persist pre-flight / self camera check verification metadata & optional snapshot
     */
    public function saveCheckInStatus(string $role, int $userId, array $meta, ?string $base64Image = null): array
    {
        $safeRole = preg_replace('/[^A-Z0-9_]/', '', strtoupper($role)) ?: 'USER';
        $safeId = max(0, $userId);

        $hasSnapshot = false;
        if (!empty($base64Image)) {
            $this->saveBase64Snapshot($base64Image, "checkin_{$safeRole}", $safeId, 'camera_1');
            $hasSnapshot = true;
        }

        $payload = [
            'role' => $safeRole,
            'user_id' => $safeId,
            'verified' => true,
            'camera_count' => (int)($meta['camera_count'] ?? 1),
            'cam1_label' => substr(trim((string)($meta['cam1_label'] ?? 'Kamera Utama')), 0, 120),
            'cam2_label' => substr(trim((string)($meta['cam2_label'] ?? '')), 0, 120),
            'resolution' => substr(trim((string)($meta['resolution'] ?? '-')), 0, 40),
            'has_snapshot' => $hasSnapshot || ($this->getFramePath("checkin_{$safeRole}", $safeId, 'camera_1') !== null),
            'checked_at' => date('Y-m-d H:i:s'),
        ];

        $metaPath = $this->cameraDir . DIRECTORY_SEPARATOR . "checkin_{$safeRole}_{$safeId}.json";
        $this->writeAtomic($metaPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $payload;
    }

    /**
     * Retrieve latest camera check verification metadata for user
     */
    public function getCheckInStatus(string $role, int $userId): ?array
    {
        $safeRole = preg_replace('/[^A-Z0-9_]/', '', strtoupper($role)) ?: 'USER';
        $safeId = max(0, $userId);
        $metaPath = $this->cameraDir . DIRECTORY_SEPARATOR . "checkin_{$safeRole}_{$safeId}.json";

        if (!file_exists($metaPath)) {
            return null;
        }
        $decoded = json_decode((string)@file_get_contents($metaPath), true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Read image file and determine safe MIME type for authenticated HTTP delivery
     *
     * @return array{mime: string, data: string}|null
     */
    public function readImageForHttp(string $filePath): ?array
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return null;
        }
        $data = @file_get_contents($filePath);
        if ($data === false || strlen($data) === 0) {
            return null;
        }

        $mime = 'image/jpeg';
        if (str_starts_with($data, "\x89PNG\r\n\x1A\n")) {
            $mime = 'image/png';
        } elseif (substr($data, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP') {
            $mime = 'image/webp';
        }

        return ['mime' => $mime, 'data' => $data];
    }
}
