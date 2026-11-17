<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Database.php';

echo "Migrating Database for Final Integration & Audit Logging...\n";

$db = App\Core\Database::getConnection();

// 1. Create audit_logs table
$sqlAuditLogs = "CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'LOGIN, LOGOUT, CREATE_EXAM, UPDATE_EXAM, DELETE_EXAM, START_EXAM, SUBMIT_EXAM, PROCTOR_EVENT, REVIEW_EVENT',
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_al_user` (`user_id`),
    INDEX `idx_al_action` (`action`),
    INDEX `idx_al_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$db->exec($sqlAuditLogs);
echo "[OK] Table audit_logs created or already exists.\n";

// 2. Add last_activity_at to exam_sessions if not exists
try {
    $db->exec("ALTER TABLE `exam_sessions` ADD COLUMN `last_activity_at` DATETIME NULL AFTER `remaining_seconds`");
    echo "[OK] Added last_activity_at column to exam_sessions.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "[OK] Column last_activity_at already exists in exam_sessions.\n";
    } else {
        echo "[WARN] exam_sessions last_activity_at: " . $e->getMessage() . "\n";
    }
}

// 3. Add review_status to proctor_events if not exists
try {
    $db->exec("ALTER TABLE `proctor_events` ADD COLUMN `review_status` ENUM('pending', 'reviewed', 'confirmed', 'dismissed') NOT NULL DEFAULT 'pending' AFTER `reviewed`");
    echo "[OK] Added review_status column to proctor_events.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "[OK] Column review_status already exists in proctor_events.\n";
    } else {
        echo "[WARN] proctor_events review_status: " . $e->getMessage() . "\n";
    }
}

echo "Migration completed successfully!\n";
