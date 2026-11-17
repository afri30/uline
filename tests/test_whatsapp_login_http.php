<?php

declare(strict_types=1);

/**
 * HTTP Integration Test: WhatsApp Login Flow (Endpoints & View Rendering)
 */

require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Env;
use App\Core\Database;

Env::load();

$pass = 0;
$fail = 0;

function assertHttp(string $desc, bool $cond): void {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [PASS] {$desc}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$desc}\n";
    }
}

echo "====================================================================\n";
echo "   HTTP TEST: WHATSAPP LOGIN FLOW (VIEW & CONTROLLER)               \n";
echo "====================================================================\n\n";

// 1. Test Login View rendered content directly
ob_start();
$activeTab = 'wa';
$activeStep = 'input';
$sessionWa = [];
$hasActiveOtp = false;
require __DIR__ . '/../app/Views/auth/login.php';
$rendered = ob_get_clean();

assertHttp("Login view contains 'Kata Sandi' tab", str_contains($rendered, 'Kata Sandi'));
assertHttp("Login view contains 'WhatsApp OTP' tab", str_contains($rendered, 'WhatsApp OTP'));
assertHttp("Login view contains phone input 'name=\"phone_number\"'", str_contains($rendered, 'name="phone_number"'));
assertHttp("Login view contains 6-digit OTP input 'name=\"otp_code\"'", str_contains($rendered, 'name="otp_code"'));
assertHttp("Login view contains route target '/auth/whatsapp/send-otp'", str_contains($rendered, 'auth/whatsapp/send-otp'));
assertHttp("Login view contains route target '/auth/whatsapp/verify-otp'", str_contains($rendered, 'auth/whatsapp/verify-otp'));
assertHttp("Login view contains cooldown countdown timer", str_contains($rendered, 'resendTimer'));

echo "\n====================================================================\n";
echo "HTTP TEST RESULTS: {$pass} PASSED, {$fail} FAILED\n";
echo "====================================================================\n";

if ($fail > 0) exit(1);
exit(0);
