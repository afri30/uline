<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Proctoring\RiskScoringService;

class RiskScoringServiceTest extends TestCase
{
    private RiskScoringService $service;

    protected function setUp(): void
    {
        $this->service = new RiskScoringService();
    }

    public function testEmptyEventsReturnsZeroRisk(): void
    {
        $result = $this->service->calculateRiskScore([]);
        $this->assertSame(0, $result['score']);
        $this->assertSame('LOW', $result['level']);
        $this->assertSame('success', $result['color']);
        $this->assertFalse($result['requires_review']);
    }

    public function testCalculatesCumulativeRiskWithMultipleSeverities(): void
    {
        $events = [
            ['severity' => 'INFO'],    // +1
            ['severity' => 'WARNING'], // +5
            ['severity' => 'WARNING'], // +5
            ['severity' => 'HIGH'],    // +15
        ];

        $result = $this->service->calculateRiskScore($events);
        $this->assertSame(26, $result['score']);
        $this->assertSame('MEDIUM', $result['level']);
        $this->assertSame('warning', $result['color']);
        $this->assertFalse($result['requires_review']);
    }

    public function testHighRiskThresholdExceeded(): void
    {
        $events = [
            ['severity' => 'HIGH'], // 15
            ['severity' => 'HIGH'], // 15
            ['severity' => 'HIGH'], // 15
            ['severity' => 'HIGH'], // 15
        ]; // Total = 60 (> 50)

        $result = $this->service->calculateRiskScore($events);
        $this->assertSame(60, $result['score']);
        $this->assertSame('HIGH', $result['level']);
        $this->assertSame('danger', $result['color']);
        $this->assertTrue($result['requires_review']);
    }

    public function testBreakdownCountsDistribution(): void
    {
        $events = [
            ['severity' => 'INFO'],
            ['severity' => 'WARNING'],
            ['severity' => 'WARNING'],
            ['severity' => 'HIGH'],
        ];

        $result = $this->service->calculateRiskScore($events);
        $this->assertSame(1, $result['breakdown']['info']['count']);
        $this->assertSame(2, $result['breakdown']['warning']['count']);
        $this->assertSame(1, $result['breakdown']['high']['count']);
        $this->assertSame(10, $result['breakdown']['warning']['points']);
    }
}
