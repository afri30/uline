<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PhoneFormatterTest extends TestCase
{
    private function formatPhoneNumber(string $phone, string $defaultCountry = '62'): ?string
    {
        $cleaned = trim($phone);
        $hasPlus = str_starts_with($cleaned, '+');
        $digits = preg_replace('/[^\d]/', '', $cleaned);

        if (empty($digits)) {
            return null;
        }

        if ($hasPlus && str_starts_with($digits, '62')) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '62')) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '0')) {
            $normalized = '62' . substr($digits, 1);
        } else {
            $normalized = $defaultCountry . $digits;
        }

        if (strlen($normalized) >= 10 && strlen($normalized) <= 16) {
            return $normalized;
        }

        return null;
    }

    public function testConvertsIndonesianLocalZeroToCountryCode(): void
    {
        $this->assertSame('628123456789', $this->formatPhoneNumber('08123456789'));
    }

    public function testPreservesExistingCountryCode(): void
    {
        $this->assertSame('628123456789', $this->formatPhoneNumber('+628123456789'));
        $this->assertSame('628123456789', $this->formatPhoneNumber('628123456789'));
    }

    public function testStripsHyphensAndSpaces(): void
    {
        $this->assertSame('6285642349158', $this->formatPhoneNumber('0856-4234-9158'));
        $this->assertSame('6285642349158', $this->formatPhoneNumber('+62 856 4234 9158'));
    }

    public function testRejectsInvalidLengthNumbers(): void
    {
        $this->assertNull($this->formatPhoneNumber('1234')); // too short
        $this->assertNull($this->formatPhoneNumber('')); // empty
    }
}
