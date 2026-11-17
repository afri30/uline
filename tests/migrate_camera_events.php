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
    $sql = "CREATE TABLE IF NOT EXISTS `camera_events` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `exam_session_id` INT NOT NULL,
        `camera_type` VARCHAR(50) NOT NULL COMMENT 'camera_1 (Face), camera_2 (Desk/Env)',
        `event_type` VARCHAR(100) NOT NULL COMMENT 'connected, disconnected, permission_denied, stream_error, device_changed',
        `timestamp` DATETIME NOT NULL,
        `metadata` JSON DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_ce_session` FOREIGN KEY (`exam_session_id`) 
            REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX `idx_ce_session` (`exam_session_id`),
        INDEX `idx_ce_type` (`camera_type`),
        INDEX `idx_ce_event` (`event_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "SUCCESS: camera_events table created or already exists.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
