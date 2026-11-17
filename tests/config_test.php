<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Env;
use App\Core\Config;

echo "========================================================\n";
echo "  COMPREHENSIVE CONFIGURATION & ENV TEST SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(bool $condition, string $testName, string $details = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$testName}" . ($details ? " ({$details})" : "") . "\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$testName} - {$details}\n";
    }
}

// 1. Test .env Loader
echo "1. TESTING ENV LOADER (.env)...\n";
Env::load();

assertCheck(env('APP_NAME') === 'AI Online Examination & Proctoring System', "1. env('APP_NAME') matches .env value", (string)env('APP_NAME'));
assertCheck(env('DB_CONNECTION') === 'mysql', "2. env('DB_CONNECTION') matches .env value", (string)env('DB_CONNECTION'));
assertCheck(env('DB_DATABASE') === 'uline_db', "3. env('DB_DATABASE') matches .env value", (string)env('DB_DATABASE'));
assertCheck(env('APP_DEBUG') === true, "4. env('APP_DEBUG') correctly parsed as boolean true");
assertCheck(env('NON_EXISTENT_KEY', 'default_val') === 'default_val', "5. env() fallback default returns correctly");

// 2. Test Config Dot-Notation Accessor
echo "\n2. TESTING CONFIG DOT-NOTATION ACCESSOR (config())...\n";
assertCheck(config('app.app_name') === 'AI Online Examination & Proctoring System', "6. config('app.app_name') resolves correctly");
assertCheck(config('app.roles.SUPER_ADMIN') === 'SUPER_ADMIN', "7. config('app.roles.SUPER_ADMIN') resolves nested array");
assertCheck(config('database.host') === '127.0.0.1', "8. config('database.host') resolves correctly");
assertCheck(config('database.port') === '3306', "9. config('database.port') resolves correctly");
assertCheck(config('ai.provider') === 'nvidia', "10. config('ai.provider') resolves correctly");
assertCheck(!empty(config('ai.nvidia.api_key')), "11. config('ai.nvidia.api_key') is loaded");
assertCheck(config('proctoring.dual_camera.enabled') === true, "12. config('proctoring.dual_camera.enabled') resolves correctly");
assertCheck(config('proctoring.severity_weights.HIGH') === 15, "13. config('proctoring.severity_weights.HIGH') resolves correctly (15)");
assertCheck(config('mail.driver') === 'log', "14. config('mail.driver') resolves correctly ('log')");

// 3. Test Runtime Config Setting
echo "\n3. TESTING RUNTIME CONFIG OVERRIDE...\n";
Config::set('app.custom_test_key', 'runtime_value_123');
assertCheck(config('app.custom_test_key') === 'runtime_value_123', "15. Config::set() stores and retrieves runtime value");

// 4. Test Configuration Integrity across all files
echo "\n4. TESTING CONFIGURATION FILES INTEGRITY...\n";
$appConfig = Config::all('app');
$dbConfig = Config::all('database');
$aiConfig = Config::all('ai');
$proctoringConfig = Config::all('proctoring');
$mailConfig = Config::all('mail');

assertCheck(isset($appConfig['app_name'], $appConfig['session'], $appConfig['roles'], $appConfig['upload'], $appConfig['security']), "16. app.php has all required sections (app, session, roles, upload, security)");
assertCheck(isset($dbConfig['driver'], $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['username']), "17. database.php has all required credentials");
assertCheck(isset($aiConfig['provider'], $aiConfig['nvidia'], $aiConfig['guardrails']), "18. ai.php has provider, nvidia, and guardrails sections");
assertCheck(isset($proctoringConfig['dual_camera'], $proctoringConfig['severity_weights'], $proctoringConfig['risk_thresholds'], $proctoringConfig['events']), "19. proctoring.php has dual_camera, weights, thresholds, and events");
assertCheck(isset($mailConfig['driver'], $mailConfig['from']['address']), "20. mail.php has driver and from address");

echo "\n========================================================\n";
echo "CONFIG TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

exit($failCount > 0 ? 1 : 0);
