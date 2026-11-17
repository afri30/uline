<?php

declare(strict_types=1);

/**
 * Migration: Create whatsapp_otps table and synchronize user phone numbers for WhatsApp Login
 */

require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Env;
use App\Core\Database;

Env::load();

try {
    $db = Database::getConnection();
    echo "Connected to database successfully.\n";

    // 1. Create table `whatsapp_otps`
    $sqlOtps = "
    CREATE TABLE IF NOT EXISTS `whatsapp_otps` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `phone_number` VARCHAR(30) NOT NULL,
        `otp_code` VARCHAR(10) NOT NULL,
        `action` VARCHAR(50) NOT NULL DEFAULT 'LOGIN',
        `is_used` TINYINT(1) NOT NULL DEFAULT 0,
        `attempts` INT(11) NOT NULL DEFAULT 0,
        `expires_at` DATETIME NOT NULL,
        `ip_address` VARCHAR(45) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_user_action` (`user_id`, `action`),
        INDEX `idx_phone_otp` (`phone_number`, `otp_code`),
        INDEX `idx_expires` (`expires_at`, `is_used`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlOtps);
    echo "[OK] Table 'whatsapp_otps' verified/created.\n";

    // 2. Add 'notify_login_otp' to whatsapp_settings if missing
    $stmtCheckSetting = $db->prepare("SELECT COUNT(*) FROM `whatsapp_settings` WHERE `setting_key` = 'notify_login_otp'");
    $stmtCheckSetting->execute();
    if ((int)$stmtCheckSetting->fetchColumn() === 0) {
        $stmtInsert = $db->prepare("
            INSERT INTO `whatsapp_settings` (`setting_key`, `setting_value`, `description`)
            VALUES ('notify_login_otp', '1', 'Kirim kode OTP login melalui WhatsApp Gateway Fonnte')
        ");
        $stmtInsert->execute();
        echo "[OK] Setting 'notify_login_otp' added to whatsapp_settings.\n";
    }

    // 3. Synchronize phone numbers from students to users
    $stmtSyncStudents = $db->exec("
        UPDATE `users` u
        JOIN `students` s ON s.user_id = u.id
        SET u.phone_number = s.phone, u.whatsapp_opt_in = 1
        WHERE (u.phone_number IS NULL OR u.phone_number = '') AND (s.phone IS NOT NULL AND s.phone != '')
    ");
    echo "[OK] Synchronized {$stmtSyncStudents} student phone numbers into users table.\n";

    // 4. Synchronize phone numbers from teachers to users
    $stmtSyncTeachers = $db->exec("
        UPDATE `users` u
        JOIN `teachers` t ON t.user_id = u.id
        SET u.phone_number = t.phone, u.whatsapp_opt_in = 1
        WHERE (u.phone_number IS NULL OR u.phone_number = '') AND (t.phone IS NOT NULL AND t.phone != '')
    ");
    echo "[OK] Synchronized {$stmtSyncTeachers} teacher phone numbers into users table.\n";

    // 5. Ensure Super Admin user has phone_number set (set to Fonnte owner's device '089654411611' if empty)
    $stmtAdmin = $db->prepare("SELECT id, phone_number FROM `users` WHERE `role` = 'SUPER_ADMIN' LIMIT 1");
    $stmtAdmin->execute();
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    if ($admin && (empty($admin['phone_number']))) {
        $db->prepare("UPDATE `users` SET `phone_number` = '089654411611', `whatsapp_opt_in` = 1 WHERE `id` = :id")
           ->execute([':id' => $admin['id']]);
        echo "[OK] Set Super Admin phone number to '089654411611'.\n";
    }

    echo "\n=== Migration completed successfully! ===\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

