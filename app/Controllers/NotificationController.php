<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Student;
use App\Services\Notification\NotificationService;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationService = new NotificationService();
    }

    /**
     * Helper to get authenticated user or reject
     */
    private function getAuthUser(): array
    {
        $user = auth();
        if (!$user) {
            $this->redirect('login');
            exit;
        }
        return $user;
    }

    /**
     * Web View: List of notifications (/notifications)
     */
    public function index(): void
    {
        $user = $this->getAuthUser();
        $userId = (int)$user['id'];

        // If student, check if any upcoming exams should trigger notifications
        if (($user['role'] ?? '') === 'PESERTA') {
            $studentModel = new Student();
            $student = $studentModel->findByUserId($userId);
            if ($student && !empty($student['class_id'])) {
                $this->notificationService->checkUpcomingExamsForStudent($userId, (int)$student['id'], (int)$student['class_id']);
            }
        }

        $page = max(1, (int)$this->request->input('page', 1));
        $filter = (string)$this->request->input('filter', 'all');
        if (!in_array($filter, ['all', 'unread', 'read'], true)) {
            $filter = 'all';
        }

        $statusFilter = ($filter === 'all') ? null : $filter;
        $paginated = $this->notificationService->getPaginated($userId, $page, 10, $statusFilter);
        $unreadCount = $this->notificationService->getUnreadCount($userId);

        $this->view('notifications/index', [
            'title' => 'Pusat Notifikasi',
            'user' => $user,
            'items' => $paginated['items'],
            'total' => $paginated['total'],
            'page' => $paginated['page'],
            'perPage' => $paginated['per_page'],
            'totalPages' => $paginated['total_pages'],
            'unreadCount' => $unreadCount,
            'filter' => $filter
        ]);
    }

    /**
     * Web Action: Mark single notification as read (/notifications/read/{id})
     */
    public function markRead($id = null): void
    {
        $user = $this->getAuthUser();
        $notificationId = (int)($id ?? $this->request->input('id', 0));

        $csrfToken = $this->request->string('_csrf_token');
        if (!csrf_verify($csrfToken)) {
            Session::setFlash('error', 'Token CSRF tidak valid atau sesi Anda telah kedaluwarsa.');
            $this->redirect('notifications');
            return;
        }

        if ($notificationId > 0) {
            $this->notificationService->markAsRead($notificationId, (int)$user['id']);
            Session::setFlash('success', 'Notifikasi berhasil ditandai telah dibaca.');
        }

        $redirectUrl = (string)$this->request->input('redirect', 'notifications');
        $this->redirect($redirectUrl);
    }

    /**
     * Web Action: Mark all notifications as read (/notifications/mark-all-read)
     */
    public function markAllRead(): void
    {
        $user = $this->getAuthUser();

        $csrfToken = $this->request->string('_csrf_token');
        if (!csrf_verify($csrfToken)) {
            Session::setFlash('error', 'Token CSRF tidak valid atau sesi Anda telah kedaluwarsa.');
            $this->redirect('notifications');
            return;
        }

        $count = $this->notificationService->markAllAsRead((int)$user['id']);
        Session::setFlash('success', "{$count} notifikasi berhasil ditandai telah dibaca.");
        $this->redirect('notifications');
    }

    /**
     * AJAX API: Recent notifications for navbar dropdown (/api/notifications/recent)
     */
    public function apiRecent(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $userId = (int)$user['id'];

        // Refresh upcoming exam alerts for students
        if (($user['role'] ?? '') === 'PESERTA') {
            $studentModel = new Student();
            $student = $studentModel->findByUserId($userId);
            if ($student && !empty($student['class_id'])) {
                $this->notificationService->checkUpcomingExamsForStudent($userId, (int)$student['id'], (int)$student['class_id']);
            }
        }

        $recent = $this->notificationService->getRecent($userId, 5);
        $unreadCount = $this->notificationService->getUnreadCount($userId);

        $formatted = array_map(function ($item) {
            return [
                'id' => (int)$item['id'],
                'title' => e($item['title']),
                'message' => e($item['message']),
                'type' => e($item['type']),
                'link' => !empty($item['link']) ? base_url($item['link']) : null,
                'is_read' => (bool)$item['is_read'],
                'created_at' => $item['created_at'],
                'time_ago' => $this->formatTimeAgo((string)$item['created_at'])
            ];
        }, $recent);

        $this->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $formatted
        ]);
    }

    /**
     * AJAX API: Unread count for periodic polling (/api/notifications/unread-count)
     */
    public function apiUnreadCount(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $count = $this->notificationService->getUnreadCount((int)$user['id']);
        $this->json([
            'status' => 'success',
            'unread_count' => $count
        ]);
    }

    /**
     * AJAX API: Mark single as read (/api/notifications/{id}/read)
     */
    public function apiMarkRead($id = null): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $notificationId = (int)($id ?? $this->request->input('id', 0));
        if ($notificationId <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID notifikasi tidak valid.'], 422);
            return;
        }

        $success = $this->notificationService->markAsRead($notificationId, (int)$user['id']);
        $newCount = $this->notificationService->getUnreadCount((int)$user['id']);

        $this->json([
            'status' => $success ? 'success' : 'not_found_or_forbidden',
            'unread_count' => $newCount
        ]);
    }

    /**
     * AJAX API: Mark all as read (/api/notifications/mark-all-read)
     */
    public function apiMarkAllRead(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $count = $this->notificationService->markAllAsRead((int)$user['id']);

        $this->json([
            'status' => 'success',
            'marked_count' => $count,
            'unread_count' => 0
        ]);
    }

    /**
     * Helper to format timestamps to Indonesian human-friendly diff
     */
    private function formatTimeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        if (!$time) {
            return $datetime;
        }

        $diff = time() - $time;
        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            $m = (int)floor($diff / 60);
            return "{$m} menit yang lalu";
        } elseif ($diff < 86400) {
            $h = (int)floor($diff / 3600);
            return "{$h} jam yang lalu";
        } elseif ($diff < 604800) {
            $d = (int)floor($diff / 86400);
            return "{$d} hari yang lalu";
        } else {
            return date('d/m/Y H:i', $time);
        }
    }

    /**
     * AJAX API: Ambil VAPID Public Key untuk Web Push (/api/push/vapid-public-key)
     * Hanya mengekspos public key ke browser, private key tetap terlindungi di backend.
     */
    public function apiVapidPublicKey(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $webPush = $this->notificationService->getWebPushService();
        $this->json([
            'status' => 'success',
            'is_configured' => $webPush->isConfigured(),
            'public_key' => $webPush->getPublicKey()
        ]);
    }

    /**
     * AJAX API: Simpan Subscription Web Push (/api/push/subscribe)
     */
    public function apiPushSubscribe(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $input = $this->request->isJson() ? $this->request->json() : $_POST;
        $endpoint = trim((string)($input['endpoint'] ?? ''));
        $keys = $input['keys'] ?? [];
        $p256dh = trim((string)($keys['p256dh'] ?? ''));
        $auth = trim((string)($keys['auth'] ?? ''));
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            $this->json(['status' => 'error', 'message' => 'Parameter subscription push tidak lengkap.'], 400);
            return;
        }

        $webPush = $this->notificationService->getWebPushService();
        $success = $webPush->subscribe((int)$user['id'], $endpoint, $p256dh, $auth, $userAgent);

        $this->json([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Notifikasi browser berhasil diaktifkan.' : 'Gagal menyimpan subscription.'
        ]);
    }

    /**
     * AJAX API: Nonaktifkan Subscription Web Push (/api/push/unsubscribe)
     */
    public function apiPushUnsubscribe(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $input = $this->request->isJson() ? $this->request->json() : $_POST;
        $endpoint = trim((string)($input['endpoint'] ?? ''));

        if (empty($endpoint)) {
            $this->json(['status' => 'error', 'message' => 'Endpoint subscription wajib disertakan.'], 400);
            return;
        }

        $webPush = $this->notificationService->getWebPushService();
        $success = $webPush->unsubscribe((int)$user['id'], $endpoint);

        $this->json([
            'status' => $success ? 'success' : 'error',
            'message' => 'Notifikasi browser berhasil dinonaktifkan.'
        ]);
    }

    /**
     * AJAX API: Status Subscription Push (/api/push/status)
     */
    public function apiPushStatus(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $webPush = $this->notificationService->getWebPushService();
        $subs = $webPush->getActiveSubscriptions((int)$user['id']);

        $this->json([
            'status' => 'success',
            'is_configured' => $webPush->isConfigured(),
            'is_subscribed' => count($subs) > 0,
            'active_devices' => count($subs)
        ]);
    }
}

