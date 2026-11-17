<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SecurityHelperTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        $malicious = '<script>alert("XSS")</script>';
        $escaped = e($malicious);

        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $escaped);
    }

    public function testEscapesSingleAndDoubleQuotes(): void
    {
        $raw = "O'Connor & \"Partners\"";
        $escaped = e($raw);

        $this->assertSame('O&#039;Connor &amp; &quot;Partners&quot;', $escaped);
    }
}
