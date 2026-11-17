<?php

declare(strict_types=1);

use App\Core\Env;

if (class_exists(Env::class)) {
    Env::load();
}

/**
 * Mail & Notification Configuration
 */
return [
    'driver' => function_exists('env') ? (string)env('MAIL_DRIVER', 'log') : 'log', // 'log', 'smtp'
    'host' => function_exists('env') ? (string)env('MAIL_HOST', '127.0.0.1') : '127.0.0.1',
    'port' => function_exists('env') ? (int)env('MAIL_PORT', 1025) : 1025,
    'username' => function_exists('env') ? (string)env('MAIL_USERNAME', '') : '',
    'password' => function_exists('env') ? (string)env('MAIL_PASSWORD', '') : '',
    'encryption' => function_exists('env') ? (string)env('MAIL_ENCRYPTION', '') : '',
    'from' => [
        'address' => function_exists('env') ? (string)env('MAIL_FROM_ADDRESS', 'noreply@uline.test') : 'noreply@uline.test',
        'name' => function_exists('env') ? (string)env('MAIL_FROM_NAME', 'U-Line Examination System') : 'U-Line Examination System',
    ],
];
