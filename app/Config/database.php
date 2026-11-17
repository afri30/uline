<?php

declare(strict_types=1);

use App\Core\Env;

if (class_exists(Env::class)) {
    Env::load();
}

$host = function_exists('env') ? (string)env('DB_HOST', '127.0.0.1') : (getenv('DB_HOST') ?: '127.0.0.1');
$port = function_exists('env') ? (string)env('DB_PORT', '3306') : (getenv('DB_PORT') ?: '3306');
$database = function_exists('env') ? (string)env('DB_DATABASE', 'uline_db') : (getenv('DB_DATABASE') ?: 'uline_db');
$username = function_exists('env') ? (string)env('DB_USERNAME', 'root') : (getenv('DB_USERNAME') ?: 'root');
$password = function_exists('env') ? (string)env('DB_PASSWORD', '') : (getenv('DB_PASSWORD') !== false ? (string)getenv('DB_PASSWORD') : '');
$charset = function_exists('env') ? (string)env('DB_CHARSET', 'utf8mb4') : (getenv('DB_CHARSET') ?: 'utf8mb4');
$collation = function_exists('env') ? (string)env('DB_COLLATION', 'utf8mb4_unicode_ci') : (getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci');

return [
    'driver' => function_exists('env') ? (string)env('DB_CONNECTION', 'mysql') : 'mysql',
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset' => $charset,
    'collation' => $collation,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
