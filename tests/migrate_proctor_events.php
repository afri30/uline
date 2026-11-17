<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

try {
    $db = Database::getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS `proctor_events` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `exam_session_id` INT NOT NULL,
        `camera_type` VARCHAR(50) NOT NULL DEFAULT 'camera_1',
        `event_type` VARCHAR(100) NOT NULL COMMENT 'FACE_NOT_DETECTED, MULTIPLE_FACES, CAMERA_DISCONNECTED, LOOKING_AWAY, SUSPICIOUS_ACTIVITY, TAB_SWITCHED',
        `confidence` FLOAT NOT NULL DEFAULT 1.0 COMMENT 'Confidence score 0.0 - 1.0',
        `severity` ENUM('INFO', 'WARNING', 'HIGH') NOT NULL DEFAULT 'INFO',
        `timestamp` DATETIME NOT NULL,
        `evidence_path` VARCHAR(255) DEFAULT NULL,
        `metadata` JSON DEFAULT NULL,
        `reviewed` TINYINT(1) NOT NULL DEFAULT 0,
        `review_note` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_pe_session` FOREIGN KEY (`exam_session_id`) 
            REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX `idx_pe_session` (`exam_session_id`),
        INDEX `idx_pe_type` (`event_type`),
        INDEX `idx_pe_severity` (`severity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "SUCCESS: proctor_events table created or already exists.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
