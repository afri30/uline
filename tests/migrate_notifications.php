<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getConnection();
    echo "Connected to database successfully.\n";

    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'info' COMMENT 'info, success, warning, danger, exam, result, proctoring, system',
        `link` VARCHAR(255) DEFAULT NULL COMMENT 'Internal link URL',
        `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'Entity type for deduplication',
        `reference_id` INT DEFAULT NULL COMMENT 'Entity ID for deduplication',
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `read_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_user_read` (`user_id`, `is_read`),
        INDEX `idx_user_created` (`user_id`, `created_at`),
        INDEX `idx_dedup` (`user_id`, `reference_type`, `reference_id`),
        CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'notifications' created or already exists.\n";

    $cols = $pdo->query("DESCRIBE `notifications`")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in 'notifications':\n";
    foreach ($cols as $col) {
        echo " - {$col['Field']} ({$col['Type']}) " . ($col['Key'] ? "[{$col['Key']}]" : "") . "\n";
    }

} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
