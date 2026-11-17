<?php

declare(strict_types=1);

/**
 * U-Line Zero-Dependency Unit Test Runner
 * Runs all unit test assertions without requiring Composer or a live MySQL database.
 */

// If vendor PHPUnit is available, delegate to it for official testdox reporting
$vendorPhpunit = dirname(__DIR__) . '/vendor/bin/phpunit';
if (PHP_OS_FAMILY === 'Windows' && file_exists($vendorPhpunit . '.bat')) {
    $vendorPhpunit .= '.bat';
}

if (file_exists($vendorPhpunit)) {
    echo "Composer PHPUnit detected. Delegating to official PHPUnit test runner...\n\n";
    passthru(escapeshellcmd($vendorPhpunit) . ' --testdox', $exitCode);
    exit($exitCode);
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

use Tests\TestCounter;

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
            $initPass = TestCounter::$passCount;
            $initFail = TestCounter::$failCount;

            $refMethod = new \ReflectionMethod($instance, 'setUp');
            $refMethod->setAccessible(true);
            $refMethod->invoke($instance);

            $instance->$method();

            $refTear = new \ReflectionMethod($instance, 'tearDown');
            $refTear->setAccessible(true);
            $refTear->invoke($instance);

            $methodPassed = (TestCounter::$failCount === $initFail);
            $status = $methodPassed ? "[PASS]" : "[FAIL]";
            echo "  $status $method\n";
        }
    }
    echo "\n";
}

echo "------------------------------------------------------------------------\n";
echo "Summary: $totalClasses Test Classes, $totalMethods Test Methods Executed\n";
echo "Assertions Passed : " . TestCounter::$passCount . "\n";
echo "Assertions Failed : " . TestCounter::$failCount . "\n";
echo "------------------------------------------------------------------------\n";

if (TestCounter::$failCount === 0) {
    echo "Result: SUCCESS (100% Tests Passed - Ready for CI/CD Deployment)\n\n";
    exit(0);
} else {
    echo "Result: FAILURE (" . TestCounter::$failCount . " Assertions Failed)\n\n";
    exit(1);
}
