<?php

declare(strict_types=1);

/**
 * Automated Test Suite: WhatsApp Login via Fonnte Gateway
 * Tests Token Config, Database Schema, Phone Lookup, OTP Generation, Verification, Security & Role Dispatching.
 */

require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Teacher.php';
require_once __DIR__ . '/../app/Models/Student.php';
require_once __DIR__ . '/../app/Models/WhatsAppOtp.php';
require_once __DIR__ . '/../app/Services/Notification/FonnteService.php';

use App\Core\Env;
use App\Core\Database;
use App\Models\User;
use App\Models\WhatsAppOtp;
use App\Services\Notification\FonnteService;

Env::load();

$pass = 0;
$fail = 0;

function assertTest(string $desc, bool $condition): void {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  [PASS] {$desc}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$desc}\n";
    }
}

echo "====================================================================\n";
echo "    WHATSAPP LOGIN (FONNTE GATEWAY) - AUTOMATED TEST SUITE          \n";
echo "====================================================================\n\n";

$db = Database::getConnection();
$userModel = new User();
$otpModel = new WhatsAppOtp();
$fonnteService = new FonnteService();

// -----------------------------------------------------------------------------
// TEST 1: Kredensial & Konfigurasi Fonnte Token
// -----------------------------------------------------------------------------
echo "[1] Testing Fonnte Token Configuration & Security...\n";
$config = $fonnteService->getConfigurationStatus();
assertTest("FonnteService isConfigured returns true with provided key", $fonnteService->isConfigured() === true);
assertTest("Fonnte endpoint is https://api.fonnte.com/send", $config['endpoint'] === 'https://api.fonnte.com/send');
assertTest("Token is properly masked (starts with K4b7 and ends with JoAT)", str_starts_with($config['masked_token'], 'K4b7') && str_ends_with($config['masked_token'], 'JoAT'));
assertTest("Token length in mask preserves obfuscation with asterisks", str_contains($config['masked_token'], '****'));

// -----------------------------------------------------------------------------
// TEST 2: Struktur Database Tabel whatsapp_otps
// -----------------------------------------------------------------------------
echo "\n[2] Testing Database Schema whatsapp_otps...\n";
$stmtCheck = $db->query("SHOW TABLES LIKE 'whatsapp_otps'");
assertTest("Table 'whatsapp_otps' exists in database", $stmtCheck->rowCount() > 0);

$cols = $db->query("DESCRIBE `whatsapp_otps`")->fetchAll(PDO::FETCH_COLUMN);
assertTest("Column 'user_id' exists in whatsapp_otps", in_array('user_id', $cols));
assertTest("Column 'phone_number' exists in whatsapp_otps", in_array('phone_number', $cols));
assertTest("Column 'otp_code' exists in whatsapp_otps", in_array('otp_code', $cols));
assertTest("Column 'action' exists in whatsapp_otps", in_array('action', $cols));
assertTest("Column 'is_used' exists in whatsapp_otps", in_array('is_used', $cols));
assertTest("Column 'attempts' exists in whatsapp_otps", in_array('attempts', $cols));
assertTest("Column 'expires_at' exists in whatsapp_otps", in_array('expires_at', $cols));

// -----------------------------------------------------------------------------
// TEST 3: Pencarian Pengguna Berdasarkan Nomor WhatsApp (Multi-Format)
// -----------------------------------------------------------------------------
echo "\n[3] Testing User Lookup by WhatsApp Number (Multi-Format & Roles)...\n";
// 3.1 Admin user lookup
$admin = $userModel->findByPhone('089654411611');
assertTest("Find Super Admin by 089654411611", $admin !== false && $admin['role'] === 'SUPER_ADMIN');

$adminIntl = $userModel->findByPhone('6289654411611');
assertTest("Find Super Admin by 6289654411611", $adminIntl !== false && $adminIntl['id'] === $admin['id']);

$adminPlus = $userModel->findByPhone('+6289654411611');
assertTest("Find Super Admin by +6289654411611", $adminPlus !== false && $adminPlus['id'] === $admin['id']);

// 3.2 Guru lookup
$guru = $userModel->findByPhone('081234567890');
assertTest("Find Guru by 081234567890", $guru !== false && $guru['role'] === 'GURU');

// 3.3 Peserta lookup
$siswa = $userModel->findByPhone('085711223344');
assertTest("Find Peserta Didik by 085711223344", $siswa !== false && $siswa['role'] === 'PESERTA');

// 3.4 Non-existent number
$notFound = $userModel->findByPhone('089999999999');
assertTest("Non-existent number returns false", $notFound === false);

// -----------------------------------------------------------------------------
// TEST 4: Pembuatan & Siklus Hidup Kode OTP
// -----------------------------------------------------------------------------
echo "\n[4] Testing OTP Generation & Lifecycle...\n";
$testUser = $admin;
$testUserId = (int)$testUser['id'];
$testPhone = '6289654411611';

// Generate OTP
$otpData = $otpModel->generateOtp($testUserId, $testPhone, 'LOGIN', 5, '127.0.0.1');
assertTest("OTP data contains numeric code", isset($otpData['otp_code']) && is_numeric($otpData['otp_code']));
assertTest("OTP code length is exactly 6 digits", strlen((string)$otpData['otp_code']) === 6);
assertTest("OTP validity is 5 minutes", $otpData['valid_minutes'] === 5);

// Check database record
$stmtOtp = $db->prepare("SELECT * FROM `whatsapp_otps` WHERE `id` = :id");
$stmtOtp->execute([':id' => $otpData['id']]);
$rowOtp = $stmtOtp->fetch(PDO::FETCH_ASSOC);
assertTest("OTP record stored in database with is_used = 0", (int)$rowOtp['is_used'] === 0);
assertTest("OTP attempts initialized to 0", (int)$rowOtp['attempts'] === 0);

// Generate second OTP should invalidate the first one
$secondOtpData = $otpModel->generateOtp($testUserId, $testPhone, 'LOGIN', 5, '127.0.0.1');
$stmtOldOtp = $db->prepare("SELECT `is_used` FROM `whatsapp_otps` WHERE `id` = :id");
$stmtOldOtp->execute([':id' => $otpData['id']]);
assertTest("Subsequent OTP generation invalidates previous active OTP", (int)$stmtOldOtp->fetchColumn() === 1);

// -----------------------------------------------------------------------------
// TEST 5: Validasi & Keamanan Verifikasi Kode OTP
// -----------------------------------------------------------------------------
echo "\n[5] Testing OTP Verification & Anti-Brute Force Security...\n";
$activeOtp = $secondOtpData['otp_code'];

// 5.1 Salah memasukkan kode OTP
$wrongVerify = $otpModel->verifyOtp($testUserId, '000000', 'LOGIN');
assertTest("Wrong OTP verification fails", $wrongVerify['valid'] === false);
assertTest("Error message indicates remaining attempts", str_contains($wrongVerify['message'], 'Sisa percobaan'));

// 5.2 Kode OTP yang benar
$correctVerify = $otpModel->verifyOtp($testUserId, $activeOtp, 'LOGIN');
assertTest("Correct OTP verification succeeds", $correctVerify['valid'] === true);
assertTest("Returns authenticated user_id", $correctVerify['user_id'] === $testUserId);

// 5.3 Percobaan menggunakan kembali OTP yang sudah dipakai (One-Time Use)
$reuseVerify = $otpModel->verifyOtp($testUserId, $activeOtp, 'LOGIN');
assertTest("Used OTP cannot be reused (one-time protection)", $reuseVerify['valid'] === false);

// 5.4 Proteksi kadaluarsa (Expired OTP)
$expiredData = $otpModel->generateOtp($testUserId, $testPhone, 'LOGIN', -1, '127.0.0.1'); // Expired in past
$expiredVerify = $otpModel->verifyOtp($testUserId, $expiredData['otp_code'], 'LOGIN');
assertTest("Expired OTP is rejected", $expiredVerify['valid'] === false);
assertTest("Rejection message notes expiration", str_contains($expiredVerify['message'], 'kadaluarsa'));

// -----------------------------------------------------------------------------
// TEST 6: Proteksi Rate Limiting & Cooldown
// -----------------------------------------------------------------------------
echo "\n[6] Testing Rate Limiting & Request Cooldown...\n";
// Record an OTP right now
$recentOtp = $otpModel->generateOtp($testUserId, $testPhone, 'LOGIN', 5, '127.0.0.1');
$cooldownCheck = $otpModel->checkRateLimit($testPhone, '127.0.0.1');
assertTest("Immediate second request is rate-limited (60s cooldown)", $cooldownCheck['allowed'] === false);
assertTest("Rate-limit reason is cooldown", $cooldownCheck['reason'] === 'cooldown');

// -----------------------------------------------------------------------------
// TEST 7: Template Pesan OTP WhatsApp (Fonnte API)
// -----------------------------------------------------------------------------
echo "\n[7] Testing WhatsApp OTP Message Template via Fonnte...\n";
$otpTemplateRes = $fonnteService->sendLoginOtp($testPhone, 'Administrator', '839201', $testUserId);
assertTest("sendLoginOtp processes request via FonnteService", is_array($otpTemplateRes));

// Check logged message content
$stmtLog = $db->prepare("SELECT * FROM `whatsapp_logs` WHERE `notification_type` = 'LOGIN_OTP' AND `user_id` = :uid ORDER BY `id` DESC LIMIT 1");
$stmtLog->execute([':uid' => $testUserId]);
$logRow = $stmtLog->fetch(PDO::FETCH_ASSOC);

assertTest("Log notification_type is LOGIN_OTP", $logRow['notification_type'] === 'LOGIN_OTP');
assertTest("Message contains recipient name 'Administrator'", str_contains($logRow['message'], 'Administrator'));
assertTest("Message contains 6-digit OTP code '839201'", str_contains($logRow['message'], '839201'));
assertTest("Message contains security warning '5 menit'", str_contains($logRow['message'], '5 menit'));

// -----------------------------------------------------------------------------
// TEST 8: Penyiapan Sesi Autentikasi & Dashboard Redirection Berdasarkan Role
// -----------------------------------------------------------------------------
echo "\n[8] Testing Session Setup & Role-Based Dashboard Dispatching...\n";

// Super Admin
$sessionAdmin = $userModel->prepareUserForSession($admin);
assertTest("Session user has role SUPER_ADMIN", $sessionAdmin['role'] === 'SUPER_ADMIN');
assertTest("Password hash is stripped from session", !isset($sessionAdmin['password']));
assertTest("Super Admin redirected to admin/dashboard", User::getDashboardUrl($sessionAdmin['role']) === 'admin/dashboard');

// Guru
$sessionGuru = $userModel->prepareUserForSession($guru);
assertTest("Session user has role GURU", $sessionGuru['role'] === 'GURU');
assertTest("Guru redirected to guru/dashboard", User::getDashboardUrl($sessionGuru['role']) === 'guru/dashboard');

// Peserta
$sessionSiswa = $userModel->prepareUserForSession($siswa);
assertTest("Session user has role PESERTA", $sessionSiswa['role'] === 'PESERTA');
assertTest("Peserta redirected to peserta/dashboard", User::getDashboardUrl($sessionSiswa['role']) === 'peserta/dashboard');

// Clean up test OTP rows created during testing
$db->prepare("DELETE FROM `whatsapp_otps` WHERE `user_id` = :uid")->execute([':uid' => $testUserId]);

echo "\n====================================================================\n";
echo "TEST RESULTS: {$pass} PASSED, {$fail} FAILED\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);

