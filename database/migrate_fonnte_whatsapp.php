<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();
require_once __DIR__ . '/../app/Core/Database.php';

$db = \App\Core\Database::getConnection();

echo "========================================================\n";
echo "  MIGRATION: FONNTE WHATSAPP GATEWAY & SETTINGS SCHEMA  \n";
echo "========================================================\n\n";

// 1. Periksa dan modifikasi tabel `whatsapp_logs` agar memenuhi seluruh kolom yang disyaratkan
echo "[1] Memeriksa struktur tabel `whatsapp_logs`...\n";

$tables = $db->query("SHOW TABLES LIKE 'whatsapp_logs'")->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    $db->exec("
        CREATE TABLE `whatsapp_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `phone_number` VARCHAR(30) NOT NULL,
            `notification_type` VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
            `message` TEXT NOT NULL,
            `provider` VARCHAR(50) NOT NULL DEFAULT 'fonnte',
            `provider_response` TEXT NULL,
            `status` ENUM('PENDING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'PENDING',
            `error_message` TEXT NULL,
            `retry_count` INT NOT NULL DEFAULT 0,
            `idempotency_key` VARCHAR(64) NULL,
            `sent_at` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_wa_user` (`user_id`),
            INDEX `idx_wa_status` (`status`),
            INDEX `idx_wa_type` (`notification_type`),
            INDEX `idx_wa_phone` (`phone_number`),
            UNIQUE INDEX `idx_wa_idempotency` (`idempotency_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "    + Tabel `whatsapp_logs` baru berhasil dibuat.\n";
} else {
    // Check and add missing columns
    $existingCols = $db->query("DESCRIBE `whatsapp_logs`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('notification_type', $existingCols, true)) {
        $db->exec("ALTER TABLE `whatsapp_logs` ADD COLUMN `notification_type` VARCHAR(50) NOT NULL DEFAULT 'GENERAL' AFTER `phone_number`");
        echo "    + Kolom `notification_type` berhasil ditambahkan.\n";
    }

    if (!in_array('message', $existingCols, true)) {
        $db->exec("ALTER TABLE `whatsapp_logs` ADD COLUMN `message` TEXT NULL AFTER `notification_type`");
        // Copy existing message_payload to message if available
        if (in_array('message_payload', $existingCols, true)) {
            $db->exec("UPDATE `whatsapp_logs` SET `message` = `message_payload` WHERE `message` IS NULL OR `message` = ''");
        }
        echo "    + Kolom `message` berhasil ditambahkan.\n";
    }

    if (!in_array('provider', $existingCols, true)) {
        $db->exec("ALTER TABLE `whatsapp_logs` ADD COLUMN `provider` VARCHAR(50) NOT NULL DEFAULT 'fonnte' AFTER `message`");
        echo "    + Kolom `provider` berhasil ditambahkan.\n";
    }

    if (!in_array('provider_response', $existingCols, true)) {
        $db->exec("ALTER TABLE `whatsapp_logs` ADD COLUMN `provider_response` TEXT NULL AFTER `provider`");
        if (in_array('response_body', $existingCols, true)) {
            $db->exec("UPDATE `whatsapp_logs` SET `provider_response` = `response_body` WHERE `provider_response` IS NULL");
        }
        echo "    + Kolom `provider_response` berhasil ditambahkan.\n";
    }

    // Ubah kolom status agar mendukung ENUM('PENDING', 'SUCCESS', 'FAILED') atau VARCHAR
    $db->exec("ALTER TABLE `whatsapp_logs` MODIFY COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'PENDING'");
    $db->exec("UPDATE `whatsapp_logs` SET `status` = 'SUCCESS' WHERE `status` = 'sent'");
    $db->exec("UPDATE `whatsapp_logs` SET `status` = 'FAILED' WHERE `status` = 'failed'");
    $db->exec("UPDATE `whatsapp_logs` SET `status` = 'PENDING' WHERE `status` = 'pending'");
    echo "    + Kolom `status` dinormalisasi (PENDING, SUCCESS, FAILED).\n";
}

// 2. Buat tabel `whatsapp_settings` untuk preferensi notifikasi admin
echo "\n[2] Memeriksa tabel `whatsapp_settings`...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `whatsapp_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT NULL,
        `description` VARCHAR(255) NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$defaultSettings = [
    'notify_new_exam' => ['1', 'Kirim WhatsApp saat guru mempublikasikan ujian baru'],
    'notify_exam_reminder' => ['1', 'Kirim pengingat otomatis saat jadwal ujian mendekat'],
    'notify_schedule_change' => ['1', 'Kirim pemberitahuan saat jadwal ujian diubah'],
    'notify_exam_result' => ['1', 'Kirim pemberitahuan nilai saat hasil ujian dirilis'],
    'notify_teacher_finish' => ['1', 'Kirim laporan ringkasan ke guru saat peserta menyelesaikan ujian'],
    'reminder_lead_minutes' => ['15', 'Waktu pengingat sebelum ujian dimulai (dalam menit)']
];

$stmtInsertSetting = $db->prepare("
    INSERT INTO `whatsapp_settings` (`setting_key`, `setting_value`, `description`)
    VALUES (:k, :v, :d)
    ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)
");

foreach ($defaultSettings as $key => [$val, $desc]) {
    $stmtInsertSetting->execute([':k' => $key, ':v' => $val, ':d' => $desc]);
}
echo "    + Tabel `whatsapp_settings` dan nilai default siap digunakan.\n";

echo "\n>>> Migrasi Fonnte WhatsApp & Settings Selesai dengan Sukses! <<<\n";
