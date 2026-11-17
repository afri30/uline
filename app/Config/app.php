<?php

declare(strict_types=1);

use App\Core\Env;

if (class_exists(Env::class)) {
    Env::load();
}

$appName = function_exists('env') ? (string)env('APP_NAME', 'AI Online Examination & Proctoring System') : 'AI Online Examination & Proctoring System';
$appShortName = function_exists('env') ? (string)env('APP_SHORT_NAME', 'U-Line') : 'U-Line';
$appVersion = function_exists('env') ? (string)env('APP_VERSION', '1.0.0') : '1.0.0';
$appEnv = function_exists('env') ? (string)env('APP_ENV', 'development') : 'development';
$appDebug = function_exists('env') ? (bool)env('APP_DEBUG', true) : true;
$baseUrl = function_exists('env') ? (string)env('APP_URL', '') : '';
$timezone = function_exists('env') ? (string)env('APP_TIMEZONE', 'Asia/Jakarta') : 'Asia/Jakarta';
$locale = function_exists('env') ? (string)env('APP_LOCALE', 'id') : 'id';

return [
    'app_name' => $appName,
    'app_short_name' => $appShortName,
    'app_version' => $appVersion,
    'app_env' => $appEnv, // 'development' or 'production'
    'debug' => $appDebug,
    'timezone' => $timezone,
    'locale' => $locale,
    
    // Auto-detected base_url if empty, or can be specified explicitly e.g. 'http://localhost/uline'
    'base_url' => $baseUrl,
    
    'session' => [
        'name' => function_exists('env') ? (string)env('SESSION_NAME', 'uline_session') : 'uline_session',
        'lifetime' => function_exists('env') ? (int)env('SESSION_LIFETIME', 7200) : 7200, // 2 hours
        'path' => '/',
        'domain' => function_exists('env') ? (string)env('SESSION_DOMAIN', '') : '',
        'secure' => function_exists('env') ? (bool)env('SESSION_SECURE', false) : false,
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    'roles' => [
        'SUPER_ADMIN' => 'SUPER_ADMIN',
        'GURU' => 'GURU',
        'PESERTA' => 'PESERTA',
    ],

    'upload' => [
        'max_size_mb' => 10,
        'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'storage_path' => dirname(__DIR__, 2) . '/storage',
        'evidence_path' => dirname(__DIR__, 2) . '/storage/evidence',
    ],

    'security' => [
        'csrf_protection' => true,
        'bcrypt_cost' => 10,
    ]
];
