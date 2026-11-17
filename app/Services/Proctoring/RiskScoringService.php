<?php

declare(strict_types=1);

namespace App\Services\Proctoring;

class RiskScoringService
{
    // Severity Weights
    public const WEIGHT_INFO = 1;
    public const WEIGHT_WARNING = 5;
    public const WEIGHT_HIGH = 15;

    // Thresholds
    public const THRESHOLD_LOW_MAX = 20;
    public const THRESHOLD_MEDIUM_MAX = 50;

    /**
     * Calculate risk score and category from event counts or events array
     *
     * @param int $infoCount
     * @param int $warningCount
     * @param int $highCount
     * @return array
     */
    public function calculate(int $infoCount, int $warningCount, int $highCount): array
    {
        $score = ($infoCount * self::WEIGHT_INFO) + 
                 ($warningCount * self::WEIGHT_WARNING) + 
                 ($highCount * self::WEIGHT_HIGH);

        if ($score <= self::THRESHOLD_LOW_MAX) {
            $level = 'LOW';
            $label = 'Rendah (Aman)';
            $color = 'success';
            $description = 'Aktivitas peserta wajar dan berada dalam batas integritas normal.';
            $badgeClass = 'bg-success-subtle text-success border border-success';
            $requiresReview = false;
        } elseif ($score <= self::THRESHOLD_MEDIUM_MAX) {
            $level = 'MEDIUM';
            $label = 'Sedang (Perlu Perhatian)';
            $color = 'warning';
            $description = 'Terdapat beberapa anomali ringan yang disarankan untuk ditinjau guru.';
            $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning';
            $requiresReview = false;
        } else {
            $level = 'HIGH';
            $label = 'Tinggi (Memerlukan Reviu)';
            $color = 'danger';
            $description = 'Terdeteksi anomali berulang atau indikasi kuat yang memerlukan reviu guru pengawas.';
            $badgeClass = 'bg-danger-subtle text-danger border border-danger';
            $requiresReview = true;
        }

        return [
            'score' => $score,
            'level' => $level,
            'label' => $label,
            'color' => $color,
            'description' => $description,
            'badge_class' => $badgeClass,
            'requires_review' => $requiresReview,
            'breakdown' => [
                'info' => [
                    'count' => $infoCount,
                    'weight' => self::WEIGHT_INFO,
                    'points' => $infoCount * self::WEIGHT_INFO
                ],
                'warning' => [
                    'count' => $warningCount,
                    'weight' => self::WEIGHT_WARNING,
                    'points' => $warningCount * self::WEIGHT_WARNING
                ],
                'high' => [
                    'count' => $highCount,
                    'weight' => self::WEIGHT_HIGH,
                    'points' => $highCount * self::WEIGHT_HIGH
                ]
            ]
        ];
    }

    /**
     * Calculate risk profile directly from a list of proctor events
     *
     * @param array $events
     * @return array
     */
    public function calculateFromEvents(array $events): array
    {
        $infoCount = 0;
        $warningCount = 0;
        $highCount = 0;

        foreach ($events as $ev) {
            $sev = strtoupper($ev['severity'] ?? 'INFO');
            if ($sev === 'HIGH') {
                $highCount++;
            } elseif ($sev === 'WARNING') {
                $warningCount++;
            } else {
                $infoCount++;
            }
        }

        return $this->calculate($infoCount, $warningCount, $highCount);
    }

    /**
     * Alias for calculateFromEvents
     */
    public function calculateRiskScore(array $events): array
    {
        return $this->calculateFromEvents($events);
    }

    /**
     * Get severity counts breakdown from events list
     */
    public function getSeverityBreakdown(array $events): array
    {
        $info = 0;
        $warning = 0;
        $high = 0;

        foreach ($events as $ev) {
            $sev = strtoupper($ev['severity'] ?? 'INFO');
            if ($sev === 'HIGH') $high++;
            elseif ($sev === 'WARNING') $warning++;
            else $info++;
        }

        return ['info' => $info, 'warning' => $warning, 'high' => $high];
    }
}
