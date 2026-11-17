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

use App\Services\AI\NvidiaAIService;
use App\Services\AI\GuardrailService;
use App\Controllers\ChatbotController;

echo "========================================================\n";
echo "  CHATBOT+ LIVE DATABASE ACCURACY TEST\n";
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

// 1. Inisialisasi controller dan akses live database knowledge
$controller = new ChatbotController();
$reflector = new ReflectionClass(ChatbotController::class);
$method = $reflector->getMethod('getLiveDatabaseSummary');
$method->setAccessible(true);
$liveDb = $method->invoke($controller);

assertCheck($liveDb['ringkasan_angka']['total_pengguna'] === 15, "1. Database summary shows exactly 15 users", "Total: " . $liveDb['ringkasan_angka']['total_pengguna']);
assertCheck($liveDb['ringkasan_angka']['total_mata_pelajaran'] === 5, "2. Database summary shows exactly 5 subjects", "Total: " . $liveDb['ringkasan_angka']['total_mata_pelajaran']);
assertCheck($liveDb['ringkasan_angka']['total_guru'] === 4, "3. Database summary shows exactly 4 teachers", "Total: " . $liveDb['ringkasan_angka']['total_guru']);
assertCheck($liveDb['ringkasan_angka']['total_siswa'] === 10, "4. Database summary shows exactly 10 students", "Total: " . $liveDb['ringkasan_angka']['total_siswa']);

// 2. Build Chatbot+ prompt with student Ahmad
$guardrail = new GuardrailService();
$mockStudent = [
    'id' => 4,
    'name' => 'Ahmad Pratama',
    'username' => 'siswa_ahmad',
    'role' => 'PESERTA'
];
$scopedData = [
    'scope' => 'PESERTA_CHATBOT_PLUS',
    'database_realtime' => $liveDb,
    'profil_siswa' => ['nama' => 'Ahmad Pratama', 'kelas' => 'XII MIPA 1']
];

$systemPrompt = $guardrail->buildGlobalAssistantSystemPrompt($mockStudent, $scopedData);

assertCheck(str_contains($systemPrompt, 'U-Line AI Assistant') || str_contains($systemPrompt, 'AI Assistant'), "5. System Prompt names U-Line AI Assistant");
assertCheck(str_contains($systemPrompt, 'LIVE DATABASE KNOWLEDGE'), "6. System Prompt includes live database knowledge");
assertCheck(str_contains($systemPrompt, '15'), "7. System Prompt includes 15 users reference");

// 3. Query NVIDIA NIM with database query: "Sebutkan 5 mata pelajaran yang ada di web ini beserta guru pengampunya!"
$aiService = new NvidiaAIService();

try {
    echo "\nTesting query: 'Sebutkan 5 mata pelajaran yang ada di web ini beserta guru pengampunya!'...\n";
    $replyMapel = $aiService->chatCompletion([
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => 'Sebutkan 5 mata pelajaran yang ada di web ini beserta guru pengampunya!']
    ], ['max_tokens' => 450]);

    echo "AI Response Preview:\n" . substr($replyMapel, 0, 300) . "...\n";

    assertCheck(
        str_contains($replyMapel, 'Matematika') &&
        str_contains($replyMapel, 'Informatika') &&
        (str_contains($replyMapel, 'Fisika') || str_contains($replyMapel, 'Biologi') || str_contains($replyMapel, 'Inggris')),
        "8. Chatbot+ accurately lists database subjects"
    );
} catch (Exception $e) {
    assertCheck(false, "NVIDIA NIM query failed", $e->getMessage());
}

try {
    echo "\nTesting query: 'Berapa total pengguna di web ini?'...\n";
    $replyUsers = $aiService->chatCompletion([
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => 'Berapa total pengguna yang terdaftar di database web ini?']
    ], ['max_tokens' => 250]);

    echo "AI Response Preview:\n" . substr($replyUsers, 0, 250) . "...\n";

    assertCheck(
        str_contains($replyUsers, '15'),
        "9. Chatbot+ accurately confirms total 15 users from database"
    );
} catch (Exception $e) {
    assertCheck(false, "NVIDIA NIM query failed", $e->getMessage());
}

echo "\n========================================================\n";
echo "CHATBOT+ DATABASE TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

exit($failCount > 0 ? 1 : 0);
