<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getConnection();
    echo "=== DATABASE CONNECTION SUCCESSFUL ===\n";
    echo "DB Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in Database:\n";
    foreach ($tables as $t) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo " - {$t} (Rows: {$count})\n";
        $cols = $pdo->query("DESCRIBE `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "     * {$col['Field']} ({$col['Type']}) " . ($col['Key'] ? "[{$col['Key']}]" : "") . "\n";
        }
    }
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
