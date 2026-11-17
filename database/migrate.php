<?php

/**
 * AI Online Examination & Proctoring System
 * Database Migration & Seeder CLI Script
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

$config = require __DIR__ . '/../app/Config/database.php';

$host = $config['host'] ?? '127.0.0.1';
$port = $config['port'] ?? '3306';
$dbname = $config['database'] ?? 'uline_db';
$user = $config['username'] ?? 'root';
$pass = $config['password'] ?? '';
$charset = $config['charset'] ?? 'utf8mb4';

echo "=== AI Online Exam & Proctoring System - Database Migration ===\n";
echo "Connecting to MySQL server at {$host}:{$port}...\n";

try {
    // 1. Connect without database selected to create database if not exists
    $pdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "✓ Connected to MySQL server successfully.\n";

    // 2. Read schema.sql
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("schema.sql not found at {$sqlFile}");
    }

    echo "Reading schema.sql...\n";
    $sql = file_get_contents($sqlFile);

    // 3. Drop and recreate database cleanly
    echo "Resetting database `{$dbname}`...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$dbname}`");
    $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    // 4. Execute schema SQL
    echo "Executing database migration and table creation...\n";
    $pdo->exec($sql);

    echo "✓ Database `{$dbname}` and all tables created and seeded successfully!\n\n";

    // 4. Verify Users Table
    $pdo->exec("USE `{$dbname}`");
    $stmt = $pdo->query("SELECT id, name, username, email, role, status FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll();

    echo "--- Seeded Users List ---\n";
    echo str_pad("ID", 4) . str_pad("Name", 30) . str_pad("Username", 18) . str_pad("Role", 15) . str_pad("Status", 10) . "\n";
    echo str_repeat("-", 77) . "\n";

    foreach ($users as $u) {
        echo str_pad((string)$u['id'], 4) .
             str_pad($u['name'], 30) .
             str_pad($u['username'], 18) .
             str_pad($u['role'], 15) .
             str_pad($u['status'], 10) . "\n";
    }

    echo "\nAll default accounts have password: password123\n";
    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] PDO Exception: " . $e->getMessage() . "\n";
    echo "Please ensure MySQL is running in XAMPP on {$host}:{$port}.\n";
    exit(1);
} catch (Exception $e) {
    echo "\n[ERROR] Exception: " . $e->getMessage() . "\n";
    exit(1);
}
