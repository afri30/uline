<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Notification;
use App\Core\Database;
use PDO;
use Exception;

class NotificationService
{
    private Notification $notificationModel;
    private PDO $db;
    private WhatsAppService $whatsAppService;
    private FonnteService $fonnteService;
    private WebPushService $webPushService;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->db = Database::getConnection();
        $this->whatsAppService = new WhatsAppService();
        $this->fonnteService = new FonnteService();
        $this->webPushService = new WebPushService();
    }

    public function getWhatsAppService(): WhatsAppService
    {
        return $this->whatsAppService;
    }

    public function getFonnteService(): FonnteService
    {
        return $this->fonnteService;
    }

    public function getWebPushService(): WebPushService
    {
        return $this->webPushService;
    }

    /**
     * Send general notification to a user with deduplication
     */
    public function send(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null,
        ?string $refType = null,
        ?int $refId = null
    ): ?int {
        $notifId = $this->notificationModel->createIfNotExists([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'reference_type' => $refType,
            'reference_id' => $refId
        ]);

        // Auto-dispatch Web Push if user has active browser push subscription
        if ($notifId) {
            try {
                $this->webPushService->sendPushToUser($userId, $title, $message, $link);
            } catch (\Throwable $e) {}
        }

        return $notifId;
    }

    /**
     * 1. Notifikasi Peserta: Ujian baru tersedia bagi peserta kelas
     */
    public function notifyStudentsForNewExam(int $examId): int
    {
        // Get exam details
        $stmt = $this->db->prepare("
            SELECT e.id, e.title, e.duration, e.class_id, e.start_at, s.name AS subject_name, c.name AS class_name
            FROM `exams` e
            JOIN `subjects` s ON e.subject_id = s.id
            JOIN `classes` c ON e.class_id = c.id
            WHERE e.id = :eid AND e.status IN ('published', 'active')
            LIMIT 1
        ");
        $stmt->execute([':eid' => $examId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            return 0;
        }

        // Get all students in this class
        $stmtStudents = $this->db->prepare("
            SELECT s.id AS student_id, s.user_id, s.phone AS student_phone, u.name AS student_name, u.phone_number AS user_phone, u.whatsapp_opt_in
            FROM `students` s
            JOIN `users` u ON s.user_id = u.id
            WHERE s.class_id = :cid AND u.status = 'active'
        ");
        $stmtStudents->execute([':cid' => $exam['class_id']]);
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        $examDate = !empty($exam['start_at']) ? date('d M Y', strtotime((string)$exam['start_at'])) : date('d M Y');
        $examTime = !empty($exam['start_at']) ? date('H:i', strtotime((string)$exam['start_at'])) : date('H:i');
        $duration = (int)($exam['duration'] ?? 60);

        $count = 0;
        foreach ($students as $student) {
            $created = $this->send(
                (int)$student['user_id'],
                "Ujian Baru Tersedia: {$exam['title']}",
                "Ujian '{$exam['title']}' ({$exam['subject_name']}) telah dipublikasikan untuk kelas {$exam['class_name']}. Durasi: {$duration} menit.",
                'exam',
                'peserta/exams',
                'exam_new',
                $examId
            );
            if ($created) {
                $count++;
            }

            // WhatsApp Notification via Fonnte Gateway
            $phone = !empty($student['user_phone']) ? (string)$student['user_phone'] : (!empty($student['student_phone']) ? (string)$student['student_phone'] : '');
            if (!empty($phone)) {
                try {
                    $this->fonnteService->sendNewExamNotification(
                        $phone,
                        (string)$student['student_name'],
                        (string)$exam['title'],
                        (string)$exam['subject_name'],
                        $examDate,
                        $examTime,
                        $duration,
                        (int)$student['user_id'],
                        $examId
                    );
                } catch (\Throwable $e) {}
            }
        }

        return $count;
    }

    /**
     * 1b. Notifikasi Peserta: Perubahan jadwal ujian
     */
    public function notifyExamScheduleChanged(int $examId, string $oldStart, string $newStart, string $newEnd): int
    {
        $stmt = $this->db->prepare("
            SELECT e.id, e.title, e.duration, e.class_id, s.name AS subject_name, c.name AS class_name
            FROM `exams` e
            JOIN `subjects` s ON e.subject_id = s.id
            JOIN `classes` c ON e.class_id = c.id
            WHERE e.id = :eid
            LIMIT 1
        ");
        $stmt->execute([':eid' => $examId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            return 0;
        }

        $stmtStudents = $this->db->prepare("
            SELECT s.id AS student_id, s.user_id, s.phone AS student_phone, u.name AS student_name, u.phone_number AS user_phone, u.whatsapp_opt_in
            FROM `students` s
            JOIN `users` u ON s.user_id = u.id
            WHERE s.class_id = :cid AND u.status = 'active'
        ");
        $stmtStudents->execute([':cid' => $exam['class_id']]);
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        $formattedNew = date('d M Y H:i', strtotime($newStart));
        $formattedEnd = date('H:i', strtotime($newEnd));
        $newScheduleStr = "{$formattedNew} s.d. {$formattedEnd} WIB";

        $count = 0;
        foreach ($students as $student) {
            $userId = (int)$student['user_id'];
            $created = $this->send(
                $userId,
                "Perubahan Jadwal Ujian: {$exam['title']}",
                "Jadwal ujian '{$exam['title']}' ({$exam['subject_name']}) kelas {$exam['class_name']} telah diperbarui menjadi {$newScheduleStr}.",
                'warning',
                'peserta/exams',
                'exam_schedule_change',
                $examId
            );

            if ($created) {
                $count++;

                // WhatsApp Notification via Fonnte Gateway
                $phone = !empty($student['user_phone']) ? (string)$student['user_phone'] : (!empty($student['student_phone']) ? (string)$student['student_phone'] : '');
                if (!empty($phone)) {
                    try {
                        $this->fonnteService->sendScheduleChangeNotification(
                            $phone,
                            (string)$student['student_name'],
                            (string)$exam['title'],
                            $newScheduleStr,
                            $userId,
                            $examId
                        );
                    } catch (\Throwable $e) {}
                }
            }
        }

        return $count;
    }

    /**
     * 1c. Notifikasi Peserta: Jadwal ujian mendekat
     */
    public function checkUpcomingExamsForStudent(int $userId, int $studentId, int $classId): int
    {
        $stmt = $this->db->prepare("
            SELECT e.id, e.title, e.start_at, e.duration, s.name AS subject_name
            FROM `exams` e
            JOIN `subjects` s ON e.subject_id = s.id
            WHERE e.class_id = :cid 
              AND e.status IN ('published', 'active')
              AND e.start_at IS NOT NULL
              AND e.start_at >= NOW()
              AND e.start_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([':cid' => $classId]);
        $upcomingExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($upcomingExams as $exam) {
            $formattedStart = date('d M Y H:i', strtotime((string)$exam['start_at']));
            $created = $this->send(
                $userId,
                "Jadwal Ujian Mendekat: {$exam['title']}",
                "Ujian '{$exam['title']}' ({$exam['subject_name']}) dijadwalkan dimulai pada {$formattedStart} WIB. Pastikan perangkat dan koneksi Anda siap.",
                'warning',
                'peserta/exams',
                'exam_upcoming',
                (int)$exam['id']
            );
            if ($created) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 2a. Notifikasi Guru: Peserta mulai mengikuti ujian
     */
    public function notifyTeacherExamStarted(int $sessionId, int $examId, int $studentId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT e.title AS exam_title, t.user_id AS teacher_user_id,
                   u.name AS student_name, st.nisn, c.name AS class_name
            FROM `exams` e
            JOIN `teachers` t ON e.teacher_id = t.id
            JOIN `students` st ON st.id = :sid
            JOIN `users` u ON st.user_id = u.id
            LEFT JOIN `classes` c ON st.class_id = c.id
            WHERE e.id = :eid
            LIMIT 1
        ");
        $stmt->execute([':eid' => $examId, ':sid' => $studentId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$info || empty($info['teacher_user_id'])) {
            return null;
        }

        return $this->send(
            (int)$info['teacher_user_id'],
            "Peserta Mulai Ujian: {$info['student_name']}",
            "Peserta {$info['student_name']} ({$info['nisn']}) kelas {$info['class_name']} telah memulai pengerjaan ujian '{$info['exam_title']}'.",
            'info',
            "guru/monitoring/{$examId}/{$sessionId}",
            'session_start',
            $sessionId
        );
    }

    /**
     * 2b. Notifikasi Guru & Peserta: Ujian selesai dan hasil tersedia
     */
    public function notifyExamFinished(int $sessionId, int $examId, int $studentId): void
    {
        $stmt = $this->db->prepare("
            SELECT e.title AS exam_title, e.class_id, t.user_id AS teacher_user_id,
                   tu.name AS teacher_name, tu.phone_number AS teacher_user_phone, t.phone AS teacher_phone,
                   u.name AS student_name, u.id AS student_user_id, u.phone_number AS student_user_phone, st.phone AS student_phone
            FROM `exams` e
            JOIN `teachers` t ON e.teacher_id = t.id
            JOIN `users` tu ON t.user_id = tu.id
            JOIN `students` st ON st.id = :sid
            JOIN `users` u ON st.user_id = u.id
            WHERE e.id = :eid
            LIMIT 1
        ");
        $stmt->execute([':eid' => $examId, ':sid' => $studentId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            return;
        }

        // 1) Notifikasi ke Peserta (In-App)
        $this->send(
            (int)$info['student_user_id'],
            "Ujian Selesai: {$info['exam_title']}",
            "Anda telah menyelesaikan ujian '{$info['exam_title']}'. Lembar jawaban Anda telah tersimpan dengan aman.",
            'success',
            "peserta/exams/result?session_id={$sessionId}",
            'student_exam_finish',
            $sessionId
        );

        // WhatsApp Hasil Ujian ke Peserta
        $studentPhone = !empty($info['student_user_phone']) ? (string)$info['student_user_phone'] : (!empty($info['student_phone']) ? (string)$info['student_phone'] : '');
        if (!empty($studentPhone)) {
            $score = 0.0;
            try {
                $sessionModel = new \App\Models\ExamSession();
                $breakdown = $sessionModel->getStudentExamAnswerBreakdown($examId, $studentId);
                if ($breakdown && isset($breakdown['summary']['grade'])) {
                    $score = (float)$breakdown['summary']['grade'];
                }
            } catch (\Throwable $e) {}

            try {
                $this->fonnteService->sendExamResultNotification(
                    $studentPhone,
                    (string)$info['student_name'],
                    (string)$info['exam_title'],
                    $score,
                    (int)$info['student_user_id'],
                    $sessionId
                );
            } catch (\Throwable $e) {}
        }

        // 2) Notifikasi ke Guru (In-App)
        if (!empty($info['teacher_user_id'])) {
            $this->send(
                (int)$info['teacher_user_id'],
                "Peserta Menyelesaikan Ujian: {$info['student_name']}",
                "Peserta {$info['student_name']} telah menyelesaikan ujian '{$info['exam_title']}'. Lembar jawaban siap ditinjau.",
                'success',
                "guru/results/{$examId}/{$studentId}",
                'teacher_exam_finish',
                $sessionId
            );

            // WhatsApp Ringkasan Ujian ke Guru
            $teacherPhone = !empty($info['teacher_user_phone']) ? (string)$info['teacher_user_phone'] : (!empty($info['teacher_phone']) ? (string)$info['teacher_phone'] : '');
            if (!empty($teacherPhone)) {
                // Hitung jumlah peserta dan yang sudah selesai
                $stmtCounts = $this->db->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM `students` WHERE `class_id` = :cid) AS total_participants,
                        (SELECT COUNT(*) FROM `exam_sessions` WHERE `exam_id` = :eid AND `status` IN ('finished', 'completed', 'expired')) AS finished_participants
                ");
                $stmtCounts->execute([':cid' => $info['class_id'], ':eid' => $examId]);
                $counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

                $totalParticipants = (int)($counts['total_participants'] ?? 0);
                $finishedParticipants = (int)($counts['finished_participants'] ?? 1);

                try {
                    $this->fonnteService->sendTeacherExamSummaryNotification(
                        $teacherPhone,
                        (string)$info['teacher_name'],
                        (string)$info['exam_title'],
                        $totalParticipants,
                        $finishedParticipants,
                        (int)$info['teacher_user_id'],
                        $examId
                    );
                } catch (\Throwable $e) {}
            }
        }
    }

    /**
     * Broadcast hasil ujian ke seluruh peserta kelas yang telah menyelesaikan ujian
     */
    public function notifyExamResultPublished(int $examId, ?int $studentId = null): int
    {
        $sql = "
            SELECT es.id AS session_id, es.student_id, e.id AS exam_id, e.title AS exam_title,
                   u.id AS user_id, u.name AS student_name, u.phone_number AS user_phone, st.phone AS student_phone
            FROM `exam_sessions` es
            JOIN `exams` e ON es.exam_id = e.id
            JOIN `students` st ON es.student_id = st.id
            JOIN `users` u ON st.user_id = u.id
            WHERE es.exam_id = :eid AND es.status IN ('finished', 'completed', 'expired')
        ";
        $params = [':eid' => $examId];
        if ($studentId) {
            $sql .= " AND es.student_id = :sid";
            $params[':sid'] = $studentId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sessionModel = new \App\Models\ExamSession();
        $notified = 0;
        foreach ($rows as $row) {
            $phone = !empty($row['user_phone']) ? (string)$row['user_phone'] : (!empty($row['student_phone']) ? (string)$row['student_phone'] : '');
            if (!empty($phone)) {
                $score = 0.0;
                try {
                    $breakdown = $sessionModel->getStudentExamAnswerBreakdown($examId, (int)$row['student_id']);
                    if ($breakdown && isset($breakdown['summary']['grade'])) {
                        $score = (float)$breakdown['summary']['grade'];
                    }
                } catch (\Throwable $e) {}

                $res = $this->fonnteService->sendExamResultNotification(
                    $phone,
                    (string)$row['student_name'],
                    (string)$row['exam_title'],
                    $score,
                    (int)$row['user_id'],
                    (int)$row['session_id']
                );
                if ($res['success'] ?? false) {
                    $notified++;
                }
            }
        }

        return $notified;
    }

    /**
     * 2c. Notifikasi Guru: Event proctoring mencurigakan yang perlu ditinjau
     */
    public function notifyTeacherProctorAlert(
        int $sessionId,
        int $examId,
        int $studentId,
        string $eventType,
        string $severity
    ): ?int {
        $stmt = $this->db->prepare("
            SELECT e.title AS exam_title, t.user_id AS teacher_user_id, u.name AS student_name
            FROM `exams` e
            JOIN `teachers` t ON e.teacher_id = t.id
            JOIN `students` st ON st.id = :sid
            JOIN `users` u ON st.user_id = u.id
            WHERE e.id = :eid
            LIMIT 1
        ");
        $stmt->execute([':eid' => $examId, ':sid' => $studentId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$info || empty($info['teacher_user_id'])) {
            return null;
        }

        $type = ($severity === 'HIGH') ? 'danger' : 'warning';
        $severityLabel = strtoupper($severity);

        return $this->send(
            (int)$info['teacher_user_id'],
            "Peringatan Proctoring: {$info['student_name']}",
            "AI Proctoring mendeteksi event '{$eventType}' (Tingkat: {$severityLabel}) pada peserta {$info['student_name']} saat ujian '{$info['exam_title']}'.",
            $type,
            "guru/monitoring/{$examId}/{$sessionId}",
            "proctor_alert_{$eventType}",
            $sessionId
        );
    }

    /**
     * 3. Notifikasi Super Admin: Informasi operasional & peringatan sistem
     */
    public function notifySuperAdmins(
        string $title,
        string $message,
        string $type = 'system',
        ?string $link = null,
        ?string $refType = null,
        ?int $refId = null
    ): int {
        $stmt = $this->db->query("
            SELECT `id` FROM `users` 
            WHERE `role` = 'SUPER_ADMIN' AND `status` = 'active'
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($admins as $admin) {
            $created = $this->send(
                (int)$admin['id'],
                $title,
                $message,
                $type,
                $link,
                $refType,
                $refId
            );
            if ($created) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Fetch unread count for navbar badge
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationModel->getUnreadCount($userId);
    }

    /**
     * Fetch recent notifications for dropdown
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        return $this->notificationModel->getRecent($userId, $limit);
    }

    /**
     * Fetch paginated notifications for listing page
     */
    public function getPaginated(int $userId, int $page = 1, int $perPage = 10, ?string $filter = null): array
    {
        return $this->notificationModel->getPaginated($userId, $page, $perPage, $filter);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(int $id, int $userId): bool
    {
        return $this->notificationModel->markAsRead($id, $userId);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->notificationModel->markAllAsRead($userId);
    }
}
