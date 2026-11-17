<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Proctoring\DetectionService;

class DetectionServiceTest extends TestCase
{
    private DetectionService $service;

    protected function setUp(): void
    {
        $this->service = new DetectionService();
    }

    public function testValidatesStandardEventTypes(): void
    {
        $this->assertTrue($this->service->isValidEventType('FACE_NOT_DETECTED'));
        $this->assertTrue($this->service->isValidEventType('MULTIPLE_FACES'));
        $this->assertTrue($this->service->isValidEventType('CAMERA_DISCONNECTED'));
        $this->assertTrue($this->service->isValidEventType('LOOKING_AWAY'));
        $this->assertTrue($this->service->isValidEventType('TAB_SWITCHED'));
        $this->assertTrue($this->service->isValidEventType('SUSPICIOUS_ACTIVITY'));

        $this->assertFalse($this->service->isValidEventType('UNKNOWN_EVENT_XYZ'));
    }

    public function testDefaultSeverityMapping(): void
    {
        $this->assertSame('HIGH', $this->service->getDefaultSeverity('MULTIPLE_FACES'));
        $this->assertSame('HIGH', $this->service->getDefaultSeverity('CAMERA_DISCONNECTED'));
        $this->assertSame('WARNING', $this->service->getDefaultSeverity('FACE_NOT_DETECTED'));
        $this->assertSame('WARNING', $this->service->getDefaultSeverity('TAB_SWITCHED'));
        $this->assertSame('WARNING', $this->service->getDefaultSeverity('LOOKING_AWAY'));
    }

    public function testEvaluatesRawEventToStandardizedPayload(): void
    {
        $payload = $this->service->evaluateRawEvent('MULTIPLE_FACES');
        $this->assertSame('MULTIPLE_FACES', $payload['event_type']);
        $this->assertSame('HIGH', $payload['severity']);
        $this->assertSame(0.97, $payload['confidence']);
    }

    public function testMapsAliasesForLooseEventTypes(): void
    {
        $disconnectPayload = $this->service->evaluateRawEvent('disconnect');
        $this->assertSame('CAMERA_DISCONNECTED', $disconnectPayload['event_type']);

        $blurPayload = $this->service->evaluateRawEvent('window_blur');
        $this->assertSame('TAB_SWITCHED', $blurPayload['event_type']);
    }
}
