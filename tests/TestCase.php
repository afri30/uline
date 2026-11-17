<?php

declare(strict_types=1);

namespace PHPUnit\Framework;

if (!class_exists(TestCase::class)) {
    class TestCase
    {
        public static int $passCount = 0;
        public static int $failCount = 0;

        protected function setUp(): void {}
        protected function tearDown(): void {}

        public function assertSame(mixed $expected, mixed $actual, string $message = ''): void
        {
            if ($expected === $actual) {
                self::$passCount++;
            } else {
                self::$failCount++;
                $exp = var_export($expected, true);
                $act = var_export($actual, true);
                echo "  [FAIL] Expected: $exp, but got: $act. $message\n";
            }
        }

        public function assertTrue(bool $condition, string $message = ''): void
        {
            if ($condition === true) {
                self::$passCount++;
            } else {
                self::$failCount++;
                echo "  [FAIL] Expected true, got false. $message\n";
            }
        }

        public function assertFalse(bool $condition, string $message = ''): void
        {
            if ($condition === false) {
                self::$passCount++;
            } else {
                self::$failCount++;
                echo "  [FAIL] Expected false, got true. $message\n";
            }
        }

        public function assertNull(mixed $actual, string $message = ''): void
        {
            if ($actual === null) {
                self::$passCount++;
            } else {
                self::$failCount++;
                echo "  [FAIL] Expected null, got " . var_export($actual, true) . ". $message\n";
            }
        }

        public function assertNotNull(mixed $actual, string $message = ''): void
        {
            if ($actual !== null) {
                self::$passCount++;
            } else {
                self::$failCount++;
                echo "  [FAIL] Expected not null, got null. $message\n";
            }
        }

        public function assertStringContainsString(string $needle, ?string $haystack, string $message = ''): void
        {
            if ($haystack !== null && str_contains($haystack, $needle)) {
                self::$passCount++;
            } else {
                self::$failCount++;
                echo "  [FAIL] Expected string to contain '$needle'. $message\n";
            }
        }

        public function assertStringNotContainsString(string $needle, ?string $haystack, string $message = ''): void
        {
            if ($haystack === null || !str_contains($haystack, $needle)) {
                self::$passCount++;
            } else {
                self::$failCount++;
                echo "  [FAIL] Expected string NOT to contain '$needle'. $message\n";
            }
        }
    }
}
