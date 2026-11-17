<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AI\NvidiaAIService;

class NvidiaAIServiceTest extends TestCase
{
    private NvidiaAIService $service;

    protected function setUp(): void
    {
        // Pass dummy config so it doesn't try to connect to any network
        $this->service = new NvidiaAIService([
            'api_key' => 'dummy_key',
            'endpoint' => 'http://localhost/test',
            'model' => 'dummy_model',
        ]);
    }

    public function testCleansXmlThinkTags(): void
    {
        $raw = "<think>Let me analyze this user question carefully. The user wants to know...</think>Glikolisis adalah proses pemecahan glukosa menjadi asam piruvat.";
        $cleaned = $this->service->cleanAIResponse($raw);

        $this->assertSame("Glikolisis adalah proses pemecahan glukosa menjadi asam piruvat.", $cleaned);
    }

    public function testCleansUnclosedThinkTags(): void
    {
        $raw = "Berikut adalah penjelasan resmi.<think>Internal reasoning that was truncated...";
        $cleaned = $this->service->cleanAIResponse($raw);

        $this->assertSame("Berikut adalah penjelasan resmi.", $cleaned);
    }

    public function testCleansThinkingProcessHeaders(): void
    {
        $raw = "Here's a thinking process:\n1. Analyze input\n2. Query database\n\nFinal Answer:\nUjian Biologi akan dimulai pukul 08:00 WIB.";
        $cleaned = $this->service->cleanAIResponse($raw);

        $this->assertSame("Ujian Biologi akan dimulai pukul 08:00 WIB.", $cleaned);
    }
}
