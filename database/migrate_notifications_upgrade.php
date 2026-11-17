<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();
require_once __DIR__ . '/../app/Core/Database.php';

$db = \App\Core\Database::getConnection();

echo "========================================================\n";
echo "  MIGRATION: UPGRADE SISTEM NOTIFIKASI & MULTI-CHANNEL  \n";
echo "========================================================\n\n";

// 1. Alter users table to add phone_number, whatsapp_opt_in, push_opt_in if not exists
echo "[1] Memeriksa kolom di tabel `users`...\n";
$userCols = $db->query("DESCRIBE `users`")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('phone_number', $userCols, true)) {
    $db->exec("ALTER TABLE `users` ADD COLUMN `phone_number` VARCHAR(30) NULL AFTER `email`");
    echo "    + Kolom `phone_number` berhasil ditambahkan ke `users`.\n";
} else {
    echo "    - Kolom `phone_number` sudah ada.\n";
}

if (!in_array('whatsapp_opt_in', $userCols, true)) {
    $db->exec("ALTER TABLE `users` ADD COLUMN `whatsapp_opt_in` TINYINT(1) NOT NULL DEFAULT 0 AFTER `phone_number`");
    echo "    + Kolom `whatsapp_opt_in` berhasil ditambahkan ke `users`.\n";
} else {
    echo "    - Kolom `whatsapp_opt_in` sudah ada.\n";
}

if (!in_array('push_opt_in', $userCols, true)) {
    $db->exec("ALTER TABLE `users` ADD COLUMN `push_opt_in` TINYINT(1) NOT NULL DEFAULT 1 AFTER `whatsapp_opt_in`");
    echo "    + Kolom `push_opt_in` berhasil ditambahkan ke `users`.\n";
} else {
    echo "    - Kolom `push_opt_in` sudah ada.\n";
}

// 2. Create whatsapp_logs table
echo "\n[2] Memeriksa tabel `whatsapp_logs`...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `phone_number` VARCHAR(30) NOT NULL,
        `template_name` VARCHAR(100) NOT NULL,
        `message_payload` TEXT NOT NULL,
        `status` ENUM('pending', 'sent', 'failed', 'skipped_no_credential', 'skipped_not_consented') NOT NULL DEFAULT 'pending',
        `response_body` TEXT NULL,
        `error_message` TEXT NULL,
        `retry_count` INT NOT NULL DEFAULT 0,
        `idempotency_key` VARCHAR(64) NULL,
        `sent_at` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_wa_user` (`user_id`),
        INDEX `idx_wa_status` (`status`),
        INDEX `idx_wa_phone` (`phone_number`),
        UNIQUE INDEX `idx_wa_idempotency` (`idempotency_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "    + Tabel `whatsapp_logs` siap digunakan.\n";

// 3. Create push_subscriptions table
echo "\n[3] Memeriksa tabel `push_subscriptions`...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `push_subscriptions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `endpoint` TEXT NOT NULL,
        `endpoint_hash` VARCHAR(64) NOT NULL,
        `p256dh` VARCHAR(255) NOT NULL,
        `auth` VARCHAR(255) NOT NULL,
        `user_agent` VARCHAR(255) NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_push_user` (`user_id`),
        UNIQUE INDEX `idx_push_endpoint_hash` (`endpoint_hash`),
        INDEX `idx_push_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "    + Tabel `push_subscriptions` siap digunakan.\n";

echo "\n>>> Seluruh migrasi database berhasil dijalankan! <<<\n";
