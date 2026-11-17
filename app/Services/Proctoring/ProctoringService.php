<?php

declare(strict_types=1);

namespace App\Services\Proctoring;

use App\Models\ProctorEvent;
use App\Models\ExamSession;
use App\Models\CameraEvent;

class ProctoringService
{
    private ProctorEvent $eventModel;
    private DetectionService $detectionService;
    private RiskScoringService $riskScoringService;
    private ExamSession $sessionModel;
    private CameraEvent $cameraEventModel;

    public function __construct(
        ?ProctorEvent $eventModel = null,
        ?DetectionService $detectionService = null,
        ?RiskScoringService $riskScoringService = null,
        ?ExamSession $sessionModel = null,
        ?CameraEvent $cameraEventModel = null
    ) {
        $this->eventModel = $eventModel ?? new ProctorEvent();
        $this->detectionService = $detectionService ?? new DetectionService();
        $this->riskScoringService = $riskScoringService ?? new RiskScoringService();
        $this->sessionModel = $sessionModel ?? new ExamSession();
        $this->cameraEventModel = $cameraEventModel ?? new CameraEvent();
    }

    /**
     * Record a new proctoring event after evaluating through DetectionService
     */
    public function recordEvent(
        int $sessionId,
        string $rawEventType,
        string $cameraType = 'camera_1',
        ?array $metadata = null,
        ?string $evidencePath = null
    ): array {
        $evaluated = $this->detectionService->evaluateRawEvent($rawEventType, $metadata);

        $eventId = $this->eventModel->logEvent(
            $sessionId,
            $evaluated['event_type'],
            $evaluated['severity'],
            $evaluated['confidence'],
            $cameraType,
            $evaluated['metadata'],
            $evidencePath
        );

        $riskProfile = $this->getSessionRiskProfile($sessionId);

        return [
            'event_id' => $eventId,
            'evaluated' => $evaluated,
            'current_risk' => $riskProfile['risk']
        ];
    }

    /**
     * Helper for recording suspicious event directly
     */
    public function recordSuspiciousEvent(
        int $sessionId,
        string $cameraType,
        string $eventType,
        float $confidence = 0.85,
        ?array $metadata = null,
        ?string $evidencePath = null
    ): array {
        $meta = $metadata ?? [];
        if (!isset($meta['confidence'])) {
            $meta['confidence'] = $confidence;
        }

        $res = $this->recordEvent($sessionId, $eventType, $cameraType, $meta, $evidencePath);
        return [
            'event_id' => $res['event_id'],
            'event_type' => $res['evaluated']['event_type'],
            'severity' => $res['evaluated']['severity'],
            'confidence' => $res['evaluated']['confidence'],
            'current_risk_score' => $res['current_risk']
        ];
    }

    /**
     * Get complete risk profile and timeline events for an exam session
     */
    public function getSessionRiskProfile(int $sessionId): array
    {
        $session = $this->sessionModel->getSessionWithDetails($sessionId);
        $events = $this->eventModel->getBySessionId($sessionId);
        $stats = $this->eventModel->getEventStatsBySession($sessionId);
        $risk = $this->riskScoringService->calculate(
            $stats['info_count'],
            $stats['warning_count'],
            $stats['high_count']
        );

        $catalog = $this->detectionService->getEventTypesCatalog();
        $camStatus = $this->cameraEventModel->getLatestStatusByCamera($sessionId);

        // Enhance events with catalog metadata
        foreach ($events as &$ev) {
            $type = $ev['event_type'];
            $ev['catalog_info'] = $catalog[$type] ?? null;
            $ev['icon'] = $catalog[$type]['icon'] ?? 'bi-exclamation-circle';
        }

        $now = time();
        $lastActiveTs = !empty($session['last_activity_at']) ? strtotime($session['last_activity_at']) : 0;
        $isOnline = (!empty($session['status']) && $session['status'] === 'started' && ($now - $lastActiveTs) <= 60);

        return [
            'session' => $session,
            'events' => $events,
            'stats' => $stats,
            'risk' => $risk,
            'is_online' => $isOnline,
            'cameras' => $camStatus
        ];
    }

    /**
     * Get monitoring overview of all participants for an exam
     */
    public function getExamMonitoringOverview(int $examId, ?string $filterRisk = null, ?string $search = null): array
    {
        $participants = $this->sessionModel->getExamParticipants($examId);

        $monitoredParticipants = [];
        $totalOnline = 0;
        $totalOffline = 0;
        $totalHighRisk = 0;
        $totalMediumRisk = 0;
        $totalLowRisk = 0;
        $totalFlaggedEvents = 0;

        $now = time();

        foreach ($participants as $p) {
            $sessionId = (int)$p['session_id'];
            $stats = $this->eventModel->getEventStatsBySession($sessionId);
            $risk = $this->riskScoringService->calculate(
                $stats['info_count'],
                $stats['warning_count'],
                $stats['high_count']
            );

            // Determine Online Status (Active within last 60 seconds and session status is 'started')
            $lastActiveTs = !empty($p['last_activity_at']) ? strtotime($p['last_activity_at']) : 0;
            $isOnline = ($p['session_status'] === 'started' && ($now - $lastActiveTs) <= 60);

            if ($isOnline) {
                $totalOnline++;
            } else {
                $totalOffline++;
            }

            if ($risk['level'] === 'HIGH') {
                $totalHighRisk++;
            } elseif ($risk['level'] === 'MEDIUM') {
                $totalMediumRisk++;
            } else {
                $totalLowRisk++;
            }
            $totalFlaggedEvents += $stats['total_events'];

            // Fetch latest camera status
            $camStatus = $this->cameraEventModel->getLatestStatusByCamera($sessionId);
            $cam1Event = $camStatus['camera_1']['event_type'] ?? 'none';
            $cam2Event = $camStatus['camera_2']['event_type'] ?? 'none';

            // Apply risk level filter if requested
            if ($filterRisk && strtoupper($filterRisk) !== 'ALL' && $risk['level'] !== strtoupper($filterRisk)) {
                continue;
            }

            // Apply search filter if requested
            if ($search) {
                $needle = strtolower(trim($search));
                $name = strtolower($p['student_name'] ?? '');
                $nis = strtolower($p['student_nis'] ?? '');
                if (strpos($name, $needle) === false && strpos($nis, $needle) === false) {
                    continue;
                }
            }

            $p['is_online'] = $isOnline;
            $p['last_active_formatted'] = $lastActiveTs > 0 ? date('H:i:s', $lastActiveTs) : '-';
            $p['camera_1_status'] = $cam1Event;
            $p['camera_2_status'] = $cam2Event;
            $p['warnings_count'] = $stats['warning_count'] + $stats['high_count'];
            $p['proctor_stats'] = $stats;
            $p['risk'] = $risk;

            $monitoredParticipants[] = $p;
        }

        // Sort by Risk Score DESC (highest risk first)
        usort($monitoredParticipants, fn($a, $b) => $b['risk']['score'] <=> $a['risk']['score']);

        return [
            'participants' => $monitoredParticipants,
            'summary' => [
                'total_participants' => count($participants),
                'online_count' => $totalOnline,
                'offline_count' => $totalOffline,
                'normal_count' => $totalLowRisk,
                'warning_count' => $totalMediumRisk,
                'high_risk_count' => $totalHighRisk,
                'medium_risk_count' => $totalMediumRisk,
                'low_risk_count' => $totalLowRisk,
                'total_events' => $totalFlaggedEvents
            ]
        ];
    }
}
