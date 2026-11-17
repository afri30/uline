<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getConnection();
    echo "Connected to database successfully.\n";

    $sql = "CREATE TABLE IF NOT EXISTS `exam_configs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL DEFAULT 'Konfigurasi Utama Ujian Online',
        `duration` INT NOT NULL DEFAULT 60 COMMENT 'Durasi pengerjaan dalam menit',
        `random_questions` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Acak Soal, 0 = Urut',
        `random_options` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Acak Opsi Jawaban, 0 = Urut',
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `token` VARCHAR(50) NOT NULL DEFAULT 'ULINE2026',
        `token_secret` VARCHAR(255) DEFAULT NULL COMMENT 'Kredensial rahasia / salt token',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'exam_configs' created or already exists.\n";

    // Check if initial row exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM `exam_configs` WHERE `id` = 1");
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        $insertSql = "INSERT INTO `exam_configs` 
            (`id`, `title`, `duration`, `random_questions`, `random_options`, `start_time`, `end_time`, `token`, `token_secret`, `is_active`) 
            VALUES 
            (1, 'Konfigurasi Utama Ujian Online', 60, 1, 1, '2026-10-20 08:00:00', '2026-10-20 12:00:00', 'ULINE2026', 'exam_sec_f98c21a084eb73', 1)";
        $pdo->exec($insertSql);
        echo "Default exam_config row (id=1) inserted successfully.\n";
    } else {
        echo "Default exam_config row (id=1) already exists.\n";
    }

    $checkStmt = $pdo->query("SELECT * FROM `exam_configs` WHERE `id` = 1");
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    echo "Current exam_config (id=1): " . json_encode($row, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
