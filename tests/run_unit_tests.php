<?php

declare(strict_types=1);

/**
 * U-Line Zero-Dependency Unit Test Runner
 * Runs all unit test assertions without requiring Composer or a live MySQL database.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

use PHPUnit\Framework\TestCase;

echo "\n";
echo "========================================================================\n";
echo "  U-LINE CBT & AI PROCTORING SYSTEM - UNIT TEST SUITE\n";
echo "  Standard OBE Skor 4 - Automated Verification\n";
echo "========================================================================\n\n";

$testClasses = [
    \Tests\Unit\RiskScoringServiceTest::class,
    \Tests\Unit\DetectionServiceTest::class,
    \Tests\Unit\GuardrailServiceTest::class,
    \Tests\Unit\NvidiaAIServiceTest::class,
    \Tests\Unit\SecurityHelperTest::class,
    \Tests\Unit\PhoneFormatterTest::class,
];

$totalClasses = 0;
$totalMethods = 0;

foreach ($testClasses as $class) {
    $classFile = __DIR__ . '/Unit/' . substr(strrchr($class, '\\'), 1) . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }

    $shortName = substr(strrchr($class, '\\'), 1);
    echo "Running Test Suite: $shortName ...\n";
    $totalClasses++;

    $instance = new $class();
    $methods = get_class_methods($instance);

    foreach ($methods as $method) {
        if (str_starts_with($method, 'test')) {
            $totalMethods++;
            $initPass = TestCase::$passCount;
            $initFail = TestCase::$failCount;

            $refMethod = new \ReflectionMethod($instance, 'setUp');
            $refMethod->setAccessible(true);
            $refMethod->invoke($instance);

            $instance->$method();

            $refTear = new \ReflectionMethod($instance, 'tearDown');
            $refTear->setAccessible(true);
            $refTear->invoke($instance);

            $methodPassed = (TestCase::$failCount === $initFail);
            $status = $methodPassed ? "[PASS]" : "[FAIL]";
            echo "  $status $method\n";
        }
    }
    echo "\n";
}

echo "------------------------------------------------------------------------\n";
echo "Summary: $totalClasses Test Classes, $totalMethods Test Methods Executed\n";
echo "Assertions Passed : " . TestCase::$passCount . "\n";
echo "Assertions Failed : " . TestCase::$failCount . "\n";
echo "------------------------------------------------------------------------\n";

if (TestCase::$failCount === 0) {
    echo "Result: SUCCESS (100% Tests Passed - Ready for CI/CD Deployment)\n\n";
    exit(0);
} else {
    echo "Result: FAILURE (" . TestCase::$failCount . " Assertions Failed)\n\n";
    exit(1);
}
