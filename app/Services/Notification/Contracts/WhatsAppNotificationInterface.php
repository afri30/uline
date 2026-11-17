<?php

declare(strict_types=1);

namespace App\Services\Notification\Contracts;

/**
 * Interface WhatsAppNotificationInterface
 * Kontrak standar layanan notifikasi WhatsApp Business Platform (Cloud API & Provider)
 */
interface WhatsAppNotificationInterface
{
    /**
     * Kirim pesan notifikasi berbasis template resmi Meta WhatsApp Business
     *
     * @param string $toPhoneNumber Nomor telepon penerima (format internasional E.164)
     * @param string $templateName Nama template yang disetujui Meta
     * @param array $parameters Parameter isi pesan variabel template
     * @param int|null $userId ID pengguna pemilik nomor (opsional)
     * @param string|null $idempotencyKey Kunci unik pencegah pesan ganda
     * @return array Status dan hasil pengiriman ['success' => bool, 'status' => string, 'message' => string, ...]
     */
    public function sendTemplate(
        string $toPhoneNumber,
        string $templateName,
        array $parameters = [],
        ?int $userId = null,
        ?string $idempotencyKey = null
    ): array;

    /**
     * Kirim pemberitahuan jadwal ujian kepada peserta didik / orang tua
     */
    public function sendExamScheduleNotice(
        int $userId,
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        string $scheduleTime
    ): array;

    /**
     * Kirim pemberitahuan pengumuman hasil nilai ujian yang telah selesai
     */
    public function sendExamResultNotice(
        int $userId,
        string $phoneNumber,
        string $studentName,
        string $examTitle,
        float $score
    ): array;

    /**
     * Cek apakah kredensial WhatsApp API telah lengkap dan aktif di sistem
     */
    public function isConfigured(): bool;

    /**
     * Normalisasi nomor telepon ke format E.164 (+62xxxxxxxxxx)
     */
    public function formatPhoneNumber(string $phone): ?string;
}
