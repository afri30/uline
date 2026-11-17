<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Central Configuration Repository & Dot-Notation Accessor
 */
class Config
{
    private static array $items = [];

    /**
     * Get a configuration item using dot notation (e.g. 'app.app_name', 'database.host')
     *
     * @param string $key Dot-notated configuration key
     * @param mixed $default Default value if key is not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$items[$file])) {
            $path = dirname(__DIR__) . "/Config/{$file}.php";
            if (file_exists($path)) {
                self::$items[$file] = require $path;
            } else {
                return $default;
            }
        }

        $current = self::$items[$file];

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * Set a configuration item at runtime
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$items[$file])) {
            $path = dirname(__DIR__) . "/Config/{$file}.php";
            self::$items[$file] = file_exists($path) ? require $path : [];
        }

        $current = &self::$items[$file];
        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current = $value;
    }

    /**
     * Get all configuration items from a specific file
     *
     * @param string $file
     * @return array
     */
    public static function all(string $file): array
    {
        if (!isset(self::$items[$file])) {
            $path = dirname(__DIR__) . "/Config/{$file}.php";
            self::$items[$file] = file_exists($path) ? require $path : [];
        }

        return self::$items[$file];
    }
}
